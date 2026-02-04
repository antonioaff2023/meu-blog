<?php

use Adianti\Database\TTransaction;

class SermoesViews 
{
    public static function render($codigo = null)
    {
 
        if ($codigo == null) 
        {
            $codigo =  1; // Sermões
        } 

        $acao = ($codigo == 1) ? 'Pregado' : 'Ministrado';

        TTransaction::open('sample');
        //Lista os últimos 3 sermões
        $sermoes = Postagens::where('id_tipo', '=', $codigo)
            ->where('data_postagem', '<', date('Y-m-d'))
            ->orderBy('data_postagem', 'desc')
            ->take(4)
            ->load();
        TTransaction::close();

        foreach ($sermoes as $sermao) {

            $postagem = (new DateTime($sermao->data_postagem))->format('d/m/Y');
            // Converte a data em extenso
            $formatter = new IntlDateFormatter('pt_BR', IntlDateFormatter::FULL, IntlDateFormatter::NONE, null, null, "EEEE, dd 'de' MMMM 'de' yyyy");
            $data_extenso = $formatter->format(new DateTime($sermao->data_postagem));

            $sermao->passagem = $sermao->passagem;
            //Título em caixa alta
            $titulo = strtoupper($sermao->titulo);

            $html = <<<HTML
                <div class="postagem">
                    <h3>
                        <a href="/index.php?class=SermoesPublicosView&id={$sermao->id}"
                        generator="adianti"
                        target="modal">{$titulo}
                        </a>
                    </h3>
                    
                    <p><em><strong>Texto:</strong> {$sermao->passagem}</em></p>
                    <p class='data-postagem'><em>{$acao} - {$data_extenso}</em></p>
                    
                </div>
            HTML;

        }

        return $html;
    }
}
