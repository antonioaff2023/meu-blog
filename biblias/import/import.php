#!/usr/bin/env php
<?php
/**
 * import/import.php — CLI para importar arquivos SQLite externos
 *
 * USO:
 *   php import/import.php                         (importa todos configurados em config.php)
 *   php import/import.php NVI                     (importa só a NVI)
 *   php import/import.php NVI ACF KJV             (importa versões específicas)
 *   php import/import.php --list                  (lista versões configuradas)
 *   php import/import.php --stats                 (exibe estatísticas do banco central)
 *   php import/import.php --inspect data/bibles/meu.sqlite  (inspeciona schema)
 */

define('CLI', php_sapi_name() === 'cli');
if (!CLI) die("Execute via linha de comando: php import/import.php\n");

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/BibleDB.php';

// ─── Parse de argumentos ───────────────────────────────────────────────────────

$args   = array_slice($argv, 1);
$target = [];

if (in_array('--list', $args)) {
    echo "\nVersões configuradas em BIBLE_VERSIONS:\n";
    echo str_pad('Sigla', 8) . str_pad('Label', 40) . "Arquivo\n";
    echo str_repeat('-', 70) . "\n";
    foreach (BIBLE_VERSIONS as $sig => $info) {
        $path   = BIBLES_PATH . '/' . $info['file'];
        $exists = file_exists($path) ? '✓' : '✗ não encontrado';
        echo str_pad($sig, 8) . str_pad($info['label'], 40) . $info['file'] . " [$exists]\n";
    }
    exit(0);
}

if (in_array('--stats', $args)) {
    $db    = new BibleDB();
    $stats = $db->getStats();
    echo "\nEstatísticas do banco central\n";
    echo "Total de versículos: " . number_format($stats['total'], 0, ',', '.') . "\n\n";
    echo str_pad('Versão', 8) . "Versículos\n";
    echo str_repeat('-', 20) . "\n";
    foreach ($stats['by_version'] as $row) {
        echo str_pad($row['version'], 8) . number_format($row['cnt'], 0, ',', '.') . "\n";
    }
    exit(0);
}

if (in_array('--inspect', $args)) {
    $idx  = array_search('--inspect', $args);
    $file = $args[$idx + 1] ?? null;
    if (!$file || !file_exists($file)) {
        die("Arquivo não encontrado. Use: php import/import.php --inspect caminho/para/arquivo.sqlite\n");
    }
    $pdo    = new PDO('sqlite:' . $file);
    $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
    echo "\nTabelas em $file:\n";
    foreach ($tables as $t) {
        $info = $pdo->query("PRAGMA table_info(`$t`)")->fetchAll(PDO::FETCH_ASSOC);
        $cols = implode(', ', array_column($info, 'name'));
        $count = $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
        echo "  [$t] → $cols  ({$count} linhas)\n";
    }
    exit(0);
}

// Filtra versões a importar
if (!empty($args)) {
    foreach ($args as $a) {
        $a = strtoupper($a);
        if (isset(BIBLE_VERSIONS[$a])) $target[] = $a;
        else echo "Aviso: versão '$a' não configurada em config.php, ignorada.\n";
    }
} else {
    $target = array_keys(BIBLE_VERSIONS);
}

// ─── Importação ───────────────────────────────────────────────────────────────

$db = new BibleDB();
echo "\nBuscador Bíblico — Importação de versões\n";
echo str_repeat('=', 50) . "\n";

$imported = 0;
$skipped  = 0;
$errors   = 0;

foreach ($target as $sig) {
    $info = BIBLE_VERSIONS[$sig];
    $file = BIBLES_PATH . '/' . $info['file'];

    echo "\n► $sig — {$info['label']}\n";

    if (!file_exists($file)) {
        echo "  ✗ Arquivo não encontrado: {$info['file']}\n";
        echo "    Coloque o SQLite em: data/bibles/{$info['file']}\n";
        $skipped++;
        continue;
    }

    try {
        echo "  Importando...";
        $start = microtime(true);
        $count = $db->importBible($sig, $file);
        $elapsed = round(microtime(true) - $start, 2);
        echo " ✓ {$count} versículos ({$elapsed}s)\n";
        $imported++;
    } catch (Exception $e) {
        echo "\n  ✗ Erro: " . $e->getMessage() . "\n";
        $errors++;
    }
}

echo "\n" . str_repeat('=', 50) . "\n";
echo "Concluído: $imported versão(ões) importada(s)";
if ($skipped) echo ", $skipped não encontrada(s)";
if ($errors)  echo ", $errors com erro";
echo "\n\n";
