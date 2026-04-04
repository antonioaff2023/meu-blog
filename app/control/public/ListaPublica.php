<?php

/**
 * ListaPublica
 *
 * @version    8.4
 * @package    control
 * @subpackage public
 * @author     Antonio Affonso
 * 
 */
class ListaPublica extends TStandardList
{

    use app\MeuTrait;

    public function __construct()
    {
        parent::__construct();

        parent::setDatabase('sample');            // Define a base de dados
        parent::setActiveRecord('Postagens');   // Define o registro ativo
        parent::setDefaultOrder('data_postagem', 'desc');         // Define a ordem padrão


        parent::setLimit(7);                    // Define o limite de registros por página


        $div_principal = new TElement('div');
        $div_principal->style = 'display: flex; flex-wrap: wrap; justify-content: space-between; margin-top: -3.5em; gap: 20px;';
        $div_principal->add('Teste');

        

        // vertical box container
        $container = new TVBox;
        $container->style = 'margin: 0 auto; padding: 20px; max-width: 1200px;';


        $container->add($div_principal);

        parent::add($container);
    }
}
