<?php
/**
 * Adapter para schemas com uma única tabela contendo (b, c, v, t)
 * Exemplos: tabela "verse" do formato popular GitHub SQLite bibles
 */

require_once __DIR__ . '/AdapterInterface.php';

class SingleTableAdapter implements AdapterInterface
{
    private PDO    $pdo;
    private string $table;
    private string $bCol;
    private string $cCol;
    private string $vCol;
    private string $tCol;

    public function __construct(
        PDO    $pdo,
        string $table  = 'verse',
        string $bCol   = 'b',
        string $cCol   = 'c',
        string $vCol   = 'v',
        string $tCol   = 't'
    ) {
        $this->pdo   = $pdo;
        $this->table = $table;
        $this->bCol  = $bCol;
        $this->cCol  = $cCol;
        $this->vCol  = $vCol;
        $this->tCol  = $tCol;
    }

    public function extract(): array
    {
        $sql  = "SELECT `{$this->bCol}` AS book_num,
                        `{$this->cCol}` AS chapter,
                        `{$this->vCol}` AS verse,
                        `{$this->tCol}` AS text
                 FROM `{$this->table}`
                 ORDER BY book_num, chapter, verse";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}
