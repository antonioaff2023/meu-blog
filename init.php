<?php
if (version_compare(PHP_VERSION, '8.2.0') == -1)
{
    die ('The minimum version required for PHP is 8.2.0');
}

// define the autoloader
require_once 'lib/adianti/core/AdiantiCoreLoader.php';
spl_autoload_register(array('Adianti\Core\AdiantiCoreLoader', 'autoload'));
Adianti\Core\AdiantiCoreLoader::loadClassMap();

// vendor autoloader
$loader = require 'vendor/autoload.php';
$loader->register();

// --- INÍCIO DA CONFIGURAÇÃO .ENV ---
// Carrega as variáveis de ambiente do arquivo .env na raiz
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->safeLoad();
}
// --- FIM DA CONFIGURAÇÃO .ENV ---

// apply app configurations
// (Isso deve vir DEPOIS do safeLoad() para que as configs leiam os dados do .env)
AdiantiApplicationConfig::start();

// define constants
define('PATH', dirname(__FILE__));

setlocale(LC_ALL, 'C');