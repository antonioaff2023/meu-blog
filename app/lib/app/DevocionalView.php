<?php

use Adianti\Database\TTransaction;

class DevocionalView 
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
                            font-size: .80em !important;
                        }
                        .texto-devocional {
                            font-size: .80em !important;
                        }
                    }

                    @media (max-width: 480px) {
                        h3 {
                            font-size: .80em !important;
                        }
                        .texto-devocional {
                            font-size: .80em !important;
                        }
                        p {
                            font-size: .80em !important;
                        }
                    }
                </style>";

        $acao = 'Publicada';


        TTransaction::open('sample');
        //Lista os últimos 1 sermão
        $devocionais = Postagens::where('id_tipo', '=', 4)
            ->where('data_postagem', '=', date('Y-m-d'))
            ->orderBy('data_postagem', 'desc')
            ->take(1)
            ->load();
        TTransaction::close();

        foreach ($devocionais as $devocional) {

            $postagem = (new DateTime($devocional->data_postagem))->format('d/m/Y');
            // Converte a data em extenso
            $formatter = new IntlDateFormatter('pt_BR', IntlDateFormatter::FULL, IntlDateFormatter::NONE, null, null, "EEEE, dd 'de' MMMM 'de' yyyy");
            $data_extenso = $formatter->format(new DateTime($devocional->data_postagem));

            $devocional->passagem = $devocional->passagem;
            //Título em caixa alta
            $titulo = strtoupper($devocional->titulo);

            $html .= <<<HTML
                <div class="postagem">
                    
                    <h3>
                        <span class="texto-devocional" >MEDITAÇÃO DO DIA - </span>
                        <a class="texto-devocional" href="/index.php?class=SermoesPublicosView&id={$devocional->id}"
                        generator="adianti"
                        target="modal">{$titulo} ({$devocional->passagem})
                        </a>

                    </h3>
                </div>
            HTML;

        }

        return $html;
    }
}
