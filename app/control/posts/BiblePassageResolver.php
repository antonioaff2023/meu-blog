<?php

/**
 * BiblePassageResolver.php
 * Resolve uma passagem bíblica lendo diretamente os arquivos SQLite locais.
 *
 * Coloque este arquivo em: app/control/posts/
 *
 * USO:
 *   $resolver = new BiblePassageResolver('/caminho/absoluto/para/app/database/biblias');
 *   $html     = $resolver->resolveHtml('Jo 3.1-21', 'NAA');
 *
 * Schemas suportados (mesmos do biblia-buscador):
 *   D) t_<sigla>(b, c, v, t)
 *   C) verse(b, c, v, t)
 *   B) books + verses(book_id, chapter, verse, text)
 *   A) bible_books + bible_verses(book_id, chapter, verse, text)
 *   Fallback: inspeciona colunas de qualquer tabela
 */
class BiblePassageResolver
{
    // Mapa sigla → nome base do arquivo SQLite
    private const FILE_MAP = [
        'ACF'  => 'acf',
        'ARA'  => 'ara',
        'ARC'  => 'arc',
        'AS21' => 'as21',
        'JFAA' => 'jfaa',
        'KJA'  => 'kja',
        'KJF'  => 'kjf',
        'NAA'  => 'naa',
        'NVI'  => 'nvi',
        'NVT'  => 'nvt',
    ];

    // Livros canônicos 1-66
    private const BOOKS = [
        1 => ['name' => 'Gênesis',           'abbr' => ['gn', 'genesis', 'gênesis']],
        2 => ['name' => 'Êxodo',             'abbr' => ['ex', 'êx', 'exodo', 'êxodo', 'exodus']],
        3 => ['name' => 'Levítico',          'abbr' => ['lv', 'lev', 'levitico', 'levítico']],
        4 => ['name' => 'Números',           'abbr' => ['nm', 'num', 'numeros', 'números']],
        5 => ['name' => 'Deuteronômio',      'abbr' => ['dt', 'deu', 'deuteronomio', 'deuteronômio']],
        6 => ['name' => 'Josué',             'abbr' => ['js', 'jos', 'josue', 'josué']],
        7 => ['name' => 'Juízes',            'abbr' => ['jz', 'jui', 'juizes', 'juízes']],
        8 => ['name' => 'Rute',              'abbr' => ['rt', 'rute']],
        9 => ['name' => '1 Samuel',          'abbr' => ['1sm', '1samuel']],
        10 => ['name' => '2 Samuel',          'abbr' => ['2sm', '2samuel']],
        11 => ['name' => '1 Reis',            'abbr' => ['1rs', '1reis']],
        12 => ['name' => '2 Reis',            'abbr' => ['2rs', '2reis']],
        13 => ['name' => '1 Crônicas',        'abbr' => ['1cr', '1cronicas', '1crônicas']],
        14 => ['name' => '2 Crônicas',        'abbr' => ['2cr', '2cronicas', '2crônicas']],
        15 => ['name' => 'Esdras',            'abbr' => ['ed', 'esdras']],
        16 => ['name' => 'Neemias',           'abbr' => ['ne', 'neemias']],
        17 => ['name' => 'Ester',             'abbr' => ['et', 'ester', 'éster']],
        18 => ['name' => 'Jó',               'abbr' => ['jó',  'job']],
        19 => ['name' => 'Salmos',            'abbr' => ['sl', 'salmos', 'ps', 'psalms']],
        20 => ['name' => 'Provérbios',        'abbr' => ['pv', 'prov', 'proverbios', 'provérbios']],
        21 => ['name' => 'Eclesiastes',       'abbr' => ['ec', 'eclesiastes', 'ecl']],
        22 => ['name' => 'Cantares',          'abbr' => ['ct', 'cantares']],
        23 => ['name' => 'Isaías',            'abbr' => ['is', 'isaias', 'isaías']],
        24 => ['name' => 'Jeremias',          'abbr' => ['jr', 'jeremias']],
        25 => ['name' => 'Lamentações',       'abbr' => ['lm', 'lamentacoes', 'lamentações']],
        26 => ['name' => 'Ezequiel',          'abbr' => ['ez', 'ezequiel']],
        27 => ['name' => 'Daniel',            'abbr' => ['dn', 'daniel']],
        28 => ['name' => 'Oséias',            'abbr' => ['os', 'oseias', 'oséias']],
        29 => ['name' => 'Joel',              'abbr' => ['jl', 'joel']],
        30 => ['name' => 'Amós',              'abbr' => ['am', 'amos', 'amós']],
        31 => ['name' => 'Obadias',           'abbr' => ['ob', 'obadias']],
        32 => ['name' => 'Jonas',             'abbr' => ['jn', 'jonas']],
        33 => ['name' => 'Miquéias',          'abbr' => ['mq', 'miqueias', 'miquéias']],
        34 => ['name' => 'Naum',              'abbr' => ['na', 'naum']],
        35 => ['name' => 'Habacuque',         'abbr' => ['hc', 'habacuque']],
        36 => ['name' => 'Sofonias',          'abbr' => ['sf', 'sofonias']],
        37 => ['name' => 'Ageu',              'abbr' => ['ag', 'ageu']],
        38 => ['name' => 'Zacarias',          'abbr' => ['zc', 'zacarias']],
        39 => ['name' => 'Malaquias',         'abbr' => ['ml', 'malaquias']],
        40 => ['name' => 'Mateus',            'abbr' => ['mt', 'mateus']],
        41 => ['name' => 'Marcos',            'abbr' => ['mc', 'marcos']],
        42 => ['name' => 'Lucas',             'abbr' => ['lc', 'lucas']],
        43 => ['name' => 'João',              'abbr' => ['jo', 'joao', 'joão', 'john']],
        44 => ['name' => 'Atos',              'abbr' => ['at', 'atos', 'acts']],
        45 => ['name' => 'Romanos',           'abbr' => ['rm', 'romanos', 'romans']],
        46 => ['name' => '1 Coríntios',       'abbr' => ['1co', '1corintios', '1coríntios']],
        47 => ['name' => '2 Coríntios',       'abbr' => ['2co', '2corintios', '2coríntios']],
        48 => ['name' => 'Gálatas',           'abbr' => ['gl', 'galatas', 'gálatas']],
        49 => ['name' => 'Efésios',           'abbr' => ['ef', 'efesios', 'efésios']],
        50 => ['name' => 'Filipenses',        'abbr' => ['fp', 'filipenses']],
        51 => ['name' => 'Colossenses',       'abbr' => ['cl', 'colossenses']],
        52 => ['name' => '1 Tessalonicenses', 'abbr' => ['1ts', '1tessalonicenses']],
        53 => ['name' => '2 Tessalonicenses', 'abbr' => ['2ts', '2tessalonicenses']],
        54 => ['name' => '1 Timóteo',         'abbr' => ['1tm', '1timoteo', '1timóteo']],
        55 => ['name' => '2 Timóteo',         'abbr' => ['2tm', '2timoteo', '2timóteo']],
        56 => ['name' => 'Tito',              'abbr' => ['tt', 'tito']],
        57 => ['name' => 'Filemom',           'abbr' => ['fm', 'filemom']],
        58 => ['name' => 'Hebreus',           'abbr' => ['hb', 'hebreus', 'hebrews']],
        59 => ['name' => 'Tiago',             'abbr' => ['tg', 'tiago', 'james']],
        60 => ['name' => '1 Pedro',           'abbr' => ['1pe', '1pedro']],
        61 => ['name' => '2 Pedro',           'abbr' => ['2pe', '2pedro']],
        62 => ['name' => '1 João',            'abbr' => ['1jo', '1joao', '1joão']],
        63 => ['name' => '2 João',            'abbr' => ['2jo', '2joao', '2joão']],
        64 => ['name' => '3 João',            'abbr' => ['3jo', '3joao', '3joão']],
        65 => ['name' => 'Judas',             'abbr' => ['jd', 'judas']],
        66 => ['name' => 'Apocalipse',        'abbr' => ['ap', 'apocalipse', 'revelation']],
    ];

    private string $dbDir;

    public function __construct(string $dbDir = 'app/database/biblias')
    {
        $this->dbDir = rtrim($dbDir, '/\\');
    }

    // ─── Ponto de entrada principal ───────────────────────────────────────────────

    public function resolveHtml(string $passagem, string $versao = 'NAA'): string
    {
        $versao = strtoupper(trim($versao));
        $parsed = $this->parseRange(trim($passagem));

        if (!$parsed) {
            throw new \RuntimeException("Referência não reconhecida: \"{$passagem}\". Use o formato: Jo 3.1-21 ou Hb 5.11-6.6");
        }

        $pdo  = $this->openDb($versao);
        $rows = $this->fetchVerses($pdo, $parsed, $versao);

        if (empty($rows)) {
            throw new \RuntimeException("Nenhum versículo encontrado para \"{$passagem}\" na versão {$versao}.");
        }

        return $this->buildHtml($rows, $parsed, $versao);
    }

    // ─── Parser de referência ─────────────────────────────────────────────────────

    public function parseRange(string $text): ?array
    {
        $text = trim(preg_replace('/\s+/', ' ', $text));

        if (!preg_match('/^(\d?\s?[a-záàâãéèêíìîóòôõúùûçñü]+\.?)/iu', $text, $bm)) {
            return null;
        }
        $bookRaw = rtrim(trim($bm[1]), '.');
        $bookNum = $this->lookupBook($bookRaw);
        if (!$bookNum) return null;

        $rest = ltrim(substr($text, strlen($bm[0])), ' :');
        if ($rest === '') return null;

        $parts = preg_split('/\s*[–\-]\s*/', $rest, 2);
        [$startChap, $startVs] = $this->parseChapterVerse($parts[0]);
        if (!$startChap) return null;

        $endChap = $startChap;
        $endVs   = 999;

        if (isset($parts[1]) && $parts[1] !== '') {
            $endRaw = trim($parts[1]);
            if (preg_match('/^\d+[.:]\d+$/', $endRaw)) {
                [$endChap, $endVs] = $this->parseChapterVerse($endRaw);
            } elseif (preg_match('/^\d+$/', $endRaw)) {
                if ($startVs > 0) {
                    $endVs = (int)$endRaw;
                } else {
                    $endChap = (int)$endRaw;
                    $endVs   = 999;
                }
            }
        } elseif ($startVs === 0) {
            $startVs = 1;
        } else {
            $endVs = $startVs;
        }

        if ($startVs === 0) $startVs = 1;

        $bookName = self::BOOKS[$bookNum]['name'];

        if ($startChap === $endChap) {
            $label = "{$bookName} {$startChap}:{$startVs}";
            if ($endVs !== $startVs && $endVs < 999) $label .= "–{$endVs}";
        } else {
            $endVsStr = ($endVs < 999) ? ":{$endVs}" : '';
            $label = "{$bookName} {$startChap}:{$startVs}–{$endChap}{$endVsStr}";
        }

        return compact('bookNum', 'bookName', 'startChap', 'startVs', 'endChap', 'endVs', 'label');
    }

    private function parseChapterVerse(string $str): array
    {
        $str = trim($str);
        if (preg_match('/^(\d+)[.:,](\d+)$/', $str, $m)) return [(int)$m[1], (int)$m[2]];
        if (preg_match('/^(\d+)$/', $str, $m))            return [(int)$m[1], 0];
        return [0, 0];
    }

    private function lookupBook(string $raw): int
    {
        // Limpa a string original removendo pontos e espaços extras
        $rawClean = trim(str_replace('.', '', $raw));
        $rawLower = mb_strtolower($rawClean);

        // 1ª Passagem: Busca exata (ignora maiúsculas/minúsculas, mas RESPEITA acentos)
        // Isso garante que "Jo" vá para João e "Jó" vá para Jó.
        foreach (self::BOOKS as $n => $info) {
            foreach ($info['abbr'] as $abbr) {
                if (mb_strtolower($abbr) === $rawLower) {
                    return $n;
                }
            }
        }

        // 2ª Passagem: Fallback normalizado (REMOVE acentos)
        // Mantém a tolerância a falhas para outros livros (ex: "genesis" encontrar "gênesis")
        $norm = $this->normalizeStr($rawClean);
        foreach (self::BOOKS as $n => $info) {
            foreach ($info['abbr'] as $abbr) {
                if ($this->normalizeStr($abbr) === $norm) {
                    return $n;
                }
            }
        }

        return 0; // Nenhum livro encontrado
    }

    private function normalizeStr(string $s): string
    {
        $s = mb_strtolower(trim($s));
        $s = str_replace('.', '', $s);
        if (function_exists('transliterator_transliterate')) {
            $s = transliterator_transliterate('Any-Latin; Latin-ASCII', $s);
        } else {
            $s = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
        }
        $s = preg_replace('/[^a-z0-9]/', '', $s);
        return $s;
    }

    // ─── Acesso ao SQLite ─────────────────────────────────────────────────────────

    private function openDb(string $versao): \PDO
    {
        $base = self::FILE_MAP[$versao] ?? strtolower($versao);
        foreach (['.sqlite', '.db', ''] as $ext) {
            $path = $this->dbDir . '/' . $base . $ext;
            if (file_exists($path)) {
                $pdo = new \PDO('sqlite:' . $path);
                $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
                return $pdo;
            }
        }
        throw new \RuntimeException("Arquivo SQLite da versão {$versao} não encontrado em {$this->dbDir}/");
    }

    // ─── Detecção de schema e query ───────────────────────────────────────────────

    private function fetchVerses(\PDO $pdo, array $p, string $versao): array
    {
        $tables   = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(\PDO::FETCH_COLUMN);
        $tablesLo = array_map('strtolower', $tables);
        $vLower   = strtolower($versao);

        // Schema D: t_<sigla>(b, c, v, t)
        if (in_array("t_{$vLower}", $tablesLo)) {
            return $this->queryBCV($pdo, "t_{$vLower}", 'b', 'c', 'v', 't', $p);
        }
        // Qualquer tabela t_* com colunas b,c,v,t
        foreach ($tablesLo as $t) {
            if (str_starts_with($t, 't_') && $this->tableHasCols($pdo, $t, ['b', 'c', 'v', 't'])) {
                return $this->queryBCV($pdo, $t, 'b', 'c', 'v', 't', $p);
            }
        }

        // Schema C: verse(b, c, v, t)
        if (in_array('verse', $tablesLo) && $this->tableHasCols($pdo, 'verse', ['b', 'c', 'v', 't'])) {
            return $this->queryBCV($pdo, 'verse', 'b', 'c', 'v', 't', $p);
        }

        // Schema A: bible_books + bible_verses
        if (in_array('bible_books', $tablesLo) && in_array('bible_verses', $tablesLo)) {
            $tCol = $this->detectTextCol($pdo, 'bible_verses', ['text', 't', 'verse_text', 'content']);
            return $this->queryBooksVerses($pdo, 'bible_books', 'bible_verses', $tCol, $p);
        }

        // Schema B: books + verses
        if (in_array('books', $tablesLo) && in_array('verses', $tablesLo)) {
            $tCol = $this->detectTextCol($pdo, 'verses', ['text', 't', 'verse_text', 'content', 'versiculo']);
            return $this->queryBooksVerses($pdo, 'books', 'verses', $tCol, $p);
        }

        // Fallback: inspeciona todas as tabelas
        foreach ($tablesLo as $t) {
            $cols = $this->getTableCols($pdo, $t);
            $bCol = $this->findCol($cols, ['b', 'book', 'book_id', 'book_num']);
            $cCol = $this->findCol($cols, ['c', 'chapter']);
            $vCol = $this->findCol($cols, ['v', 'verse']);
            $tCol = $this->findCol($cols, ['t', 'text', 'content', 'verse_text']);
            if ($bCol && $cCol && $vCol && $tCol) {
                return $this->queryBCV($pdo, $t, $bCol, $cCol, $vCol, $tCol, $p);
            }
        }

        throw new \RuntimeException("Schema do SQLite da versão {$versao} não reconhecido. Tabelas encontradas: " . implode(', ', $tablesLo));
    }

    /**
     * Query para schemas com colunas diretas de livro/capítulo/versículo (D e C).
     * O número do livro canônico é armazenado diretamente na coluna $b.
     */
    private function queryBCV(
        \PDO $pdo,
        string $table,
        string $b,
        string $c,
        string $v,
        string $t,
        array $p
    ): array {
        $sql = "SELECT `$b` AS book_num, `$c` AS chapter, `$v` AS verse, `$t` AS text
                FROM `$table`
                WHERE `$b` = :bn
                  AND ((`$c` > :sc1) OR (`$c` = :sc2 AND `$v` >= :sv))
                  AND ((`$c` < :ec1) OR (`$c` = :ec2 AND `$v` <= :ev))
                ORDER BY `$c`, `$v`";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':bn'  => $p['bookNum'],
            ':sc1' => $p['startChap'],
            ':sc2' => $p['startChap'],
            ':sv' => $p['startVs'],
            ':ec1' => $p['endChap'],
            ':ec2' => $p['endChap'],
            ':ev' => $p['endVs'],
        ]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Query para schemas books+verses / bible_books+bible_verses (A e B).
     * Faz JOIN usando book_id; mapeia posição ordinal → número canônico 1-66.
     */
    private function queryBooksVerses(
        \PDO $pdo,
        string $booksTable,
        string $versesTable,
        string $textCol,
        array $p
    ): array {
        // Constrói mapa: book_id no banco → número canônico (1-66) por posição
        $bookIds = $pdo->query("SELECT id FROM `{$booksTable}` ORDER BY id")->fetchAll(\PDO::FETCH_COLUMN);
        $bookMap = [];
        foreach ($bookIds as $i => $id) {
            if (($i + 1) <= 66) $bookMap[$id] = $i + 1;
        }

        // Acha o book_id correspondente ao livro pedido
        $targetId = array_search($p['bookNum'], $bookMap);
        if ($targetId === false) return [];

        $sql = "SELECT :bn AS book_num, chapter, verse, `{$textCol}` AS text
                FROM `{$versesTable}`
                WHERE book_id = :bid
                  AND ((chapter > :sc1) OR (chapter = :sc2 AND verse >= :sv))
                  AND ((chapter < :ec1) OR (chapter = :ec2 AND verse <= :ev))
                ORDER BY chapter, verse";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':bn'  => $p['bookNum'],
            ':bid' => $targetId,
            ':sc1' => $p['startChap'],
            ':sc2' => $p['startChap'],
            ':sv' => $p['startVs'],
            ':ec1' => $p['endChap'],
            ':ec2' => $p['endChap'],
            ':ev' => $p['endVs'],
        ]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    // ─── Helpers de inspeção de schema ────────────────────────────────────────────

    private function tableHasCols(\PDO $pdo, string $table, array $needed): bool
    {
        $cols = $this->getTableCols($pdo, $table);
        foreach ($needed as $c) {
            if (!in_array(strtolower($c), $cols)) return false;
        }
        return true;
    }

    private function getTableCols(\PDO $pdo, string $table): array
    {
        $rows = $pdo->query("PRAGMA table_info(`{$table}`)")->fetchAll(\PDO::FETCH_ASSOC);
        return array_map(fn($r) => strtolower($r['name']), $rows);
    }

    private function detectTextCol(\PDO $pdo, string $table, array $candidates): string
    {
        $cols = $this->getTableCols($pdo, $table);
        foreach ($candidates as $c) {
            if (in_array(strtolower($c), $cols)) return $c;
        }
        return 'text';
    }

    private function findCol(array $cols, array $candidates): ?string
    {
        foreach ($candidates as $c) {
            if (in_array(strtolower($c), $cols)) return $c;
        }
        return null;
    }

    // ─── Formatação HTML ──────────────────────────────────────────────────────────

    // ─── Formatação HTML ──────────────────────────────────────────────────────────

    // ─── Formatação HTML ──────────────────────────────────────────────────────────

    private function buildHtml(array $rows, array $parsed, string $versao): string
    {
        $ref   = htmlspecialchars($parsed['label'] . " ({$versao})", ENT_QUOTES, 'UTF-8');
        $html  = '';
        $currentChap = null;

        // Estilo inline para ajustar tamanho, altura e espaçamento do versículo
        $styleSup = 'font-size: 0.7em; vertical-align: baseline; position: relative; top: -0.3em; margin-right: 4px; color: #555; font-weight: bold; line-height: 0;';

        foreach ($rows as $r) {
            $chap  = (int)$r['chapter'];
            $vsNum = (int)$r['verse'];
            $text  = htmlspecialchars($r['text'], ENT_QUOTES, 'UTF-8');

            if ($currentChap !== null) {
                if ($chap !== $currentChap) {
                    $html .= '<br><br>';
                } else {
                    $html .= '<br>';
                }
            }
            $currentChap = $chap;

            // Insere a tag com o estilo e remove o espaço fixo entre o número e o texto
            $html .= "<sup class=\"vnum\" style=\"{$styleSup}\">{$vsNum}</sup>{$text}";
        }

        return <<<HTML
        <h2 class="bible-ref"><strong>{$ref}</strong></h2>
        <blockquote class="bible-passage">
        <p class="bible-text" style="line-height: 1.6; font-style: italic;">{$html}</p>
        </blockquote>
        HTML;
    }
}
