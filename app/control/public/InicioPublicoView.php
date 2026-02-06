<?php

/**
 * InicioPublicoView
 *
 * @version    8.4
 * @package    control
 * @subpackage public
 * @author     Antonio Affonso
 * 
 */
class InicioPublicoView extends TPage
{

    use app\Meutrait;

    public function __construct()
    {
        parent::__construct();

        $sermões = $this->UltimosSermoes(1);
        $estudos = $this->UltimosSermoes(2);

        $estilo = "style='font-size: 1.3em; font-weight: bold; color: black; border-bottom: 2px solid black;'";
        $panel = new TPanelGroup('');
        $panel->style = 'margin-top: -3%; background-color: transparent; box-shadow: none; border: none;';
        $table = new TTable;
        $table->style = 'border-collapse:collapse';
        $table->width = '100%';
        $table->addRowSet("<span $estilo>ÚLTIMOS SERMÕES</span>", "<span $estilo>RESUMOS DOS ÚLTIMOS ESTUDOS</span>");
        $table->addRowSet($sermões, $estudos);
        $panel->add($table);


        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 70%; margin: auto;margin-left: 20%';


        $container->add($panel);

        parent::add($container);
    }

    public function onGeraPDF($param)
    {

        try {


            TTransaction::open('sample');
            $GerandoPDF = Postagens::find($param['id']);
            TTransaction::close();

            // processa um template de página em HTML
            $html = <<<EOF
                    <body class="corpo-sermao">
                        <div class="sermao-container">
                            <div class="sermao-titulo">
                                <h2>{$GerandoPDF->titulo}</h2>
                            </div>

                            <hr>
                            <div class="sermao-texto">
                                {$GerandoPDF->conteudo}
                            </div>
                        </div>
                    </body>

            EOF;    

            

            // converte o modelo HTML em PDF
            $dompdf = new \Dompdf\Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();



            // Exclui os arquivos PDF antigos
            $files = glob('app/output/*.pdf');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }

            $current_datetime = date('Y-m-d_H-i-s');

            $file = 'app/output/export_' . $current_datetime . '.pdf';

            // gravar e abrir arquivo
            file_put_contents($file, $dompdf->output());


            $mostra = $this->retornaModal($file);

            $container = new TVBox;
            $container->style = 'width: 100%';
            $container->add($mostra);
            parent::add($container);
        } catch (Exception $e) {
            new TMessage('error', 'Erro ao gerar PDF: ' . $e->getMessage());
        }
    }

    function retornaModal($file)
    {

        // processa um template de página em HTML
        $mostra = new THtmlRenderer('app/resources/modal_pdf.html');

        $replaces = [];
        $replaces['file'] = $file;
        $mostra->enableSection('main', $replaces);


        return $mostra;
    }
}
