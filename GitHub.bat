@echo off
chcp 65001 >nul
:: -------------------------------------------------------------------------
:: Script para envio automatico para o Gitea
:: -------------------------------------------------------------------------

echo ========================================================
echo      INICIANDO ENVIO AUTOMATICO PARA O GITEA
echo ========================================================
echo.

:: 1. Garante que estamos na pasta onde o arquivo .bat esta salvo
cd /d "%~dp0"

:: 2. Adiciona todos os arquivos modificados ou novos
echo [1/3] Adicionando arquivos (git add)...
git add .

:: 3. Realiza o Commit com Data, Hora e Usuario
echo [2/3] Gerando commit...
:: Monta a mensagem no formato: DD/MM/AAAA HH:MM:SS - Usuario
set MENSAGEM="%DATE% %TIME% - Por: %USERNAME%"

:: Executa o commit
git commit -m %MENSAGEM%

:: 4. Envia para o servidor
echo [3/3] Enviando para o servidor (git push)...
git push

echo.
echo ========================================================
echo                 PROCESSO FINALIZADO
echo ========================================================
echo.

:: Pausa para voce ver se deu erro ou sucesso antes de fechar
pause