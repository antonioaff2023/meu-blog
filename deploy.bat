@echo off
:: Obter caminho do diretório atual
set "source_dir=%cd%"

:: Obter nome do diretório atual
for %%i in ("%CD%") do set "nomeDiretorio=%%~nxi"

:: Definir o caminho de destino
:: Não esquecer de configurar as permissões de escrita para o usuário que irá executar este script
:: Basta acessar o caminho pelo Windows Explorer, clicar com o botão direito, escolher "Propriedades", ir para a aba "Segurança" e configurar as permissões para o usuário desejado
set "copiaPara=E:\www\"


:: Executar a cópia usando robocopy
robocopy  "%source_dir%" %copiaPara% /XO 

:: o arquivo .env é ignorado para não sobrescrever as configurações de ambiente no servidor
:: a pasta app/images é ignorada para não sobrescrever imagens no servidor

:: Caso precise copiar tudo descomente a linha abaixo e comente a linha do robocopy
:: xcopy "%source_dir%\*" %copiaPara% /XO

echo Copiado arquivos para %copiaPara%