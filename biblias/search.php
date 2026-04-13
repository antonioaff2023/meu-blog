<?php
/**
 * search.php — API JSON para busca de versículos
 * Consumida via AJAX pela interface index.php
 *
 * Parâmetros GET:
 *   mode      = keyword | reference | book
 *   q         = palavra-chave (modo keyword)
 *   book      = número do livro (1-66)
 *   chapter   = número do capítulo
 *   verse     = número do versículo (opcional, modo reference)
 *   versions  = lista separada por vírgula (ex: ACF,NVI)
 *   page      = número da página (padrão 1)
 *   per_page  = resultados por página (padrão 50, max 200)
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/BibleDB.php';

header('Content-Type: application/json; charset=utf-8');

// ─── Sanitização de entrada ───────────────────────────────────────────────────

$mode    = $_GET['mode']    ?? 'keyword';
$q       = trim($_GET['q'] ?? '');
$bookNum = (int)($_GET['book']    ?? 0);
$chapter = (int)($_GET['chapter'] ?? 0);
$verse   = (int)($_GET['verse']   ?? 0);
$page    = max(1, (int)($_GET['page']    ?? 1));
$perPage = min(200, max(1, (int)($_GET['per_page'] ?? 50)));

// Parâmetros de intervalo (modo range)
$startChapter = (int)($_GET['start_chapter'] ?? 0);
$startVerse   = (int)($_GET['start_verse']   ?? 1);
$endChapter   = (int)($_GET['end_chapter']   ?? 0);
$endVerse     = (int)($_GET['end_verse']     ?? 999);

$versionsRaw = trim($_GET['versions'] ?? '');
$versions    = $versionsRaw !== ''
    ? array_filter(array_map('strtoupper', explode(',', $versionsRaw)))
    : [];

// Valida versões
$validVersions = array_keys(BIBLE_VERSIONS);
$versions = array_values(array_filter($versions, fn($v) => in_array($v, $validVersions)));

// ─── Execução ────────────────────────────────────────────────────────────────

try {
    $db = new BibleDB();

    switch ($mode) {
        // ── Busca por palavra-chave ──────────────────────────────────────────
        case 'keyword':
            if (mb_strlen($q) < 2) {
                jsonError('Digite pelo menos 2 caracteres para buscar.');
            }
            if (mb_strlen($q) > 150) {
                jsonError('Termo de busca muito longo (máx 150 caracteres).');
            }
            $offset = ($page - 1) * $perPage;
            $total  = $db->countByKeyword($q, $versions, $bookNum);
            $rows   = $db->searchByKeyword($q, $versions, $bookNum, $perPage, $offset);
            // Destaca termo no texto
            $rows = highlightKeyword($rows, $q);
            jsonSuccess([
                'mode'       => 'keyword',
                'query'      => $q,
                'total'      => $total,
                'page'       => $page,
                'per_page'   => $perPage,
                'total_pages'=> (int)ceil($total / $perPage),
                'results'    => $rows,
            ]);
            break;

        // ── Busca por referência ─────────────────────────────────────────────
        case 'reference':
            if ($bookNum < 1 || $bookNum > 66) jsonError('Livro inválido.');
            if ($chapter < 1)                  jsonError('Capítulo inválido.');
            $rows  = $db->searchByReference($bookNum, $chapter, $verse, $versions);
            $books = BIBLE_BOOKS;
            $bookName = $books[$bookNum]['name'] ?? "Livro $bookNum";
            $ref  = $verse > 0
                ? "$bookName $chapter:$verse"
                : "$bookName $chapter";
            jsonSuccess([
                'mode'      => 'reference',
                'reference' => $ref,
                'book_num'  => $bookNum,
                'chapter'   => $chapter,
                'verse'     => $verse,
                'total'     => count($rows),
                'results'   => $rows,
            ]);
            break;

        // ── Busca por intervalo de passágio (pode cruzar capítulos) ──────────
        case 'range':
            if ($bookNum < 1 || $bookNum > 66)        jsonError('Livro inválido.');
            if ($startChapter < 1 || $endChapter < 1) jsonError('Capítulo inválido.');
            if (
                $startChapter > $endChapter ||
                ($startChapter === $endChapter && $startVerse > $endVerse)
            ) jsonError('Intervalo inválido: o início deve ser anterior ao fim.');

            $rows  = $db->searchByRange(
                $bookNum, $startChapter, $startVerse, $endChapter, $endVerse, $versions
            );
            $books = BIBLE_BOOKS;
            $bName = $books[$bookNum]['name'] ?? "Livro $bookNum";
            // Monta rótulo legível da referência
            if ($startChapter === $endChapter) {
                $rangeLabel = "$bName {$startChapter}:{$startVerse}–{$endVerse}";
            } else {
                $rangeLabel = "$bName {$startChapter}:{$startVerse}–{$endChapter}:{$endVerse}";
            }
            jsonSuccess([
                'mode'        => 'range',
                'reference'   => $rangeLabel,
                'book_num'    => $bookNum,
                'start_chapter'=> $startChapter,
                'start_verse' => $startVerse,
                'end_chapter' => $endChapter,
                'end_verse'   => $endVerse,
                'total'       => count($rows),
                'results'     => $rows,
            ]);
            break;

        // ── Lista livros/capítulos ────────────────────────────────────────────
        case 'book':
            if ($bookNum < 1 || $bookNum > 66) jsonError('Livro inválido.');
            $chapters = $db->getChaptersForBook($bookNum);
            $books    = BIBLE_BOOKS;
            jsonSuccess([
                'mode'      => 'book',
                'book_num'  => $bookNum,
                'book_name' => $books[$bookNum]['name'] ?? "Livro $bookNum",
                'chapters'  => $chapters,
            ]);
            break;

        // ── Estatísticas ──────────────────────────────────────────────────────
        case 'stats':
            jsonSuccess($db->getStats());
            break;

        // ── Versões disponíveis ───────────────────────────────────────────────
        case 'versions':
            jsonSuccess(['versions' => $db->getAvailableVersions()]);
            break;

        default:
            jsonError("Modo '$mode' inválido. Use: keyword, reference, book, stats, versions.");
    }

} catch (Exception $e) {
    jsonError('Erro interno: ' . $e->getMessage(), 500);
}

// ─── Auxiliares ───────────────────────────────────────────────────────────────

function jsonSuccess(array $data): never
{
    echo json_encode(['ok' => true, 'data' => $data], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function jsonError(string $msg, int $code = 400): never
{
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

function highlightKeyword(array $rows, string $term): array
{
    $escaped = htmlspecialchars($term, ENT_QUOTES, 'UTF-8');
    foreach ($rows as &$row) {
        $row['text_highlighted'] = preg_replace(
            '/(' . preg_quote(htmlspecialchars($row['text'], ENT_QUOTES, 'UTF-8'), '/') . ')/iu',
            '$1',
            htmlspecialchars($row['text'], ENT_QUOTES, 'UTF-8')
        );
        // Abordagem direta — preserva HTML
        $row['text_highlighted'] = str_ireplace(
            $term,
            "<mark>$term</mark>",
            $row['text']
        );
    }
    return $rows;
}
