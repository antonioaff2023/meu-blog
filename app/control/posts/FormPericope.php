<?php
/* 
*
*
*/

//Cria formulário para a tabela tbl_tema
//Criar um arquivo FormTema.php na pasta app/control/posts

use Adianti\Control\TPage;
use App\Traits\MinhaTrait;

//Cria a classe FormPericope
class FormPericope extends TPage
{
    protected $form; // form
    protected $datagrid; // list
    protected $pageNavigation; // pagination
    protected $loaded; // loaded object
    protected $deleteAction;
    protected $editAction;
    protected $createAction;
    protected $onReload;

    use Adianti\Base\AdiantiStandardFormTrait; // Standard form methods
    use MinhaTrait;

    public function __construct()
    {
        parent::__construct();
        parent::setTargetContainer('adianti_right_panel');

        $this->setDatabase('pericope'); // defines the database
        $this->setActiveRecord('Pericopes');
        

        




        //Cria o formulário
        $this->form = new BootstrapFormBuilder('form_pericope');
        $this->form->setFormTitle('Cadastro de Perícope');

        //Cria os campos do formulário
        $id = new THidden('id');
        $titulo = new TEntry('titulo');
        $titulo->setSize('100%');
        $referencia = new TEntry('referencia');
        $referencia->setSize('100%');

        //Cria os labels dos campos

        $titulo_lbl = new TLabel('Título:');
        $referencia_lbl = new TLabel('Referência:');

        //Insere os campos no formulário
        $this->form->addFields([$id]);
        $row = $this->form->addFields([$titulo_lbl, $titulo], [$referencia_lbl, $referencia]);
        $row->layout = ['col-sm-9', 'col-sm-3'];

        // Botões
        $btn_pesquisa = $this->form->addAction('Pesquisar', new TAction([$this, 'onSearch']), 'fa:search');
        $btn_pesquisa->class = 'btn btn-sm btn-primary';

        $btn_limpar = $this->form->addAction('Limpar', new TAction([$this, 'onClear']), 'fa:eraser');
        $btn_limpar->class = 'btn btn-sm btn-secondary';


        $this->form->addHeaderActionLink(_t('Close'), new TAction([$this, 'onClose']), 'fa:times red');

        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = 'width: 100%';

        $col_titulo = new TDataGridColumn('titulo', 'Título', 'left', '75%');
        $col_referencia = new TDataGridColumn('referencia', 'Referência', 'left', '25%');

        $this->datagrid->addColumn($col_titulo);
        $this->datagrid->addColumn($col_referencia);

        $this->datagrid->createModel();

        // Paginação
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->setAction(new TAction([$this, 'onReload']));
        $this->pageNavigation->setWidth('100%');
        $this->pageNavigation->enableCounters();



        $this->onReload();

        $box = new TVBox;
        $box->style = 'width: 100%';
        $box->add($this->form);
        $box->add($this->datagrid);  // ← substitui o $this->datagrid direto
        $box->add($this->pageNavigation); // ← adiciona a navegação

        // Fecha o painel ao pressionar ESC
        TScript::create("
                        $(document).on('keydown', function(e) {
                            if (e.key === 'Escape') {
                                Template.closeRightPanel();
                            }
                        });
                    ");
        parent::add($box);
    }

    public static function onClose($param)
    {
        TScript::create("Template.closeRightPanel()");
    }

    public function onReload($param = null)
    {
        try {
            TTransaction::open('pericope');

            $repository = new TRepository('Pericopes');
            $limit = 10;

            $criteria = new TCriteria;

            $titulo     = TSession::getValue('FormPericope_titulo');
            $referencia = TSession::getValue('FormPericope_referencia');

            if (!empty($titulo)) {
                $criteria->add(new TFilter('titulo', 'like', "%{$titulo}%"));
            }

            if (!empty($referencia)) {
                $criteria->add(new TFilter('referencia', 'like', "%{$referencia}%"));
            }

            $criteria->setProperty('order', 'id');
            $criteria->setProperty('limit', $limit);
            $criteria->setProperties($param);

            $objects = $repository->load($criteria);

            $this->datagrid->clear();

            if ($objects) {
                foreach ($objects as $object) {
                    $this->datagrid->addItem($object);
                }
            }

            $criteria->resetProperties();
            $count = $repository->count($criteria);

            $this->pageNavigation->setCount($count);
            $this->pageNavigation->setProperties($param);
            $this->pageNavigation->setLimit($limit);

            TTransaction::close();
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    public function onSearch($param = null)
    {
        $data = $this->form->getData();
        $this->form->setData($data);
        TSession::setValue('FormPericope_titulo',    $data->titulo);
        TSession::setValue('FormPericope_referencia', $data->referencia);
        $this->onReload($param);
    }

    public function onClear($param = null)
    {
        TSession::setValue('FormPericope_titulo',    null);
        TSession::setValue('FormPericope_referencia', null);
        $this->form->clear();
        $this->onReload($param);
    }
}
