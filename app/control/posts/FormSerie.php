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
class FormSerie extends TPage
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
        $this->setActiveRecord('Series');

        //Cria o formulário
        $this->form = new BootstrapFormBuilder('form_serie');
        $this->form->setFormTitle('Cadastro de Série ou Coleção');

        //Cria os campos do formulário
        $id = new THidden('id');
        $nome = new TEntry('nome');
        $obs = new TText('obs');
        $tag = new TEntry('tags');

        //Cria os labels dos campos

        $nome_lbl = new TLabel('Nome:');
        $obs_lbl = new TLabel('Observações:');
        $tag_lbl = new TLabel('Tags:');

        //Insere os campos no formulário
        $this->form->addFields([$id]);
        $this->form->addFields([$nome_lbl], [$nome]);
        $nome->addValidation('Nome', new TRequiredValidator);

        $this->form->addFields([$obs_lbl], [$obs]);

        $this->form->addFields([$tag_lbl], [$tag]);

        

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

    // public function onSalva($param)
    // {
    //     try {
    //         TTransaction::open('sample'); // abre transação

    //         $data = $this->form->getData(); // obtém os dados do formulário
    //         $object = new Series(); // cria novo objeto da classe Series
    //         $object->fromArray((array) $data); // carrega os dados
    //         $object->store(); // salva no banco

    //         $this->form->setData(new stdClass); // limpa o formulário

    //         TTransaction::close(); // fecha transação

    //         new TMessage('info', 'Registro salvo com sucesso');
    //     } catch (Exception $e) {
    //         new TMessage('error', $e->getMessage());
    //         TTransaction::rollback();
    //     }
    // }
}
