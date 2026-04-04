<?php
/**
 * ApiPassagem.php
 * Coloque em: app/control/posts/
 *
 * Chamado via: index.php?class=ApiPassagem&method=handle&passagem=Jo+3.1-21&versao=NAA
 * Retorna JSON direto.
 */

class ApiPassagem extends TPage
{
    public function __construct($param)
    {
        $this->handle($param);
    }

    public function handle($param)
    {
        ob_clean();
        header('Content-Type: application/json; charset=utf-8');

        $passagem = trim($param['passagem'] ?? $_REQUEST['passagem'] ?? '');
        $versao   = strtoupper(trim($param['versao'] ?? $_REQUEST['versao'] ?? 'NAA'));

        if (!$passagem) {
            echo json_encode([
                'ok'    => false,
                'error' => 'Parâmetro passagem não informado.',
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        try {
            require_once __DIR__ . '/BiblePassageResolver.php';
            $dbDir    = dirname(__DIR__, 2) . '/database/biblias';
            $resolver = new BiblePassageResolver($dbDir);

            $html   = $resolver->resolveHtml($passagem, $versao);
            $parsed = $resolver->parseRange($passagem);

            echo json_encode([
                'ok'    => true,
                'html'  => $html,
                'label' => $parsed['label'] ?? $passagem,
            ], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'ok'    => false,
                'error' => $e->getMessage(),
            ], JSON_UNESCAPED_UNICODE);
        }

        exit;
    }
}