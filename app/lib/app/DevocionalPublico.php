<?php

/**
 * DevocionalPublico
 *
 * Retorna o HTML do devocional do dia formatado para o hero da página pública.
 * Usado em index.php via: $devocional = DevocionalPublico::render();
 * E injetado no template com: str_replace('{DEVOCIONAL}', $devocional, $content)
 */
class DevocionalPublico
{
    /**
     * Busca o devocional mais recente (tipo 4) e retorna o HTML do card do hero.
     */
    public static function render(): string
    {
        try {
            TTransaction::open('sample');
            $devocional = Postagens::where('id_tipo', '=', 4)
                ->where('data_postagem', '<=', date('Y-m-d'))
                ->orderBy('data_postagem', 'desc')
                ->take(1)
                ->load();
            TTransaction::close();

            if (empty($devocional)) {
                return '';
            }

            $post = $devocional[0];

            // Pega o primeiro parágrafo ou trecho do conteúdo como verso
            $conteudo = strip_tags($post->conteudo ?? '');
            $conteudo = trim($conteudo);
            // Limita a 180 caracteres para o hero
            if (mb_strlen($conteudo) > 180) {
                $conteudo = mb_substr($conteudo, 0, 180);
                // Corta na última palavra completa
                $conteudo = mb_substr($conteudo, 0, mb_strrpos($conteudo, ' ')) . '…';
            }

            $passagem = htmlspecialchars($post->passagem ?? '');
            $titulo   = htmlspecialchars($post->titulo ?? '');

            // Se o conteúdo está vazio, usa o título como fallback
            $verso = $conteudo ?: $titulo;

            return "
            <div class='devocional-card'>
                <div class='devocional-icone'>✦</div>
                <div>
                    <div class='devocional-label'>Devocional do dia</div>
                    <p class='devocional-verso'>" . htmlspecialchars($verso) . "</p>
                    " . ($passagem ? "<p class='devocional-ref'>{$passagem}</p>" : "") . "
                </div>
            </div>";

        } catch (Exception $e) {
            TTransaction::rollback();
            return '';
        }
    }
}