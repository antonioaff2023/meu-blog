<?php
/**
 * InicioPublicoView
 *
 * @version    8.4
 * @package    control
 * @subpackage public
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    https://adiantiframework.com.br/license-template
 */
class InicioPublicoView extends TPage
{
    public function __construct()
    {
        parent::__construct();
        
        $html = new THtmlRenderer('app/resources/public.html');

        // replace the main section variables
        $html->enableSection('main', array());
        
        $panel = new TPanelGroup('Início Público');
        $panel->add($html);
        
        // add the template to the page
        parent::add( $panel );
    }
}
