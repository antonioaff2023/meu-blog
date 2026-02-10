@echo off
:: Obter caminho do diretório atual
set "source_dir=%cd%"

:: Obter nome do diretório atual
for %%i in ("%CD%") do set "nomeDiretorio=%%~nxi"

:: Definir o caminho de destino
set "copiaPara=O:\"

:: Executar a cópia usando robocopy
robocopy  "%source_dir%" %copiaPara% /XO 

echo Copiado arquivos para %copiaPara%