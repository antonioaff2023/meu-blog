<?php
/**
 * SingleWindowView
 *
 * @version    1.0
 * @package    samples
 * @subpackage tutor
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    https://adiantiframework.com.br/license-tutor
 */
class GeraPDF extends TWindow
{
    /**
     * Constructor method
     */
    public function __construct($param)
    {
        parent::__construct();
        parent::setSize(0.8, null);
        parent::removePadding();
        parent::removeTitleBar();
        parent::disableEscape();
        
        // with: 500, height: automatic
        parent::setSize(0.6, null); // use 0.6, 0.4 (for relative sizes 60%, 40%)
        
        try {


            TTransaction::open('sample');
            $GerandoPDF = Postagens::find($param['id']);
            TTransaction::close();

            //Abortar se não encontrar o registro
            if (!$GerandoPDF) {
                throw new Exception('Registro não encontrado');
            }

            // Gerar o conteúdo HTML para o PDF
            $html = file_get_contents("app/resources/sermao.html");
            $html = str_replace('{titulo}', $GerandoPDF->titulo, $html);
            $html = str_replace('{passagem}', nl2br($GerandoPDF->passagem), $html);
            $html = str_replace('{conteudo}', nl2br($GerandoPDF->conteudo), $html);


            // Converte o modelo HTML em PDF
            $dompdf = new \Dompdf\Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            //Exclui arquivos antigos
            $files = glob('app/output/*.pdf');
            $current_time = time();
            foreach ($files as $file) {
                if (is_file($file) && ($current_time - filemtime($file)) > 3600) { // Exclui arquivos com mais de 1 hora
                    unlink($file);
                }
            }

            $current_datetime = date('Y-m-d_H-i-s');
            $file = 'app/output/export_' . $current_datetime . '.pdf';

            // Gravar e abrir arquivo
            file_put_contents($file, $dompdf->output());

            $window = TWindow::create('IMPRIME LAUDO', 0.8, 0.8);
            $window->setModal(true);

            $object = new TElement('object');
            $object->data  = $file;
            $object->type  = 'application/pdf';
            $object->style = "width:100%; height:100%";
            $object->add(
                'O navegador não suporta PDF. <a target="_blank" href="' . $file . '">Clique aqui para baixar</a>'
            );

            $window->add($object);
            $window->show();
        } catch (Exception $e) {
            new TMessage('error', 'Erro ao gerar PDF: ' . $e->getMessage());
        }        
    }
}
