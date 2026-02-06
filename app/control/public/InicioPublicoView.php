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
}
