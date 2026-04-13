<?php
/**
 * Adapter para o formato YouVersion / eb.lc
 * Tabela: t_<sigla> com colunas (b, c, v, t)
 * Exemplo: t_acf, t_kjv, t_nvi
 */

require_once __DIR__ . '/AdapterInterface.php';

class YouVersionAdapter implements AdapterInterface
{
    private PDO    $pdo;
    private string $table;

    public function __construct(PDO $pdo, string $table)
    {
        $this->pdo   = $pdo;
        $this->table = $table;
    }

    public function extract(): array
    {
        $sql = "SELECT b AS book_num, c AS chapter, v AS verse, t AS text
                FROM `{$this->table}`
                ORDER BY b, c, v";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}
