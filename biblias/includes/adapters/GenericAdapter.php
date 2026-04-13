<?php
/**
 * Adapter genérico para schema "bible_books + bible_verses"
 * Usado em bancos no formato Open.Bible / theographic-bible-data
 */

require_once __DIR__ . '/AdapterInterface.php';

class GenericAdapter implements AdapterInterface
{
    private PDO    $pdo;
    private string $booksTable;
    private string $versesTable;
    private string $joinTable;

    public function __construct(PDO $pdo, string $booksTable, string $versesTable, string $joinTable = '')
    {
        $this->pdo         = $pdo;
        $this->booksTable  = $booksTable;
        $this->versesTable = $versesTable;
        $this->joinTable   = $joinTable ?: $versesTable;
    }

    public function extract(): array
    {
        // Detecta colunas do bible_verses
        $info = $this->pdo->query("PRAGMA table_info(`{$this->versesTable}`)")->fetchAll(PDO::FETCH_ASSOC);
        $cols = array_map(fn($r) => strtolower($r['name']), $info);

        $bookCol  = in_array('book_id', $cols)  ? 'book_id'  : (in_array('book', $cols) ? 'book' : 'b');
        $chapCol  = in_array('chapter', $cols)  ? 'chapter'  : 'c';
        $verseCol = in_array('verse', $cols)    ? 'verse'    : 'v';
        $textCol  = in_array('text', $cols)     ? 'text'     : (in_array('t', $cols) ? 't' : 'content');

        // Carrega mapa de IDs dos livros
        $bookIds = $this->pdo->query("SELECT id FROM `{$this->booksTable}` ORDER BY id")
                             ->fetchAll(PDO::FETCH_COLUMN);
        $bookMap = [];
        foreach ($bookIds as $i => $id) {
            if (($i + 1) <= 66) $bookMap[$id] = $i + 1;
        }

        $sql  = "SELECT `$bookCol` AS bid, `$chapCol` AS chapter, `$verseCol` AS verse, `$textCol` AS text
                 FROM `{$this->versesTable}` ORDER BY bid, chapter, verse";
        $rows = $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        $result = [];
        foreach ($rows as $r) {
            $bookNum = $bookMap[$r['bid']] ?? null;
            if ($bookNum === null) continue;
            $result[] = [
                'book_num' => $bookNum,
                'chapter'  => (int)$r['chapter'],
                'verse'    => (int)$r['verse'],
                'text'     => $r['text'],
            ];
        }
        return $result;
    }
}
