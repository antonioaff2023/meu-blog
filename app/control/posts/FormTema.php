<?php
/* 
*
*
*/

//Cria formulário para a tabela tbl_tema
//Criar um arquivo FormTema.php na pasta app/control/posts

use Adianti\Control\TPage;
use App\Traits\MinhaTrait;

//Cria a classe FormTema
class FormTema extends TPage
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

        $this->setDatabase('sample'); // defines the database
        $this->setActiveRecord('TemaConteudo');

        //Cria o formulário
        $this->form = new BootstrapFormBuilder('form_tema');
        $this->form->setFormTitle('Cadastro de Tema');

        //Cria os campos do formulário
        $id = new THidden('id');
        $descricao = new TEntry('descricao');

        //Cria os labels dos campos

        $descricao_lbl = new TLabel('Tema:');

        //Insere os campos no formulário
        $this->form->addFields([$id]);
        $this->form->addFields([$descricao_lbl], [$descricao]);
        $descricao->addValidation('Descrição', new TRequiredValidator);


        $this->form->addHeaderActionLink(_t('Close'), new TAction([$this, 'onClose']), 'fa:times red');
        //Cria os botões do formulário
        $btn_save = $this->form->addAction('Salvar', new TAction([$this, 'onSalva']), 'fa:save');
        $btn_save->class = 'btn btn-sm btn-primary';
        $btn_save->setImage('fa:save');
        $btn_save->setLabel('Salvar');

        $btn_limpa = $this->form->addAction('Limpar', new TAction([$this, 'onClear']), 'fa:plus');
        $btn_limpa->setAction(new TAction([$this, 'onClear']), 'Novo');
        $btn_limpa->class = 'btn btn-sm btn-secondary';

        //Exibe o formulário
        parent::add($this->form);
    }

    public static function onClose($param)
    {
        TScript::create("Template.closeRightPanel()");
    }
}
