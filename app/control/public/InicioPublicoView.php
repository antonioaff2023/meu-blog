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

    use Adianti\Base\Meutrait;

    public function __construct()
    {
        parent::__construct();

              
        // Exibe os três últimos sermões publicados
        $div1 = new TElement('div');
        $div1->class = 'row';
        $titulo1 = new TElement('h2');
        $titulo1->class = 'titulo-ponto';
        $titulo1->add('Últimos Sermões');
        $sermões= $this->UltimosSermoes(1);        
        $div1->add($titulo1);
        $div1->add($sermões);


        //Exibe os três últimos estudos publicados
        $div2 = new TElement('div');
        $div2->class = 'row';
        $titulo2 = new TElement('h2');
        $titulo2->class = 'titulo-ponto';
        $titulo2->add('Resumos dos últimos estudos');
        $estudos= $this->UltimosSermoes(2);        
        $div2->add($titulo2);
        $div2->add($estudos);
        




        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 90%; margin: auto; padding: 10px;';
        
        // $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));


        $tela = $this->onDivideTela($div1, $div2, '40%', '40%');
        // $container->add($css);
        
        $container->add($tela);

        parent::add($container);
    }
}
