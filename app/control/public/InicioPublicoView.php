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

    use app\MeuTrait;


    public function __construct()
    {
        parent::__construct();

        $sermões = $this->UltimosSermoes(1);
        $estudos = $this->UltimosSermoes(2);
        
        $estilo = "style='font-size: 1.3em; font-weight: bold; color: black; border-bottom: 2px solid black;'";

        $div_principal = new TElement('div');
        $div_principal->style = 'text-align: left; display: flex; gap: 20px; margin-top: -4%;';

        $div_sermoes = new TElement('div');
        $div_sermoes->style = 'flex: 1;';
        $div_sermoes->add("<span $estilo>ÚLTIMOS SERMÕES</span>");
        $div_sermoes->add($sermões);

        $div_estudos = new TElement('div');
        $div_estudos->style = 'flex: 1;';
        $div_estudos->add("<span $estilo>RESUMOS DOS ÚLTIMOS ESTUDOS - </span>");
        $div_estudos->add($estudos);

        $div_principal->add($div_sermoes);
        $div_principal->add($div_estudos);

        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 70%; margin: auto; padding: 10px; ';


        $container->add($div_principal);

        parent::add($container);
    }
}
