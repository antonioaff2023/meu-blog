<?php
return [
    'host'  =>  $_ENV['DB_HOST'],
    'port'  =>  "3306",
    'name'  =>  $_ENV['DB_NAME'],
    'user'  =>  $_ENV['DB_USER'],
    'pass'  =>  $_ENV['DB_PASS'],
    'type'  =>  "mysql",
    'prep'  =>  "1"
];

/* Não esquecer de criar o arquivo .env na raiz do projeto com as variáveis:
DB_HOST="seu_host_aqui"
DB_NAME="seu_nome_de_banco_aqui"
DB_USER="seu_usuario_aqui"
DB_PASS="sua_senha_aqui"

Instalar a biblioteca vlucas/phpdotenv via composer se ainda não estiver instalada:
composer require vlucas/phpdotenv
Em init.php, garantir que o carregamento do .env ocorra antes de chamar AdiantiApplicationConfig::start():
// --- INÍCIO DA CONFIGURAÇÃO .ENV ---
// Carrega as variáveis de ambiente do arquivo .env na raiz
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->safeLoad();
}
// --- FIM DA CONFIGURAÇÃO .ENV ---

*/