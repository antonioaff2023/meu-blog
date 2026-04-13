<?php
/**
 * AdapterFactory — detecta automaticamente o schema do SQLite externo
 * e retorna o adapter correto.
 *
 * Schemas suportados (auto-detectados pela inspeção das tabelas/colunas):
 *
 *   A) Schema "bible_books + bible_verses" (formato Open.Bible / theographic)
 *      Tabelas: bible_books(id, name, ...), bible_verses(book_id, chapter, verse, text)
 *
 *   B) Schema "books + verses" (formato bibliadigital.com.br)
 *      Tabelas: books(id, name, abbrev), verses(book_id, chapter, verse, text)
 *
 *   C) Schema "verse" única (formato popular github SQLite bibles)
 *      Tabelas: verse(b, c, v, t)  — b=book, c=chapter, v=verse, t=text
 *
 *   D) Schema "t_<sigla>" (formato YouVersion / eb.lc)
 *      Tabelas: t_acf(b, c, v, t) etc.
 *
 *   E) Schema "bible" + "book" + "chapter" + "verse" (formato legacy)
 *
 * Adicione novos adapters conforme necessário.
 */

require_once __DIR__ . '/AdapterInterface.php';
require_once __DIR__ . '/GenericAdapter.php';
require_once __DIR__ . '/BooksVersesAdapter.php';
require_once __DIR__ . '/SingleTableAdapter.php';
require_once __DIR__ . '/YouVersionAdapter.php';

class AdapterFactory
{
    public static function detect(string $version, string $filePath): AdapterInterface
    {
        $pdo = new PDO('sqlite:' . $filePath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Lista todas as tabelas do banco
        $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
        $tables = array_map('strtolower', $tables);

        // ── Schema D: t_<sigla> ──────────────────────────────────────────────
        $vLower = strtolower($version);
        if (in_array("t_{$vLower}", $tables)) {
            return new YouVersionAdapter($pdo, "t_{$vLower}");
        }
        // Tenta "t_" + qualquer nome que exista
        foreach ($tables as $t) {
            if (str_starts_with($t, 't_') && self::hasColumns($pdo, $t, ['b','c','v','t'])) {
                return new YouVersionAdapter($pdo, $t);
            }
        }

        // ── Schema C: tabela "verse" (b, c, v, t) ───────────────────────────
        if (in_array('verse', $tables) && self::hasColumns($pdo, 'verse', ['b','c','v','t'])) {
            return new SingleTableAdapter($pdo, 'verse');
        }

        // ── Schema A: bible_books + bible_verses ────────────────────────────
        if (in_array('bible_books', $tables) && in_array('bible_verses', $tables)) {
            return new GenericAdapter($pdo, 'bible_books', 'bible_verses', 'bible_verses');
        }

        // ── Schema B: books + verses ─────────────────────────────────────────
        if (in_array('books', $tables) && in_array('verses', $tables)) {
            // detecta nome da coluna de texto
            $textCol = self::detectTextColumn($pdo, 'verses', ['text','t','verse_text','content','versiculo']);
            return new BooksVersesAdapter($pdo, 'books', 'verses', $textCol);
        }

        // ── Fallback genérico: inspeciona a maior tabela e tenta mapear ──────
        return self::fallback($pdo, $tables, $version);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private static function hasColumns(PDO $pdo, string $table, array $cols): bool
    {
        $info = $pdo->query("PRAGMA table_info(`$table`)")->fetchAll(PDO::FETCH_ASSOC);
        $existing = array_map(fn($r) => strtolower($r['name']), $info);
        foreach ($cols as $c) {
            if (!in_array(strtolower($c), $existing)) return false;
        }
        return true;
    }

    private static function detectTextColumn(PDO $pdo, string $table, array $candidates): string
    {
        $info = $pdo->query("PRAGMA table_info(`$table`)")->fetchAll(PDO::FETCH_ASSOC);
        $existing = array_map(fn($r) => strtolower($r['name']), $info);
        foreach ($candidates as $c) {
            if (in_array(strtolower($c), $existing)) return $c;
        }
        return 'text'; // padrão
    }

    private static function fallback(PDO $pdo, array $tables, string $version): AdapterInterface
    {
        // Tenta encontrar qualquer tabela com colunas (b/book, c/chapter, v/verse, t/text)
        foreach ($tables as $t) {
            $info = $pdo->query("PRAGMA table_info(`$t`)")->fetchAll(PDO::FETCH_ASSOC);
            $cols = array_map(fn($r) => strtolower($r['name']), $info);
            if (
                (in_array('b', $cols) || in_array('book', $cols) || in_array('book_id', $cols)) &&
                (in_array('c', $cols) || in_array('chapter', $cols)) &&
                (in_array('v', $cols) || in_array('verse', $cols)) &&
                (in_array('t', $cols) || in_array('text', $cols) || in_array('content', $cols))
            ) {
                $bCol  = in_array('b', $cols) ? 'b' : (in_array('book', $cols) ? 'book' : 'book_id');
                $cCol  = in_array('c', $cols) ? 'c' : 'chapter';
                $vCol  = in_array('v', $cols) ? 'v' : 'verse';
                $tCol  = in_array('t', $cols) ? 't' : (in_array('text', $cols) ? 'text' : 'content');
                return new SingleTableAdapter($pdo, $t, $bCol, $cCol, $vCol, $tCol);
            }
        }
        throw new RuntimeException(
            "Schema desconhecido no arquivo SQLite da versão '$version'. " .
            "Inspecione as tabelas: " . implode(', ', $tables) . " e crie um adapter customizado."
        );
    }
}
