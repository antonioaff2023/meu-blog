<?php

use Adianti\Control\TPage;
use Adianti\Control\TWindow;
use Adianti\Database\TTransaction;

class SermoesPublicosView extends TWindow
{
    public function __construct()
    {

        parent::__construct();
        parent::removePadding();
        parent::removeTitleBar();
        parent::disableEscape();
        
        // with: 500, height: automatic
        parent::setSize(0.9, 0.9); // use 0.6, 0.4 (for relative sizes 60%, 40%)

        TTransaction::open('sample');
        //Abre a postagem pelo ID
        $id = isset($_GET['id']) ? $_GET['id'] : null;
        $sermao = Postagens::find($id);
        TTransaction::close();


        $html = file_get_contents("app/resources/sermao.html");
        $html = str_replace('{titulo}', $sermao->titulo, $html);
        $html = str_replace('{passagem}', nl2br($sermao->passagem), $html);
        $html = str_replace('{conteudo}', nl2br($sermao->conteudo), $html);

        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add($html);

        parent::add($container);
    }

    public static function onEdit($param)
    {
        $id = $param['id'];
        AdiantiCoreApplication::loadPage('SermoesPublicosView', '', ['id' => $id]);
    }
}
