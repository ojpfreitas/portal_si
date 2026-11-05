@echo off
chcp 65001 >nul
setlocal enabledelayedexpansion

SET /p PORT=Informe a porta do PostgreSQL [5432]: 
IF "%PORT%"=="" SET PORT=5432

SET /p USER=Informe o usuario do PostgreSQL [postgres]: 
IF "%USER%"=="" SET USER=postgres

SET /p PGPASSWORD=Informe a senha do usuario %USER%: 

SET PG_PATH="C:\Program Files\PostgreSQL\18\bin\psql.exe"
SET HOST=localhost
SET DB_NAME=portal_si
SET VERSIONS_DIR=versions
SET PGCLIENTENCODING=UTF8
SET PSQL=%PG_PATH% -U %USER% -h %HOST% -p %PORT% -d %DB_NAME% -v ON_ERROR_STOP=1

echo Verificando arquivos pendentes...

for %%F in (%VERSIONS_DIR%\*.sql) do (
    SET "ALREADY_APPLIED="
    %PSQL% -tAc "SELECT 1 FROM schema_version WHERE filename='%%~nxF';" > tmp_check.txt
    IF ERRORLEVEL 1 (
        DEL tmp_check.txt
        echo ERRO ao consultar schema_version. Atualizacao interrompida.
        goto :falha
    )
    SET /p ALREADY_APPLIED=<tmp_check.txt
    DEL tmp_check.txt

    IF NOT "!ALREADY_APPLIED!"=="1" (
        echo Executando %%~nxF...
        %PSQL% -f "%%F"
        IF ERRORLEVEL 1 (
            echo ERRO em %%~nxF. A versao NAO foi registrada. Atualizacao interrompida.
            goto :falha
        )
        %PSQL% -c "INSERT INTO schema_version (filename) VALUES ('%%~nxF');"
        IF ERRORLEVEL 1 (
            echo ERRO ao registrar %%~nxF em schema_version. Atualizacao interrompida.
            goto :falha
        )
    )
)

echo Atualizacao concluida.
pause
exit /b 0

:falha
pause
exit /b 1