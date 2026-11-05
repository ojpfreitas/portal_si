@echo off
chcp 65001 >nul
setlocal enabledelayedexpansion
cd /d "%~dp0"

REM === CONFIGURACOES ===
SET /p HOST=Informe o host do PostgreSQL [postgres-portal-si-g1-phpau-portal-si-esucri-pe4-g1-phpau.a.aivencloud.com]: 
IF "%HOST%"=="" SET HOST=postgres-portal-si-g1-phpau-portal-si-esucri-pe4-g1-phpau.a.aivencloud.com

SET /p PORT=Informe a porta do PostgreSQL [10917]: 
IF "%PORT%"=="" SET PORT=10917

SET /p USER=Informe o usuario do PostgreSQL [avnadmin]: 
IF "%USER%"=="" SET USER=avnadmin

SET /p PGPASSWORD=Informe a senha do usuario %USER%: 

SET PG_PATH="C:\Program Files\PostgreSQL\18\bin\psql.exe"
SET DB_NAME=portal_si
SET VERSIONS_DIR=versions
SET PGCLIENTENCODING=UTF8
SET PGSSLMODE=require
SET PSQL=%PG_PATH% -U %USER% -h %HOST% -p %PORT% -d %DB_NAME% -v ON_ERROR_STOP=1

REM === VERIFICA A PASTA DE VERSOES ===
IF NOT EXIST "%VERSIONS_DIR%\*.sql" (
    echo ERRO: nenhum arquivo .sql encontrado em "%VERSIONS_DIR%".
    goto :falha
)

REM === VERIFICA A CONEXAO / EXISTENCIA DO BANCO ===
echo Verificando conexao com o banco "%DB_NAME%"...
%PSQL% -tAc "SELECT 1;" >nul
IF ERRORLEVEL 1 (
    echo ERRO: nao foi possivel conectar ao banco "%DB_NAME%" em %HOST%:%PORT%.
    echo Verifique host, porta, usuario, senha e se o banco foi criado.
    goto :falha
)

REM === VERIFICA A TABELA DE CONTROLE DE VERSAO ===
echo Verificando tabela schema_version...
SET "TABELA_OK="
%PSQL% -tAc "SELECT 1 FROM information_schema.tables WHERE table_schema='public' AND table_name='schema_version';" > tmp_check.txt
IF ERRORLEVEL 1 (
    DEL tmp_check.txt
    echo ERRO ao verificar schema_version. Atualizacao interrompida.
    goto :falha
)
SET /p TABELA_OK=<tmp_check.txt
DEL tmp_check.txt

IF NOT "!TABELA_OK!"=="1" (
    echo Tabela schema_version nao encontrada. Criando...
    %PSQL% -c "CREATE TABLE schema_version (id SERIAL PRIMARY KEY, filename VARCHAR(255) NOT NULL UNIQUE, applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP);"
    IF ERRORLEVEL 1 (
        echo ERRO ao criar schema_version. Atualizacao interrompida.
        goto :falha
    )
)

REM === APLICA AS VERSOES PENDENTES ===
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