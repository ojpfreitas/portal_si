@echo off
SET /p NOME=Nome: 
SET /p EMAIL=Email: 
SET /p SENHA=Senha: 

C:\xampp\php\php.exe create_admin.php "%NOME%" "%EMAIL%" "%SENHA%"
pause