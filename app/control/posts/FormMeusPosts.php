<?php
//Criar um arquivo posts.php na pasta app/control/posts

use Adianti\Base\Meutrait;
use Adianti\Control\TPage;
use Adianti\Widget\Form\TButton;
use Adianti\Widget\Form\THtmlEditor;
use Adianti\Widget\Form\TText;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TDate;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Wrapper\TDBCombo;
use Adianti\Widget\Wrapper\TDBUniqueSearch;
use Adianti\Widget\Form\TForm;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Base\TElement;
use Adianti\Control\TAction;
use Adianti\Database\TCriteria;
use Adianti\Database\TFilter;
use Adianti\Database\TTransaction;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Form\TCombo;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Form\THidden;

class FormMeusPosts extends TPage
{

    private $form; // Formulário

    use Adianti\Base\AdiantiStandardFormTrait; // Standard form methods
    use Adianti\Base\MeuTrait;

    public function __construct($param)
    {
        parent::__construct();

        // Acessar database
        $this->setDatabase('sample'); // define the database
        $this->setActiveRecord('Postagens'); // define the active record

        //Insere css especifico
        TPage::include_css('app/control/posts/css/form_meus_posts.css');

        // Criando formulário
        $this->form = new BootstrapFormBuilder('form_meus_posts');
        $this->form->setFormTitle('<strong>MEUS POSTS</strong>');

        $fundo_campo = 'background-color:rgb(252, 252, 238);';
        // Criação dos campos do formulário com base na tabela tbl_postagem
        $id = new THidden('id');
        $titulo = new TEntry('titulo');
        $titulo->style = $fundo_campo;
        $subtitulo = new TEntry('subtitulo');
        $subtitulo->style = $fundo_campo;

        $datapostagem = new TDate('data_postagem');
        $datapostagem->style = $fundo_campo;

        $passagem = new TEntry('passagem');
        $passagem->style = $fundo_campo;

        $id_tema = new TDBUniqueSearch('id_tema', 'sample', 'TemaConteudo', 'id', 'descricao');
        $id_tema->setMinLength(0);
        $id_tema->style = $fundo_campo;

        $btn_tema = new TButton('btn_novo_tema');
        $btn_tema->setAction(new TAction(['FormTema', 'onEdit']), '+');
        $btn_tema->style = 'all: unset; margin: 0 auto; color: green; font-size: 20px; font-weight: bold; cursor: pointer;';



        $id_tipo = new TDBCombo('id_tipo', 'sample', 'TipoConteudo', 'id', 'descricao');
        $id_tipo->setChangeAction(new TAction([$this, 'onChangeTipo']));
        $id_tipo->setId('id_tipo');
        $id_tipo->style = $fundo_campo;
        //Preenche o campo id_tipo conforme o tipo de postagem

        if (isset($param['tipo'])) {
            //Usa swicth
            switch ($param['tipo']) {
                case 3:
                    $id_tipo->setValue(3);
                    break;
                case 2:
                    $id_tipo->setValue(2);
                    break;
                case 1:
                    $id_tipo->setValue(1);
                    break;
                default:
                    $id_tipo->setValue(0);
                    break;
            }

        }



        $filter = new TCriteria;
        $filter->add(new TFilter('id', '<', '0'));



        $id_subtipo = new TDBCombo('id_subtipo', 'sample', 'SubTipoConteudo', 'id', 'descricao', 'id', $filter);
        $this->id_subtipo = $id_subtipo;

        $id_subtipo->style = $fundo_campo;

        $id_serie = new TDBUniqueSearch('id_serie', 'sample', 'Series', 'id', 'nome');
        $id_serie->setMinLength(0);

        $btn_serie = new TButton('btn_nova_serie');
        $btn_serie->setAction(new TAction(['FormSerie', 'onEdit']), '+');
        $btn_serie->style = 'all: unset; margin: 0 auto; color: green; font-size: 20px; font-weight: bold; cursor: pointer;';

        $conteudo = new THtmlEditor('conteudo');
        $conteudo->setSize('100%', 500);


        $tag = new TText('tags');
        $tag->setSize('100%', 100);
        $tag->style = 'background-color:rgb(192, 188, 188);';

        // criação de labels
        $titulo_lbl = new TLabel('Título');
        $subtitulo_lbl = new TLabel('Subtítulo');



        $datapostagem_lbl = new TLabel('Data Postagem'); //Definindo o formato da data dd/mm/YYYY
        $datapostagem->setMask('dd/mm/yyyy');
        $datapostagem->setDatabaseMask('yyyy-mm-dd');


        $passagem_lbl = new TLabel('Passagem');
        $id_tema_lbl = new TLabel('Tema');
        $id_tipo_lbl = new TLabel('Tipo');
        $id_subtipo_lbl = new TLabel('Subtipo');

        $id_serie_lbl = new TLabel('Série');



        $conteudo_lbl = new TLabel('Conteúdo');
        $tag_lbl = new TLabel('Tag');
        $id_lbl = new TLabel('ID');
        $id_lbl->setProperty('style', 'display: none;');
        $id->setProperty('style', 'display: none;');

        // Insere os campos no formulário com setFields
        $this->form->setFields([
             $titulo,
             $id_serie,
            $subtitulo,
            $datapostagem,
            $passagem,
            $id_tema,
            $id_tipo,
            $id_subtipo,
            $conteudo,
            $tag,
            $btn_tema,
            $btn_serie
        ]);
        //Cria div's lado a lado 
        $dv_geral = new TElement('div');
        $dv_geral->style = 'display: flex; width: 100%;';

        $dv_esquerda = new TElement('div');
        $dv_esquerda->style = 'display: inline-block; flex: 40%';
        $dv_direita = new TElement('div');
        $dv_direita->style = 'display: inline-block; flex: 60%';



        //Cria div's para cada linha
        $dv_linha = [];
        $total_linhas = 7;
        for ($i = 1; $i <= $total_linhas; $i++) {
            $dv_linha[$i] = new TElement('div');
            $dv_linha[$i]->style = 'display: flex; width: 100%;';
        }


        //Titulo
        $dv_titulo = new TElement('div');
        $dv_titulo->style = 'margin-right: 10px; margin-top: 10px; display: inline-block; flex: 25%';
        $dv_titulo->add($titulo_lbl);
        $dv_titulo->add($titulo);

        //Subtitulo
        $dv_subtitulo = new TElement('div');
        $dv_subtitulo->style = 'margin-right: 10px; margin-top: 10px; display: inline-block; flex: 50%';
        $dv_subtitulo->add($subtitulo_lbl);
        $dv_subtitulo->add($subtitulo);

        //Data Postagem
        $dv_postagem = new TElement('div');
        $dv_postagem->style = 'margin-right: 10px; margin-top: 10px; display: inline-block; flex: 20%';
        $dv_postagem->add($datapostagem_lbl);
        $dv_postagem->add($datapostagem);



        //Categoria
        $dv_passagem = new TElement('div');
        $dv_passagem->style = 'margin-right: 10px; margin-top: 10px; display: inline-block; flex: 25%';
        $dv_passagem->add($passagem_lbl);
        $dv_passagem->add($passagem);

        //Tema
        $dv_tema = new TElement('div');
        $dv_tema->style = 'margin-right: 10px; margin-top: 10px; display: inline-block; flex: 25%';
        $dv_tema->add("<span>$id_tema_lbl</span><span><sup>$btn_tema</sup></span>");
        $dv_tema->add($id_tema);

        //Tipo
        $dv_tipo = new TElement('div');
        $dv_tipo->style = 'margin-right: 10px; margin-top: 10px; display: inline-block; flex: 25%';
        $dv_tipo->add($id_tipo_lbl);
        $dv_tipo->add($id_tipo);

        //Subtipo
        $dv_subtipo = new TElement('div');
        $dv_subtipo->style = 'margin-right: 10px; margin-top: 10px; display: inline-block; flex: 25%';
        $dv_subtipo->add($id_subtipo_lbl);
        $dv_subtipo->add($id_subtipo);

        //Tag
        $dv_tag = new TElement('div');
        $dv_tag->style = 'margin-right: 10px; margin-top: 10px; display: inline-block; width: 100%';
        $dv_tag->add($tag_lbl);
        $dv_tag->add($tag);

        //Serie
        $dv_serie = new TElement('div');
        $dv_serie->style = 'margin-right: 10px; margin-top: 10px; display: inline-block; flex: 25%';
        $dv_serie->add("<span>$id_serie_lbl</span><span><sup>$btn_serie</sup></span>");
        $dv_serie->add($id_serie);


        //Insere os campos no formulário nas linhas criadas
        $dv_linha[1]->add($dv_titulo);
        $dv_linha[2]->add($dv_subtitulo);
        $dv_linha[3]->add($dv_tema);
        $dv_linha[4]->add($dv_postagem);
        $dv_linha[4]->add($dv_passagem);

        $dv_linha[5]->add($dv_tipo);
        $dv_linha[5]->add($dv_subtipo);
        $dv_linha[6]->add($dv_serie);
        $dv_linha[7]->add($dv_tag);


        //Adiciona o id oculto
        $this->form->addFields([$id]);



        //Insere botões no formulário
        $tamanho_botao = "width: 25mm;";
        $btn = $this->form->addAction(_t('Save'),  new TAction([$this, 'onSalva']),  'fa:save white');
        $btn->class = 'btn btn-success';
        $btn->style = $tamanho_botao;


        $btn2 = $this->form->addAction('Limpar', new TAction([$this, 'onClear']), 'fa:eraser white');
        $btn2->class = 'btn btn-danger';
        $btn2->style = $tamanho_botao;

        //Insere campos da coluna esquerda

        for ($i = 1; $i <= $total_linhas; $i++) {
            $dv_esquerda->add($dv_linha[$i]);
        }



        $dv_direita->add($conteudo_lbl);
        $dv_direita->add($conteudo);

        $dv_geral->add($dv_esquerda);
        $dv_geral->add($dv_direita);
        $this->form->addFields([$dv_geral]);





        // Adiciona o formulário dentro de um Box
        $vbox = new TVBox();
        $vbox->style = 'width: 100%';
        $vbox->add($this->form);

        parent::add($vbox);
    }


    public function onClear()
    {
        $this->form->clear(); // Limpa o formulário
        $this->form->setData(new stdClass); // Preenche o formulário com um objeto vazio
    }

    public static function onChangeTipo($param)
    {
        try {

            TTransaction::open('sample');
            if (!empty($param['id_tipo'])) {
                $estado = TipoConteudo::where('id', '=', $param['id_tipo'])->first();
                $criteria = TCriteria::create(['id_tipo' => $param['id_tipo']]);


                TDBCombo::reloadFromModel('form_meus_posts', 'id_subtipo', 'sample', 'SubTipoConteudo', 'id', "descricao", 'id', $criteria, TRUE);

                if ($param['id_tipo'] == 2) {
                    // Esconde o container dos campos subtipo
                    TScript::create("document.querySelector('[name=id_subtipo]').closest('div').style.display = 'none';");
                    TScript::create("document.getElementById('id_tipo').style.width = '49.2%';");
                } else {
                    // Exibe o container novamente
                    TScript::create("document.querySelector('[name=id_subtipo]').closest('div').style.display = 'inline-block';");
                    TScript::create("document.getElementById('id_tipo').style.width = '100%';");
                }

                if ($param['id_tipo'] == 3) {
                    // Esconde o container dos campos subtipo
                    TScript::create("document.querySelector('[name=id_serie]').closest('div').style.display = 'none';");
                } else {
                    // Exibe o container novamente
                    TScript::create("document.querySelector('[name=id_serie]').closest('div').style.display = 'inline-block';");
                }
            } else {
                TCombo::clearField('form_meus_posts', 'id_subtipo');
            }

            TTransaction::close();
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
        }
    }

    public function onEdit($param)
    {
        try {
            TTransaction::open('sample');

            if (isset($param['key'])) {
                $key = $param['key'];

                // Carrega o registro do banco
                $postagem = new Postagens($key);

                // Recarrega os valores do campo id_subtipo com base no id_tipo
                $criteria = new TCriteria();
                $criteria->add(new TFilter('id_tipo', '=', $postagem->id_tipo));

                if ($postagem->id_tipo != 2) {
                    TDBCombo::reloadFromModel('form_meus_posts', 'id_subtipo', 'sample', 'SubTipoConteudo', 'id', 'descricao', 'id', $criteria, TRUE);

                    // Define o valor de id_subtipo explicitamente após o reload
                    TScript::create("setTimeout(function() {
                                    document.querySelector('[name=id_subtipo]').value = '{$postagem->id_subtipo}';
                                }, 300);");
                }


                // Preenche o formulário com os dados do banco
                // $this->form->setData($postagem);
                //converte data para o formato brasileiro
                $postagem->data_postagem = TDate::date2br($postagem->data_postagem);

                $this->form->sendData('form_meus_posts', $postagem);


                // Atualiza o comportamento visual do tipo (para ocultar/mostrar campos conforme regras)
                self::onChangeTipo(['id_tipo' => $postagem->id_tipo]);
            }

            TTransaction::close();
        } catch (Exception $e) {
            new TMessage('error', 'Erro ao carregar dados: ' . $e->getMessage());
            TTransaction::rollback();
        }
    }

    /**
     * method onSave()
     * Executed whenever the user clicks at the save button
     */
    public function onSave($param)
    {
        try {


            // open a transaction with database
            TTransaction::open('sample');
            // get the active record

            // get the form data
            $object = $this->form->getData('Postagens');

            // var_dump($object);
            // validate data
            $this->form->validate();

            // stores the object
            $object->store();

            if (!empty($this->afterSaveCallback)) {
                $callback = $this->afterSaveCallback;
                $callback($object, $this->form->getData());
            }

            // fill the form with the active record data
            $object->data_postagem = TDate::date2br($object->data_postagem);
            $this->form->sendData('form_meus_posts', $object);


            // close the transaction
            TTransaction::close();

            // shows the success message
            if (isset($this->useMessages) and $this->useMessages === false) {
                AdiantiCoreApplication::loadPageURL($this->afterSaveAction->serialize());
            } else {
                new TMessage('info', AdiantiCoreTranslator::translate('Record saved'), $this->afterSaveAction);
            }

            return $object;
        } catch (Exception $e) // in case of exception
        {
            // get the form data
            $object = $this->form->getData();

            // fill the form with the active record data
            $this->form->setData($object);

            // shows the exception error message
            new TMessage('error', $e->getMessage());

            // undo all pending operations
            TTransaction::rollback();
        }
    }
}
