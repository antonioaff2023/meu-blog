<?php
/**
 * diagnostico_sqlite.php
 * Coloque em: app/control/posts/
 * Acesse pelo navegador: http://seu-servidor/app/control/posts/diagnostico_sqlite.php
 * 
 * Lista todas as tabelas e colunas de cada SQLite das Bíblias.
 */

$dbDir = dirname(__DIR__, 3) . '/app/database/biblias';

echo "<pre>\n";
echo "=== DIAGNÓSTICO DOS SQLITE DA BÍBLIA ===\n";
echo "Diretório: {$dbDir}\n\n";

if (!is_dir($dbDir)) {
    echo "ERRO: Diretório não encontrado!\n";
    exit;
}

$files = glob($dbDir . '/*.{sqlite,db}', GLOB_BRACE);
if (!$files) {
    echo "Nenhum arquivo .sqlite ou .db encontrado.\n";
    exit;
}

foreach ($files as $path) {
    $name = basename($path);
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "Arquivo: {$name}\n";

    try {
        $pdo = new PDO('sqlite:' . $path);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name")
                      ->fetchAll(PDO::FETCH_COLUMN);

        if (!$tables) {
            echo "  (nenhuma tabela encontrada)\n";
            continue;
        }

        foreach ($tables as $tbl) {
            echo "  Tabela: {$tbl}\n";
            $cols = $pdo->query("PRAGMA table_info(`{$tbl}`)")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($cols as $col) {
                echo "    - {$col['name']} ({$col['type']})\n";
            }

            // Mostra uma linha de exemplo
            try {
                $row = $pdo->query("SELECT * FROM `{$tbl}` LIMIT 1")->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    echo "    Exemplo: " . json_encode($row, JSON_UNESCAPED_UNICODE) . "\n";
                }
            } catch (Exception $e) {
                echo "    (erro ao ler exemplo: {$e->getMessage()})\n";
            }
        }

    } catch (Exception $e) {
        echo "  ERRO: {$e->getMessage()}\n";
    }

    echo "\n";
}

echo "</pre>\n";
