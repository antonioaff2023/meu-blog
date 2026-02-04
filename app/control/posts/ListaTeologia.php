<?php
//Criar um arquivo posts.php na pasta app/control/posts

use Adianti\Database\TTransaction;
use Adianti\Widget\Wrapper\TDBCombo;
use App\Traits\MinhaTrait;
use Adianti\Base\TStandardList;


class ListaTeologia extends TStandardList
{
    protected $form;     // registration form
    protected $datagrid; // listing
    protected $pageNavigation;
    protected $formgrid;
    protected $deleteButton;
    protected $transformCallback;

    use Adianti\Base\AdiantiStandardFormTrait; // Standard form methods
    use MinhaTrait;
    public function __construct()
    {
        parent::__construct();

        parent::setDatabase('sample');            // Define a base de dados
        parent::setActiveRecord('Postagens');   // Define o registro ativo
        parent::setDefaultOrder('id_subtipo', 'asc');         // Define a ordem padrão
        parent::setLimit(7);                    // Define o limite de registros por página


        $this->form = new BootstrapFormBuilder('form_search_Teologia');

        $tipo = 3; //Trabalha com o tipo Teologia



        //Pega o tipo na tabela de tipos
        $conn = TTransaction::open('sample');
        $tipoconteudo = new TipoConteudo($tipo);
        $tipoconteudo->id = $tipoconteudo->id;

        TTransaction::close();

        $tituloform = 'Pesquisa - ' . $tipoconteudo->descricao;
        $titulopanel = 'Teologia';

        //Cria os campos do formulário
        $id = new THidden('id');
        $titulo = new TEntry('titulo');
        $subtitulo = new TEntry('subtitulo');
        $id_tipo = new THidden('id_tipo');

        $filter = new TCriteria;
        $filter->add(new TFilter('id_tipo', '=', $tipoconteudo->id));

        $id_subtipo = new TDBCombo('id_subtipo', 'sample', 'SubTipoConteudo', 'id', 'descricao', 'id', $filter);

        //Filtra automaticamente os subtipos de acordo com o tipo Teologia 
        $id_subtipo->setChangeAction(new TAction([$this, 'onReload'], ['static' => 1, 'id_tipo' => $tipo]));
        


        $passagem = new TEntry('passagem');

        //Data postagem no formato dd/mm/yyyy
        $data_postagem = new TDate('data_postagem');
        $data_postagem->setMask('dd/mm/yyyy');
        $data_postagem->setDatabaseMask('yyyy-mm-dd');

        //Cria os labels dos campos
        $titulo_lbl = new TLabel('Título, subtítulo ou tag para pesquisa:');

        $id_tipo_lbl = new TLabel('Tipo:');
        $id_subtipo_lbl = new TLabel('Subtipo:');
        $passagem_lbl = new TLabel('Passagem:');
        $data_postagem_lbl = new TLabel('Data:');




        //Titulo do formulário
        $this->form->setFormTitle($tituloform);



        $this->form->setFields([$titulo, $data_postagem, $id, $id_tipo, $id_subtipo, $passagem, $subtitulo]);

        //Cria div para linha única para os campos
        $dv_linha =  new TElement('div');
        $dv_linha->style = 'display: flex; width: 100%;';

        //Titulo
        $dv_titulo = new TElement('div');
        $dv_titulo->style = 'margin-right: 10px; margin-top: 10px; display: inline-block; width: 30%';
        $dv_titulo->add($titulo_lbl);
        $dv_titulo->add($titulo);

        //Subtipo
        $dv_subtipo = new TElement('div');
        $dv_subtipo->style = 'margin-right: 10px; margin-top: 10px; display: inline-block; width: 20%';
        $dv_subtipo->add($id_subtipo_lbl);
        $dv_subtipo->add($id_subtipo);


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
        $dv_linha->add($dv_subtipo);
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
        $btn = $this->form->addAction('Limpar', new TAction([$this, 'onLimpa']), 'fa:eraser black');
        $btn->class = 'btn btn-sm btn-danger';
        $btn->setLabel('Limpar');
        $btn->style = $estilo_btn;

        //Cria o botão de novo
        $btn = $this->form->addAction('Novo', new TAction(['FormMeusPosts', 'onEdit']), 'fa:plus black');
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
            if ($object->id_tipo == 3) {
                return "<span class='badge badge-success'>$value</span>";
            } else {
                return "<span class='badge badge-danger'>$value</span>";
            }
        });


        $col_titulo = new TDataGridColumn('titulo', 'Título', 'left', '30%');
        $col_subtitulo = new TDataGridColumn('subtitulo', 'Subtítulo', 'left', '20%');
        $col_passagem = new TDataGridColumn('passagem', 'Passagem', 'left', '10%');
        $col_tags = new TDataGridColumn('tags', 'Tags', 'left', '30%');

        //Adiciona as colunas ao data grid
        $this->datagrid->addColumn($col_id);
        $this->datagrid->addColumn($col_passagem);
        $this->datagrid->addColumn($col_titulo);
        $this->datagrid->addColumn($col_subtitulo);
        $this->datagrid->addColumn($col_tags);

        //Cria as ações das colunas do data grid
        $order_titulo = new TAction(array($this, 'onReload'), ['id_tipo' => $tipo]);
        $order_titulo->setParameter('order', 'titulo');
        $col_titulo->setAction($order_titulo);
        $order_subtitulo = new TAction(array($this, 'onReload'), ['id_tipo' => $tipo]);
        $order_subtitulo->setParameter('order', 'subtitulo');
        $col_subtitulo->setAction($order_subtitulo);


        //Cria as ações do data grid
        $action1 = new TDataGridAction(['FormMeusPosts', 'onEdit'],   ['id' => '{id}']);
        $action2 = new TDataGridAction([$this, 'onDelete'], ['id' => '{id}']);

        $this->datagrid->addAction($action1, 'Editar', 'fa:pen-to-square blue');
        $this->datagrid->addAction($action2, 'Apagar', 'far:trash-alt red');



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
        // $container->add($panel);

        parent::add($container);
    }

    //Cria método onClear para limpar os campos do formulário
    public function onClear()
    {
        $this->form->clear(TRUE);
        $this->onReload();
    }


    //

    // Cria um método onReload para recarregar o datagrid sempre com o tipo Teologia e permintindo filtro dinâmico dos campos do formulário
    public function  onReload($param = null)
    {
        try {
            TTransaction::open('sample'); // Open a transaction

            //Lê os campos do formulário
            $data = $this->form->getData();
            $this->form->setData($data);


            $criteria = new TCriteria;
            $criteria->add(new TFilter('id_tipo', '=', 3)); // Filter by type Teologia

            if (isset($param['order'])) {
                $criteria->setProperty('order', $param['order']);
            } else {
                $criteria->setProperty('order', 'id_subtipo');
            }

            if (isset($data->id_subtipo) && $data->id_subtipo) {
                $criteria->add(new TFilter('id_subtipo', '=', $data->id_subtipo));
            }

            if (isset($param['titulo']) && $param['titulo']) {
                $criteria->add(new TFilter('titulo', 'like', "%{$param['titulo']}%"));
            }

            if (isset($param['subtitulo']) && $param['subtitulo']) {
                $criteria->add(new TFilter('subtitulo', 'like', "%{$param['subtitulo']}%"));
            }

            if (isset($param['passagem']) && $param['passagem']) {
                $criteria->add(new TFilter('passagem', 'like', "%{$param['passagem']}%"));
            }

            if (isset($param['data_postagem']) && $param['data_postagem']) {
                $criteria->add(new TFilter('data_postagem', '=', "{$param['data_postagem']}"));
            }

            // Load the datagrid with the records
            $repository = new TRepository('Postagens');
            $objects = $repository->load($criteria, FALSE);

            // Clear the datagrid
            $this->datagrid->clear();


            if ($objects) {
                foreach ($objects as $object) {
                    // Add the object to the datagrid
                    $this->datagrid->addItem($object);
                }
            }

            // Close the transaction
            TTransaction::close();
        } catch (Exception $e) {
            // Show an error message in case of exception
            new TMessage('error', 'Erro: ' . $e->getMessage());
            TTransaction::rollback(); // Rollback the transaction

        }
    }


    public function onSearch($param = null)
    {
        $data = $this->form->getData(); // pega os dados do form
        $this->form->setData($data); // mantém os dados no form

        $params = (array) $data; // transforma objeto em array para passar como parâmetro
        $params['id_tipo'] = 3; // garante o filtro do tipo Teologia

        $this->onReload($params);
    }
}
