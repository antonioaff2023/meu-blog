<?php
/**
 * Adapter para schemas com tabelas "books" + "verses"
 * Faz JOIN pelo book_id e normaliza para book_num 1-66.
 *
 * Formato esperado:
 *   books:  id (int), name (text), abbrev (text)
 *   verses: id, book_id (FK->books.id), chapter, verse, text
 */

require_once __DIR__ . '/AdapterInterface.php';
require_once __DIR__ . '/../../includes/config.php';

class BooksVersesAdapter implements AdapterInterface
{
    private PDO    $pdo;
    private string $booksTable;
    private string $versesTable;
    private string $textCol;

    public function __construct(
        PDO    $pdo,
        string $booksTable  = 'books',
        string $versesTable = 'verses',
        string $textCol     = 'text'
    ) {
        $this->pdo         = $pdo;
        $this->booksTable  = $booksTable;
        $this->versesTable = $versesTable;
        $this->textCol     = $textCol;
    }

    public function extract(): array
    {
        // Carrega mapa de livros do banco externo → book_num canônico
        $bookMap = $this->buildBookMap();

        $sql = "SELECT v.book_id, v.chapter, v.verse, v.`{$this->textCol}` AS text
                FROM `{$this->versesTable}` v
                ORDER BY v.book_id, v.chapter, v.verse";
        $rows = $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        $result = [];
        foreach ($rows as $r) {
            $bookNum = $bookMap[$r['book_id']] ?? null;
            if ($bookNum === null) continue; // livro não mapeado (apócrife, etc.)
            $result[] = [
                'book_num' => $bookNum,
                'chapter'  => (int)$r['chapter'],
                'verse'    => (int)$r['verse'],
                'text'     => $r['text'],
            ];
        }
        return $result;
    }

    /**
     * Mapeia book_id do banco externo para o número canônico (1-66).
     * Tenta por posição ordinal (a maioria dos bancos ordena os 66 livros em sequência).
     */
    private function buildBookMap(): array
    {
        $books = $this->pdo->query("SELECT id FROM `{$this->booksTable}` ORDER BY id")
                           ->fetchAll(PDO::FETCH_COLUMN);
        $map = [];
        foreach ($books as $i => $id) {
            $canonical = $i + 1;
            if ($canonical <= 66) {
                $map[$id] = $canonical;
            }
        }
        return $map;
    }
}
