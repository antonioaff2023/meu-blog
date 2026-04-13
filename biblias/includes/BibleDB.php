<?php
/**
 * BibleDB — Gerenciador do banco central SQLite unificado
 * Cria e gerencia a tabela central de versículos importados de múltiplos SQLites
 */

require_once __DIR__ . '/config.php';

class BibleDB
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = new PDO('sqlite:' . CENTRAL_DB);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec('PRAGMA journal_mode=WAL');
        $this->pdo->exec('PRAGMA synchronous=NORMAL');
        $this->createSchema();
    }

    // ─── Criação do schema central ──────────────────────────────────────────────

    private function createSchema(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS verses (
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                version     TEXT    NOT NULL,       -- sigla: ACF, NVI, etc.
                book_num    INTEGER NOT NULL,       -- 1=Gênesis … 66=Apocalipse
                chapter     INTEGER NOT NULL,
                verse       INTEGER NOT NULL,
                text        TEXT    NOT NULL,
                UNIQUE(version, book_num, chapter, verse)
            );
            CREATE INDEX IF NOT EXISTS idx_verses_lookup  ON verses(version, book_num, chapter, verse);
            CREATE INDEX IF NOT EXISTS idx_verses_text    ON verses(text);
            CREATE INDEX IF NOT EXISTS idx_verses_book    ON verses(book_num);

            -- Tabela de controle de importações
            CREATE TABLE IF NOT EXISTS import_log (
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                version     TEXT    NOT NULL,
                file_path   TEXT    NOT NULL,
                imported_at TEXT    NOT NULL DEFAULT (datetime('now','localtime')),
                verse_count INTEGER NOT NULL DEFAULT 0,
                status      TEXT    NOT NULL DEFAULT 'ok'
            );
        ");
    }

    // ─── Importação de um SQLite externo ────────────────────────────────────────

    /**
     * Importa versículos de um arquivo SQLite externo usando o adapter adequado.
     * Retorna o número de versículos importados.
     */
    public function importBible(string $version, string $filePath): int
    {
        if (!file_exists($filePath)) {
            throw new RuntimeException("Arquivo não encontrado: $filePath");
        }

        require_once __DIR__ . '/adapters/AdapterFactory.php';
        $adapter = AdapterFactory::detect($version, $filePath);
        $rows    = $adapter->extract();          // array de [book_num, chapter, verse, text]

        $count = 0;
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare("
                INSERT OR REPLACE INTO verses (version, book_num, chapter, verse, text)
                VALUES (:version, :book_num, :chapter, :verse, :text)
            ");
            foreach ($rows as $r) {
                $stmt->execute([
                    ':version'  => strtoupper($version),
                    ':book_num' => (int)$r['book_num'],
                    ':chapter'  => (int)$r['chapter'],
                    ':verse'    => (int)$r['verse'],
                    ':text'     => trim($r['text']),
                ]);
                $count++;
            }
            $this->pdo->commit();
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }

        // Registra na log
        $log = $this->pdo->prepare("
            INSERT INTO import_log (version, file_path, verse_count) VALUES (?,?,?)
        ");
        $log->execute([$version, $filePath, $count]);

        return $count;
    }

    // ─── Versões disponíveis no banco central ───────────────────────────────────

    public function getAvailableVersions(): array
    {
        $rows = $this->pdo->query("SELECT DISTINCT version FROM verses ORDER BY version")->fetchAll(PDO::FETCH_COLUMN);
        $books = BIBLE_BOOKS;
        $versions = BIBLE_VERSIONS;
        $result = [];
        foreach ($rows as $v) {
            $result[$v] = $versions[$v]['label'] ?? $v;
        }
        return $result;
    }

    // ─── Busca por palavra-chave ────────────────────────────────────────────────

    /**
     * Busca versículos cujo texto contém $keyword.
     * Filtros opcionais: version (sigla ou array), book_num.
     * Retorna array de linhas com todos os campos + book_name.
     */
    public function searchByKeyword(
        string $keyword,
        array  $versions  = [],
        int    $bookNum   = 0,
        int    $limit     = 200,
        int    $offset    = 0
    ): array {
        [$sql, $params] = $this->buildKeywordQuery($keyword, $versions, $bookNum, false);
        $sql .= " LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $this->decorateRows($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function countByKeyword(string $keyword, array $versions = [], int $bookNum = 0): int
    {
        [$sql, $params] = $this->buildKeywordQuery($keyword, $versions, $bookNum, true);
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    private function buildKeywordQuery(string $keyword, array $versions, int $bookNum, bool $count): array
    {
        $select  = $count ? "SELECT COUNT(*)" : "SELECT *";
        $sql     = "$select FROM verses WHERE text LIKE :kw";
        $params  = [':kw' => '%' . $keyword . '%'];

        if (!empty($versions)) {
            $placeholders = [];
            foreach (array_values($versions) as $i => $v) {
                $key = ":v$i";
                $placeholders[] = $key;
                $params[$key] = strtoupper($v);
            }
            $sql .= " AND version IN (" . implode(',', $placeholders) . ")";
        }
        if ($bookNum > 0) {
            $sql .= " AND book_num = :book_num";
            $params[':book_num'] = $bookNum;
        }
        if (!$count) $sql .= " ORDER BY version, book_num, chapter, verse";
        return [$sql, $params];
    }

    // ─── Busca por referência bíblica ───────────────────────────────────────────

    /**
     * Busca versículos por referência: livro + capítulo + versículo opcional.
     * $versions: array de siglas a exibir (vazio = todos).
     */
    public function searchByReference(
        int   $bookNum,
        int   $chapter,
        int   $verse   = 0,
        array $versions = []
    ): array {
        $sql    = "SELECT * FROM verses WHERE book_num=:b AND chapter=:c";
        $params = [':b' => $bookNum, ':c' => $chapter];

        if ($verse > 0) {
            $sql .= " AND verse=:v";
            $params[':v'] = $verse;
        }
        if (!empty($versions)) {
            $placeholders = [];
            foreach (array_values($versions) as $i => $v) {
                $key = ":v$i";
                $placeholders[] = $key;
                $params[$key] = strtoupper($v);
            }
            $sql .= " AND version IN (" . implode(',', $placeholders) . ")";
        }
        $sql .= " ORDER BY book_num, chapter, verse, version";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->execute();
        return $this->decorateRows($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    // ─── Busca por passagem com intervalo (pode cruzar capítulos) ───────────────

    /**
     * Retorna versículos de um intervalo que pode cruzar capítulos.
     * Ex: Jo 3.1-15, Hb 5.11-6.6
     *
     * Algoritmo: converte cada versículo em uma chave ordinal
     *   key = chapter * 10000 + verse
     * e filtra entre startKey e endKey dentro do mesmo livro.
     *
     * Para intervalos intra-capítulo (Hb 5.11-6.6):
     *   startBook=58, startChap=5, startVerse=11
     *   endBook=58,   endChap=6,   endVerse=6
     *
     * $versions: array de siglas (vazio = todos).
     */
    public function searchByRange(
        int   $bookNum,
        int   $startChapter,
        int   $startVerse,
        int   $endChapter,
        int   $endVerse,
        array $versions = []
    ): array {
        // Monta condição de intervalo
        // (chapter > startChap) OR (chapter = startChap AND verse >= startVerse)
        // AND
        // (chapter < endChap)   OR (chapter = endChap   AND verse <= endVerse)
        $sql = "SELECT * FROM verses
                WHERE book_num = :bn
                AND (
                    (chapter > :sc1) OR (chapter = :sc2 AND verse >= :sv)
                )
                AND (
                    (chapter < :ec1) OR (chapter = :ec2 AND verse <= :ev)
                )";
        $params = [
            ':bn'  => $bookNum,
            ':sc1' => $startChapter, ':sc2' => $startChapter, ':sv' => $startVerse,
            ':ec1' => $endChapter,   ':ec2' => $endChapter,   ':ev' => $endVerse,
        ];

        if (!empty($versions)) {
            $placeholders = [];
            foreach (array_values($versions) as $i => $v) {
                $key = ":ver$i";
                $placeholders[] = $key;
                $params[$key]   = strtoupper($v);
            }
            $sql .= " AND version IN (" . implode(',', $placeholders) . ")";
        }
        $sql .= " ORDER BY chapter, verse, version";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->execute();
        return $this->decorateRows($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    // ─── Busca por livro completo (capítulos) ───────────────────────────────────

    public function getChaptersForBook(int $bookNum): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT DISTINCT chapter FROM verses WHERE book_num=? ORDER BY chapter"
        );
        $stmt->execute([$bookNum]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getVersesCount(int $bookNum, int $chapter): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT DISTINCT verse FROM verses WHERE book_num=? AND chapter=? ORDER BY verse"
        );
        $stmt->execute([$bookNum, $chapter]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // ─── Estatísticas ────────────────────────────────────────────────────────────

    public function getStats(): array
    {
        $total = $this->pdo->query("SELECT COUNT(*) FROM verses")->fetchColumn();
        $byVer = $this->pdo->query(
            "SELECT version, COUNT(*) as cnt FROM verses GROUP BY version ORDER BY version"
        )->fetchAll(PDO::FETCH_ASSOC);
        return ['total' => (int)$total, 'by_version' => $byVer];
    }

    // ─── Auxiliares ─────────────────────────────────────────────────────────────

    private function decorateRows(array $rows): array
    {
        $books = BIBLE_BOOKS;
        foreach ($rows as &$row) {
            $b = (int)$row['book_num'];
            $row['book_name']  = $books[$b]['name']  ?? "Livro $b";
            $row['book_abbr']  = $books[$b]['abbr']  ?? "L$b";
            $row['version_label'] = BIBLE_VERSIONS[$row['version']]['label'] ?? $row['version'];
            $row['reference']  = "{$row['book_name']} {$row['chapter']}:{$row['verse']}";
        }
        return $rows;
    }

    public function getPdo(): PDO { return $this->pdo; }
}
