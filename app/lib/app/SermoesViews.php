<?php

use Adianti\Database\TTransaction;

class SermoesViews 
{
    public static function render($codigo = null)
    {
        $html = "<style>
                    * { margin: 0; padding: 0; }
                    h3 {
                        font-size: 1.2em;
                        margin-bottom: 5px;
                        color: #ffffff;
                    }

                    .texto-devocional {
                        font-size: 1em;
                        margin-bottom: 10px;
                        color: #ffffff;
                        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
                    }

                    p {
                        margin: 0 0 0 0;
                        padding: 0;
                    }

                    @media (max-width: 768px) {
                        h3 {
                            font-size: 1em;
                        }
                        .texto-devocional {
                            font-size: 0.7em;
                        }
                    }

                    @media (max-width: 480px) {
                        h3 {
                            font-size: 0.9em;
                        }
                        .texto-devocional {
                            font-size: 0.5em;
                        }
                        p {
                            font-size: 0.85em;
                        }
                    }
                </style>";

        
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

            $html .= <<<HTML
                <div class="postagem">
                    <h3>
                        <a href="/index.php?class=SermoesPublicosView&id={$sermao->id}"
                        generator="adianti"
                        target="modal">{$titulo}
                        </a>
                    </h3>
                    <p><strong>Texto:</strong> {$sermao->passagem}</p>
                    <p class='data-postagem'>'{$acao}' - {$data_extenso}</p>
                </div>
            HTML;

        }

        return $html;
    }
}
