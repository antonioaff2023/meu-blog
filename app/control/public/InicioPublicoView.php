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

    use app\MeuTrait;

    public function __construct()
    {
        parent::__construct();

        $sermões = $this->UltimosSermoes(1);
        $estudos = $this->UltimosSermoes(2);

        $estilo = "style='font-size: clamp(.7em, 2.5vw, 1.4em); font-weight: bold; color: black; border-bottom: 2px solid black;'";
        $panel = new TPanelGroup('');
        $panel->style = 'background-color: transparent; box-shadow: none; border: none;';
        $table = new TTable;
        $table->style = 'border-collapse:collapse; border-spacing:0; margin-top: -3.5em;';
        $table->width = '100%';
        $table->addRowSet("<span $estilo>ÚLTIMOS SERMÕES</span>", "<span $estilo>RESUMOS DOS ÚLTIMOS ESTUDOS</span>");
        $table->addRowSet($sermões, $estudos);
        $panel->add($table);

        // Alterar para div
        $div_principal = new TElement('div');
        $div_principal->style = 'display: flex; flex-wrap: wrap; justify-content: space-between; margin-top: -3.5em; gap: 20px;';

        $div_sermoes = new TElement('div');
        $div_sermoes->style = 'flex: 1; min-width: 300px;';
        $div_sermoes->add("<div style='font-size: clamp(.7em, 2.5vw, 1.4em); font-weight: bold; color: black; margin-bottom: 10px;'><u>ÚLTIMOS SERMÕES</u></div>");
        $div_sermoes->add($sermões);

        $div_estudos = new TElement('div');
        $div_estudos->style = 'flex: 1; min-width: 300px;';
        $div_estudos->add("<div style='font-size: clamp(.7em, 2.5vw, 1.4em); font-weight: bold; color: black; margin-bottom: 10px;'><u>RESUMOS DOS ÚLTIMOS ESTUDOS</u></div>");
        $div_estudos->add($estudos);

        $div_principal->add($div_sermoes);
        $div_principal->add($div_estudos);


        // vertical box container
        $container = new TVBox;
        $container->style = 'margin: 0 auto; padding: 20px; max-width: 1200px;';       


        $container->add($div_principal);

        parent::add($container);
    }


}
