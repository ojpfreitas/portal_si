@echo off
echo Verificando e criando banco de dados portal_si...

REM === CONFIGURACOES ===
SET /p PORT=Informe a porta do PostgreSQL [5432]: 
IF "%PORT%"=="" SET PORT=5432

SET /p USER=Informe o usuario do PostgreSQL [postgres]: 
IF "%USER%"=="" SET USER=postgres

SET /p PGPASSWORD=Informe a senha do usuario %USER%: 

SET PG_PATH="C:\Program Files\PostgreSQL\18\bin\psql.exe"
SET HOST=localhost
SET DB_NAME=portal_si

REM === VERIFICA SE O BANCO EXISTE ===
%PG_PATH% -U %USER% -h %HOST% -p %PORT% -tAc "SELECT 1 FROM pg_database WHERE datname='%DB_NAME%';" > tmp_db_check.txt

SET /p DB_EXISTS=<tmp_db_check.txt
DEL tmp_db_check.txt

IF "%DB_EXISTS%"=="1" (
    echo O banco "%DB_NAME%" ja existe. Nada a fazer.
    pause
    exit /b 0
)

REM === CRIA O BANCO CASO NAO EXISTA ===
echo Criando banco "%DB_NAME%"...
%PG_PATH% -U %USER% -h %HOST% -p %PORT% -c "CREATE DATABASE %DB_NAME%;"

REM === CRIA A TABELA DE CONTROLE DE VERSAO ===
echo Criando tabela schema_version...
%PG_PATH% -U %USER% -h %HOST% -p %PORT% -d %DB_NAME% -c "CREATE TABLE schema_version (id SERIAL PRIMARY KEY, filename VARCHAR(255) NOT NULL UNIQUE, applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP);"

echo Banco criado com sucesso.
pause