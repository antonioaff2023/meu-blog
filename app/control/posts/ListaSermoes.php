<?php

use Adianti\Database\TExpression;
use Adianti\Widget\Form\THidden;
use Adianti\Widget\Form\THtmlEditor;
use Adianti\Widget\Form\TText;
use App\Traits\MinhaTrait;

/**
 * ListaSermoes
 *
 * @version    8.1
 * @package    control
 * @subpackage admin
 * @author     Antonio Affonso
 * @copyright  Copyright (c) 2025, Adianti Framework
 * @license    https://adiantiframework.com.br/license-template
 */
class ListaSermoes extends TStandardList
{
    protected $form;     // registration form
    protected $datagrid; // listing
    protected $pageNavigation;
    protected $formgrid;
    protected $deleteButton;
    protected $transformCallback;

    use MinhaTrait;
    /**
     * Page constructor
     */
    public function __construct($param = null)
    {
        parent::__construct();

        parent::setDatabase('sample');            // Define a base de dados
        parent::setActiveRecord('Postagens');   // Define o registro ativo
        parent::setDefaultOrder('data_postagem', 'desc');         // Define a ordem padrão
        parent::setLimit(7);                    // Define o limite de registros por página


        //Cria o formulário
        $this->form = new BootstrapFormBuilder('form_search_Sermoes');


        //Cria os campos do formulário
        $id = new THidden('id');
        $titulo = new TEntry('titulo');
        $id_tipo = new THidden('id_tipo');

        if (!empty($param['id_tipo'])) {
            $tipo = $param['id_tipo'];
        } else {
            $tipo = 1;
        }

        if ($tipo == 1) {
            $tituloform = 'Pesquisa de Sermões';
            $titulopanel = 'Sermões';
        } else if ($tipo == 2) {
            $tituloform = 'Pesquisa de Estudos';
            $titulopanel = 'Estudos';
        } else if ($tipo == 4) {
            $tituloform = 'Pesquisa de Devocionais';
            $titulopanel = 'Devocionais';
        }

        $this->form->setFormTitle($tituloform);

        //Data postagem no formato dd/mm/yyyy
        $data_postagem = new TDate('data_postagem');
        $data_postagem->setMask('dd/mm/yyyy');
        $data_postagem->setDatabaseMask('yyyy-mm-dd');


        $passagem = new TEntry('passagem');


        //Cria os labels dos campos
        $titulo_lbl = new TLabel('Pesquisar por título, subtítulo ou tags');
        $data_postagem_lbl = new TLabel('Data');
        $passagem_lbl = new TLabel('Passagem');

        $this->form->setFields([$titulo, $data_postagem, $passagem, $id, $id_tipo]);

        //Cria div para linha única para os campos
        $dv_linha =  new TElement('div');
        $dv_linha->style = 'display: flex; width: 100%;';

        //Titulo
        $dv_titulo = new TElement('div');
        $dv_titulo->style = 'margin-right: 10px; margin-top: 10px; display: inline-block; width: 30%;';
        $dv_titulo->add($titulo_lbl);
        $dv_titulo->add($titulo);

        //Data
        $dv_data = new TElement('div');
        $dv_data->style = 'margin-right: 10px; margin-top: 10px; display: inline-block; width: 10%';
        $dv_data->add($data_postagem_lbl);
        $dv_data->add($data_postagem);



        //Passagem
        $dv_passagem = new TElement('div');
        $dv_passagem->style = 'margin-right: 10px; margin-top: 10px; display: inline-block; width: 10%';
        $dv_passagem->add($passagem_lbl);
        $dv_passagem->add($passagem);

        //Adiciona os campos na div
        $dv_linha->add($dv_titulo);
        $dv_linha->add($dv_data);
        $dv_linha->add($dv_passagem);

        //Adiciona a div ao formulário  
        $this->form->addFields([$dv_linha]);

        $estilo_btn = 'color: black; width: 100px;';
        //Cria os botões do formulário
        $btn = $this->form->addAction('Pesquisar', new TAction([$this, 'onReload'], ['id_tipo' => $tipo]), 'fa:search black');
        $btn->class = 'btn btn-sm btn-primary';
        $btn->setLabel('Pesquisar');
        $btn->style = $estilo_btn;

        //Cria o botão de limpar
        $btn = $this->form->addAction('Limpar', new TAction([$this, 'onLimpa'], ['id_tipo' => $tipo]), 'fa:eraser black');
        $btn->class = 'btn btn-sm btn-danger';
        $btn->setLabel('Limpar');
        $btn->style = $estilo_btn;

        //Cria o botão de novo
        $btn = $this->form->addAction('Novo', new TAction(['FormMeusPosts', 'onEdit'], ['tipo' => $tipo]), 'fa:plus black');
        $btn->class = 'btn btn-sm btn-success';
        $btn->setLabel('Novo');
        $btn->style = $estilo_btn;

        //Cria um data grid
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = 'width: 100%';
        $this->datagrid->setHeight(320);

        //Cria as colunas do data grid
        $col_id = new TDataGridColumn('id', 'ID', 'center', 50);
        //Coloque a coluna id invisível
        $col_id->setVisibility(false);
        $col_id->setTransformer(function ($value, $object, $row) {
            if ($object->id_tipo == 1) {
                return "<span class='badge badge-success'>$value</span>";
            } else {
                return "<span class='badge badge-danger'>$value</span>";
            }
        });


        if ($tipo == 2) {
            $col_id_serie = new TDataGridColumn('serie->nome', 'Série', 'left', '15%');
            $this->datagrid->addColumn($col_id_serie);
        }

        $col_titulo = new TDataGridColumn('titulo', 'Título', 'left', '25%');
        $col_titulo->setTransformer(function ($value) {
            return strtoupper($value);
        });
        $col_subtitulo = new TDataGridColumn('subtitulo', 'Subtítulo', 'left', '20%');
        $col_passagem = new TDataGridColumn('passagem', 'Passagem', 'left', '10%');
        $col_data_postagem = new TDataGridColumn('data_postagem', 'Data', 'center', '10%');
        //Exibe a data no formato dd/mm/yyyy
        $col_data_postagem->setTransformer(function ($value) {
            if ($value) {
                $date = new DateTime($value);
                $dayOfWeek = $date->format('w'); // 0 (domingo) a 6 (sábado)
                $formattedDate = $date->format('d/m/Y');
                
                if ($dayOfWeek == 0 ) { // Domingo ou Sábado
                    return "<span style='color: red;'>{$formattedDate}</span>";
                }
                else if ($dayOfWeek == 6 ) {
                    return "<span style='color: blue;'>{$formattedDate}</span>";
                }
                 else {
                    return $formattedDate;
                }
            }
            return '';
        });
        $col_tags = new TDataGridColumn('tags', 'Tags', 'left', '30%');

        //Adiciona as colunas ao data grid
        $this->datagrid->addColumn($col_id);
        $this->datagrid->addColumn($col_passagem);
        $this->datagrid->addColumn($col_titulo);
        $this->datagrid->addColumn($col_subtitulo);
        $this->datagrid->addColumn($col_data_postagem);
        $this->datagrid->addColumn($col_tags);

        //Cria as ações das colunas do data grid
        $order_titulo = new TAction(array($this, 'onReload'), ['id_tipo' => $tipo]);
        $order_titulo->setParameter('order', 'titulo');
        $col_titulo->setAction($order_titulo);
        $order_subtitulo = new TAction(array($this, 'onReload'), ['id_tipo' => $tipo]);
        $order_subtitulo->setParameter('order', 'subtitulo');
        $col_subtitulo->setAction($order_subtitulo);
        $order_data_postagem = new TAction(array($this, 'onReload'), ['id_tipo' => $tipo]);
        $order_data_postagem->setParameter('order', 'data_postagem');
        $col_data_postagem->setAction($order_data_postagem);

        //Cria as ações do data grid
        $action1 = new TDataGridAction(['FormMeusPosts', 'onEdit'],   ['id' => '{id}']);
        $action2 = new TDataGridAction([$this, 'onDelete'], ['id' => '{id}']);
        $action3 = new TDataGridAction(['SermoesPublicosView', 'onEdit'],   ['id' => '{id}']);

        $this->datagrid->addAction($action1, 'Editar', 'fa:pen-to-square blue');
        $this->datagrid->addAction($action2, 'Apagar', 'far:trash-alt red');
        $this->datagrid->addAction($action3, 'Visualizar', 'fa:eye green');




        $this->datagrid->createModel();
        //Cria a navegação do data grid
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->setAction(new TAction([$this, 'onReload'], ['id_tipo' => $tipo]));
        $this->pageNavigation->setWidth('100%');
        $this->pageNavigation->enableCounters();

        //Cria painel para apresentar o data grid
        $panel = new TPanelGroup($titulopanel);
        $panel->add($this->datagrid);
        $panel->addFooter($this->pageNavigation);

        // Box vertical
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add($this->form);
        $container->add($panel);

        parent::add($container);
    }
    /**
     * Method onReload
     * @param $param Request
     */
    public function onReload($param = null)
    {
        try {
            TTransaction::open('sample'); // Substitua pelo nome do seu banco, se necessário

            if (empty($param['offset'])) {
                $param['offset'] = 0;
            }


            // Cria um repositório para a tabela
            $repository = new TRepository('Postagens');
            $limit = 7;


            // Cria um critério de seleção
            $criteria = new TCriteria;

            if (!empty($param['passagem'])) {
                $passagem_filter = $param['passagem'];
                $criteria->add(new TFilter('passagem', 'LIKE', "%{$passagem_filter}%"));

            }

            if (!empty($param['data_postagem'])) {
                $data_filter = TDate::date2us($param['data_postagem']);
                $criteria->add(new TFilter('data_postagem', '=', $data_filter));
            }

            // Adiciona filtros com base no formulário
            if (!empty($param['titulo'])) {
                $filter_text = $param['titulo'];

                // Cria subcritério com filtros OR
                $sub_criteria = new TCriteria;
                $sub_criteria->add(new TFilter('titulo', 'LIKE', "%{$filter_text}%"), TExpression::OR_OPERATOR);
                $sub_criteria->add(new TFilter('subtitulo', 'LIKE', "%{$filter_text}%"), TExpression::OR_OPERATOR);
                $sub_criteria->add(new TFilter('tags', 'LIKE', "%{$filter_text}%"), TExpression::OR_OPERATOR);

                // Adiciona o subcritério ao critério principal
                $criteria->add($sub_criteria);
            }


            // Filtra o id_tipo
            if (!empty($param['id_tipo'])) {
                $tipo = $param['id_tipo'];
                $criteria->add(new TFilter('id_tipo', '=', $tipo));
            }



            // Ordenação
            if (!empty($param['order'])) {
                $criteria->setProperty('order', $param['order']);
                $criteria->setProperty('direction', 'asc');
            } else {
                $criteria->setProperty('order', 'data_postagem');
                $criteria->setProperty('direction', 'desc');
            }

            // Paginação
            $criteria->setProperty('limit', $limit);
            $criteria->setProperty('offset', $param['offset']);

            $objects = $repository->load($criteria, FALSE);

            $this->datagrid->clear();
            if ($objects) {
                foreach ($objects as $object) {
                    $this->datagrid->addItem($object);
                }
            }


            // Conta os registros e atualiza a paginação
            $criteria->resetProperties();
            $count = $repository->count($criteria);
            $page = isset($param['offset']) ? $param['offset'] / $limit + 1 : 1;

            // Atualiza paginação
            $this->pageNavigation->setCount($count);
            $this->pageNavigation->setLimit($limit);

            // Garante que 'offset' está no param para manter a posição
            if (!isset($param['offset'])) {
                $param['offset'] = 0;
            }


            $this->pageNavigation->setProperties($param);


            TTransaction::close();
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
}
