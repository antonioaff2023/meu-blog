<?php
//Criar um arquivo posts.php na pasta app/control/posts

use Adianti\Control\TPage;

class ListaEstudos extends TPage
{

    public function __construct()
    {
        parent::__construct();
        // create the HTML Renderer
        $this->html = new THtmlRenderer('app/resources/jumbotron.html');
        
        $ini = AdiantiApplicationConfig::get();
        
        $replaces = ['title' => 'Lista de Estudos',
                     'content' => $ini['general']['welcome_message'] ?? ''];
        
        // replace the main section variables
        $this->html->enableSection('main', $replaces);
        
        parent::add( $this->html );

    }
}
