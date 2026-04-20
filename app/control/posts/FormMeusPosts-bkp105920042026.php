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
    use app\MeuTrait; // Trait para métodos personalizados

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
        $datapostagem->setMask('dd/mm/yyyy');
        $datapostagem->setDatabaseMask('yyyy-mm-dd');

        $passagem = new TEntry('passagem');
        $passagem->style = $fundo_campo;

        $btn_pericope = new TButton('btn_nova_pericope');
        $btn_pericope->setAction(new TAction(['FormPericope', 'onEdit']), '+');
        $btn_pericope->style = 'all: unset; margin: 0 auto; color: green; font-size: 20px; font-weight: bold; cursor: pointer;';

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

        // Recupera o tipo: prioriza o que vem do formulário (id_tipo), senão o parâmetro da URL (tipo), senão o padrão 1
        $tipo = $param['id_tipo'] ?? ($param['tipo'] ?? 1);

        $id_tipo->setValue($tipo);
        $this->onChangeTipo(['id_tipo' => $tipo]);

        // Retorna a data da última devocional postada para preencher o campo data_postagem
        TTransaction::open('sample');
        $ultima_devocional = Postagens::where('id_tipo', '=', 4)->orderBy('data_postagem', 'desc')->first();
        TTransaction::close();

        // Soma 1 dia à data da última devocional para sugerir a data da nova postagem
        if ($ultima_devocional) {
            $data_sugerida = date('Y-m-d', strtotime($ultima_devocional->data_postagem . ' +1 day'));
            $datapostagem->setValue(TDate::date2br($data_sugerida));
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
        $conteudo->setSize('100%', 400);

        $resumo = new THtmlEditor('resumo');
        $resumo->setSize('100%', 400);

        $tag = new TText('tags');
        $tag->setSize('100%', 70);
        $tag->style = 'background-color:rgb(192, 188, 188);';

        $publica_postagem = new TCombo('publica_postagem');
        $publica_postagem->addItems([0 => 'Rascunho', 1 => 'Publicado']);
        $publica_postagem->style = $fundo_campo;
        $publica_postagem->setValue(0);
        $publica_postagem->setSize('100%');

        $publica_resumo = new TCombo('publica_resumo');
        $publica_resumo->addItems([0 => 'Não', 1 => 'Sim']);
        $publica_resumo->style = $fundo_campo;
        $publica_resumo->setValue(0);
        $publica_resumo->setSize('100%');

        $btn_tag = new TButton('btn_tag');
        $btn_tag->style = 'all: unset; margin: 0 auto; color: green; font-size: 20px; font-weight: bold; cursor: pointer;';
        $btn_tag->setAction(new TAction([$this, 'onAddTag']), '+');

        // Criação de labels
        $titulo_lbl          = new TLabel('Título');
        $subtitulo_lbl       = new TLabel('Subtítulo');
        $publica_resumo_lbl  = new TLabel('Publicar Resumo');
        $datapostagem_lbl    = new TLabel('Data Postagem');
        $passagem_lbl        = new TLabel('Passagem');
        $id_tema_lbl         = new TLabel('Tema');
        $id_tipo_lbl         = new TLabel('Tipo');
        $id_subtipo_lbl      = new TLabel('Subtipo');
        $id_serie_lbl        = new TLabel('Série');
        $conteudo_lbl        = new TLabel('Conteúdo');
        $tag_lbl             = new TLabel('Tag');
        $publica_postagem_lbl = new TLabel('Status');
        $id_lbl              = new TLabel('ID');

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
            $resumo,
            $btn_tema,
            $btn_serie,
            $btn_tag,
            $btn_pericope,
            $publica_postagem,
            $publica_resumo
        ]);

        // Cria divs lado a lado
        $dv_geral = new TElement('div');
        $dv_geral->style = 'display: flex; width: 100%;';

        $dv_esquerda = new TElement('div');
        $dv_esquerda->style = 'display: inline-block; flex: 40%';

        $dv_direita = new TElement('div');
        $dv_direita->style = 'display: inline-block; flex: 60%';

        // Cria divs para cada linha
        $dv_linha = [];
        $total_linhas = 7;
        for ($i = 1; $i <= $total_linhas; $i++) {
            $dv_linha[$i] = new TElement('div');
            $dv_linha[$i]->style = 'display: flex; width: 100%; margin-top: 1px;';
        }

        // Título
        $dv_titulo = new TElement('div');
        $dv_titulo->style = 'margin-right: 10px; margin-top: 10px; display: inline-block; flex: 25%';
        $dv_titulo->add($titulo_lbl);
        $dv_titulo->add($titulo);

        // Subtítulo
        $dv_subtitulo = new TElement('div');
        $dv_subtitulo->style = 'margin-right: 10px; margin-top: 10px; display: inline-block; flex: 50%';
        $dv_subtitulo->add($subtitulo_lbl);
        $dv_subtitulo->add($subtitulo);

        // Data Postagem
        $dv_postagem = new TElement('div');
        $dv_postagem->style = 'margin-right: 10px; margin-top: 10px; display: inline-block; flex: 20%';
        $dv_postagem->add($datapostagem_lbl);
        $dv_postagem->add($datapostagem);

        // Passagem
        $dv_passagem = new TElement('div');
        $dv_passagem->style = 'margin-right: 10px; margin-top: 10px; display: inline-block; flex: 25%';
        $dv_passagem->add("<span>$passagem_lbl</span><span><sup>$btn_pericope</sup></span>");
        $dv_passagem->add($passagem);

        // Botão para inserir passagem bíblica no conteúdo via API
        $btn_biblia = new TElement('a');
        $btn_biblia->href    = 'javascript:void(0)';
        $btn_biblia->style   = 'display: inline-block; margin-top: 4px; font-size: 11px; color: #5a3e1b; text-decoration: none; background: #f0e4d0; border: 1px solid #c8a87a; border-radius: 4px; padding: 2px 8px; cursor: pointer;';
        $btn_biblia->onclick = "
            var btn = this;
            var passagemCampo = document.querySelector('[name=passagem]');
            var passagem = passagemCampo ? passagemCampo.value.trim() : '';

            if (!passagem) {
                passagem = prompt('Informe a passagem bíblica (ex: Jo 3.1-21):');
                if (!passagem) return false;
                if (passagemCampo) passagemCampo.value = passagem;
            }

            var originalText = btn.innerHTML;
            btn.innerHTML = '⏳ Buscando...';
            btn.style.pointerEvents = 'none';

            var url = 'engine.php?class=ApiPassagem&method=handle&passagem=' + encodeURIComponent(passagem) + '&versao=NAA';

            fetch(url)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Erro na resposta do servidor: ' + response.statusText);
                    }
                    return response.json();
                })
                .then(data => {
                    btn.innerHTML = originalText;
                    btn.style.pointerEvents = 'auto';

                    if (data.ok) {
                        var editor = \$('[name=\"conteudo\"]');

                        if (editor.length) {
                            var htmlAtual = editor.summernote('code');

                            if (htmlAtual.indexOf('{{passagem}}') !== -1) {
                                htmlAtual = htmlAtual.replace('{{passagem}}', data.html);
                            } else {
                                htmlAtual += '<br>' + data.html;
                            }

                            editor.summernote('code', htmlAtual);

                            if (typeof TToast !== 'undefined') {
                                TToast.show('success', 'Passagem inserida com sucesso!', 'bottom center', 'far:check-circle');
                            }
                        } else {
                            alert('Editor de conteúdo não encontrado.');
                        }
                    } else {
                        alert('Erro ao buscar passagem: ' + data.error);
                    }
                })
                .catch(error => {
                    btn.innerHTML = originalText;
                    btn.style.pointerEvents = 'auto';
                    alert('Falha na comunicação com a API. Verifique o console ou a aba Network (Rede). Erro: ' + error);
                });
            return true;
        ";
        $btn_biblia->add('📖 Inserir Passagem');
        $dv_passagem->add($btn_biblia);

        // Tema
        $dv_tema = new TElement('div');
        $dv_tema->style = 'margin-right: 10px; margin-top: 10px; display: inline-block; flex: 25%';
        $dv_tema->add("<span>$id_tema_lbl</span><span><sup>$btn_tema</sup></span>");
        $dv_tema->add($id_tema);

        // Tipo
        $dv_tipo = new TElement('div');
        $dv_tipo->style = 'margin-right: 10px; margin-top: 10px; display: inline-block; flex: 25%';
        $dv_tipo->add($id_tipo_lbl);
        $dv_tipo->add($id_tipo);

        // Subtipo
        $dv_subtipo = new TElement('div');
        $dv_subtipo->style = 'margin-right: 10px; margin-top: 10px; display: inline-block; flex: 25%';
        $dv_subtipo->add($id_subtipo_lbl);
        $dv_subtipo->add($id_subtipo);

        // Tag
        $dv_tag = new TElement('div');
        $dv_tag->style = 'margin-right: 10px; margin-top: 10px; display: inline-block; width: 100%';
        $dv_tag->add("<span>$tag_lbl</span><span><sup>$btn_tag</sup></span>");
        $dv_tag->add($tag);

        // Série
        $dv_serie = new TElement('div');
        $dv_serie->style = 'margin-right: 10px; margin-top: 10px; display: inline-block; flex: 25%';
        $dv_serie->add("<span>$id_serie_lbl</span><span><sup>$btn_serie</sup></span>");
        $dv_serie->add($id_serie);

        // Publicar postagem
        $dv_publica = new TElement('div');
        $dv_publica->style = 'margin-right: 10px; margin-top: 10px; display: inline-block; width: 25%;';
        $publica_postagem_lbl->style = 'display: block; margin-bottom: 4px;';
        $dv_publica->add($publica_postagem_lbl);
        $dv_publica->add($publica_postagem);

        // Publicar resumo — visível somente para tipos 1 e 2 (Sermão / Estudo Bíblico)
        $publica_resumo_lbl->style = 'display: block; margin-bottom: 4px;';

        // Define a visibilidade do div no carregamento inicial conforme o tipo
        $display_pub_resumo = ($tipo == 1 || $tipo == 2) ? 'inline-block' : 'none';

        $dv_pub_resumo = new TElement('div');
        $dv_pub_resumo->style = "margin-right: 10px; margin-top: 10px; width: 25%; display: {$display_pub_resumo};";
        $dv_pub_resumo->add($publica_resumo_lbl);
        $dv_pub_resumo->add($publica_resumo);

        // Garante valor 0 quando o tipo não exibe o campo
        if ($tipo != 1 && $tipo != 2) {
            $publica_resumo->setValue(0);
        }

        // Insere os campos no formulário nas linhas criadas
        $dv_linha[1]->add($dv_titulo);
        $dv_linha[2]->add($dv_subtitulo);
        $dv_linha[3]->add($dv_tema);
        $dv_linha[3]->add($dv_serie);
        $dv_linha[4]->add($dv_postagem);
        $dv_linha[4]->add($dv_passagem);
        $dv_linha[5]->add($dv_tipo);
        $dv_linha[5]->add($dv_subtipo);
        $dv_linha[6]->add($dv_tag);
        $dv_linha[7]->add($dv_publica);
        $dv_linha[7]->add($dv_pub_resumo);

        // Adiciona o id oculto
        $this->form->addFields([$id]);

        // Insere botões no formulário
        $tamanho_botao = "width: 25mm;";

        $btn = $this->form->addAction(_t('Save'), new TAction([$this, 'onSave'], ['tipo' => $id_tipo->getValue()]), 'fa:save white');
        $btn->class = 'btn btn-sm btn-success';
        $btn->style = $tamanho_botao;

        $btn2 = $this->form->addAction('Limpar', new TAction([$this, 'onClear']), 'fa:eraser white');
        $btn2->class = 'btn btn-sm btn-danger';
        $btn2->style = $tamanho_botao;

        $btn = $this->form->addAction('Novo', new TAction([$this, 'onClear'], ['registro' => 'novo']), 'fa:plus black');
        $btn->class = 'btn btn-sm btn-info';
        $btn->setLabel('Novo');
        $btn->style = $tamanho_botao;

        if (isset($param['id']) && $param['id'] !== null && $param['id'] !== '') {
            $btn_pdf = $this->form->addAction('PDF', new TAction([$this, 'onPdf'], ['tipo' => $id_tipo->getValue()]), 'fa:file-pdf black');
            $btn_pdf->class = 'btn btn-sm btn-secondary';
            $btn_pdf->style = $tamanho_botao;
        }

        if (!isset($param['id']) || $param['id'] === null || $param['id'] === '') {
            $btn_sermao = $this->form->addAction(
                'Gera IA',
                new TAction([$this, 'onGerarSermao']),
                'fa:magic orange'
            );
            $btn_sermao->class = 'btn btn-sm btn-warning';
            $btn_sermao->style = $tamanho_botao;
        }

        if ($tipo != 4) {
            $btn_preencher_resumo = $this->form->addAction(
                'Resumo',
                new TAction([$this, 'onPreencherResumo']),
                'fa:copy blue'
            );
            $btn_preencher_resumo->class = 'btn btn-sm btn-primary';
            $btn_preencher_resumo->style = $tamanho_botao;
        }

        // Insere campos da coluna esquerda
        for ($i = 1; $i <= $total_linhas; $i++) {
            $dv_esquerda->add($dv_linha[$i]);
        }

        // Coluna direita com Notebook do Bootstrap
        $notebook = new BootstrapNotebookWrapper(new TNotebook(400, 230), 'bordered');
        $page1 = $notebook->appendPage('Conteúdo', $conteudo);

        if ($tipo != 4) {
            $page2 = $notebook->appendPage('Resumo', $resumo);
        }

        $dv_direita->add($notebook);

        $dv_geral->add($dv_esquerda);
        $dv_geral->add($dv_direita);
        $this->form->addFields([$dv_geral]);

        // Adiciona o formulário dentro de um Box
        $vbox = new TVBox();
        $vbox->style = 'width: 100%';
        $vbox->add($this->form);

        parent::add($vbox);
    }


    public function onClear($param)
    {
        $this->form->clear();

        $data = new stdClass();

        // Mantém o tipo que estava selecionado no formulário antes de clicar em Novo
        $data->id_tipo = $param['id_tipo'] ?? 1;

        // Recalcula a data sugerida para não perdê-la após limpar o formulário
        if ($data->id_tipo == 4) {
            TTransaction::open('sample');
            $ultima_devocional = Postagens::where('id_tipo', '=', 4)->orderBy('data_postagem', 'desc')->first();
            if ($ultima_devocional) {
                $data->data_postagem = date('d/m/Y', strtotime($ultima_devocional->data_postagem . ' +1 day'));
            }
            TTransaction::close();
        }

        if (isset($param['registro']) && $param['registro'] === 'novo') {
            unset($data->id);
        }

        $this->form->setData($data);

        self::onChangeTipo(['id_tipo' => $data->id_tipo]);
    }

    public static function onChangeTipo($param)
    {
        try {
            TTransaction::open('sample');

            if (!empty($param['id_tipo'])) {
                $estado   = TipoConteudo::where('id', '=', $param['id_tipo'])->first();
                $criteria = TCriteria::create(['id_tipo' => $param['id_tipo']]);

                TDBCombo::reloadFromModel('form_meus_posts', 'id_subtipo', 'sample', 'SubTipoConteudo', 'id', "descricao", 'id', $criteria, TRUE);

                // Controla visibilidade do campo id_subtipo
                if ($param['id_tipo'] == 2 || $param['id_tipo'] == 4) {
                    TScript::create("document.querySelector('[name=id_subtipo]').closest('div').style.display = 'none';");
                    TScript::create("document.getElementById('id_tipo').style.width = '49.2%';");
                } else {
                    TScript::create("document.querySelector('[name=id_subtipo]').closest('div').style.display = 'inline-block';");
                    TScript::create("document.getElementById('id_tipo').style.width = '100%';");
                }

                // Controla visibilidade do campo id_serie
                if ($param['id_tipo'] == 3) {
                    TScript::create("document.querySelector('[name=id_serie]').closest('div').style.display = 'none';");
                } else {
                    TScript::create("document.querySelector('[name=id_serie]').closest('div').style.display = 'inline-block';");
                }

                // Controla visibilidade do campo publica_resumo
                // Exibe somente para tipo 1 (Sermão) e tipo 2 (Estudo Bíblico)
                if ($param['id_tipo'] == 1 || $param['id_tipo'] == 2) {
                    TScript::create("document.querySelector('[name=publica_resumo]').closest('div').style.display = 'inline-block';");
                } else {
                    // Oculta o campo e força o valor para 0
                    TScript::create("document.querySelector('[name=publica_resumo]').closest('div').style.display = 'none';");
                    TScript::create("document.querySelector('[name=publica_resumo]').value = '0';");
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
                $postagem = new Postagens($key);

                $criteria = new TCriteria();
                $criteria->add(new TFilter('id_tipo', '=', $postagem->id_tipo));

                if ($postagem->id_tipo != 2) {
                    TDBCombo::reloadFromModel('form_meus_posts', 'id_subtipo', 'sample', 'SubTipoConteudo', 'id', 'descricao', 'id', $criteria, TRUE);

                    TScript::create("setTimeout(function() {
                    document.querySelector('[name=id_subtipo]').value = '{$postagem->id_subtipo}';
                }, 300);");
                }

                $postagem->data_postagem = TDate::date2br($postagem->data_postagem);
                $this->form->sendData('form_meus_posts', $postagem);

                self::onChangeTipo(['id_tipo' => $postagem->id_tipo]);
            }

            TTransaction::close();
        } catch (Exception $e) {
            new TMessage('error', 'Erro ao carregar dados: ' . $e->getMessage());
            TTransaction::rollback();
        }
    }

    public function onGerarSermao($param)
    {
        try {
            $data = $this->form->getData();
            set_time_limit(120);

            $titulo      = trim($data->titulo ?? '');
            $passagem    = trim($data->passagem ?? '');
            $id_tipo     = $data->id_tipo ?? null;
            $id_subtipo  = $data->id_subtipo ?? null;

            if ($titulo === '' && $passagem === '') {
                throw new Exception('Preencha pelo menos Título ou Passagem para gerar o conteúdo.');
            }

            $tipo_descricao = '';
            if ($id_tipo) {
                TTransaction::open('sample');
                $tipo = new TipoConteudo($id_tipo);
                $tipo_descricao = $tipo->descricao;
                TTransaction::close();
            }

            $subtipo_descricao = '';
            if ($id_subtipo) {
                TTransaction::open('sample');
                $subtipo = new SubTipoConteudo($id_subtipo);
                $subtipo_descricao = $subtipo->descricao;
                TTransaction::close();
            }

            $resource_file = null;

            if (
                mb_stripos($tipo_descricao, 'estudo bíblico') !== false
                || mb_stripos($tipo_descricao, 'estudo biblico') !== false
                || mb_stripos($tipo_descricao, 'estudo') !== false
            ) {
                $resource_file = 'app/resources/app/cria_estudos_biblicos.md';
            } elseif (mb_stripos($tipo_descricao, 'devocional') !== false) {
                $resource_file = 'app/resources/app/devocional.md';
            } elseif (
                mb_stripos($tipo_descricao, 'sermão') !== false
                || mb_stripos($tipo_descricao, 'sermao') !== false
            ) {
                if (
                    mb_stripos($subtipo_descricao, 'expositivo') !== false
                    || mb_stripos($subtipo_descricao, 'textual') !== false
                ) {
                    $resource_file = 'app/resources/app/sermao_expositivo.md';
                } elseif (
                    mb_stripos($subtipo_descricao, 'temático') !== false
                    || mb_stripos($subtipo_descricao, 'tematico') !== false
                ) {
                    $resource_file = 'app/resources/app/sermao_tematico.md';
                } elseif (
                    mb_stripos($subtipo_descricao, 'biográfico') !== false
                    || mb_stripos($subtipo_descricao, 'biografico') !== false
                ) {
                    $resource_file = 'app/resources/app/sermao_biografico.md';
                }
            }

            if (!$resource_file) {
                throw new Exception('Tipo/Subtipo não reconhecido para seleção do modelo de prompt.');
            }

            if (!file_exists($resource_file)) {
                throw new Exception("Arquivo de recurso não encontrado: {$resource_file}");
            }
            $prompt_base = file_get_contents($resource_file);

            if ($titulo === '' && $passagem !== '') {
                $prompt_titulo = "A partir da passagem bíblica abreviada: {$passagem}, proponha um título adequado e fiel à teologia reformada para um conteúdo cristocêntrico. Devolva apenas o título.";
                $novo_titulo = $this->chamarClaude($prompt_titulo);
                $data->titulo = trim($novo_titulo);
                $titulo = $data->titulo;
            }

            if ($passagem === '' && $titulo !== '') {
                $prompt_passagem = "A partir do título: \"{$titulo}\", sugira uma passagem bíblica adequada, no formato abreviado dos livros bíblicos (por exemplo: Jo 3.16; Rm 8.28; Sl 23.1-6). Devolva apenas a referência.";
                $nova_passagem = $this->chamarClaude($prompt_passagem);
                $data->passagem = trim($nova_passagem);
                $passagem = $data->passagem;
            }

            $instrucao_tipo  = "Sou calvinista moderado, uso muito material de Russell Shedd, 
            Millard Erickson, Wayne Grudem, Augustus Nicodemos, Hernandes Dias Lopes, Charles Spurgeon, John Piper, John MacArthur Júnior, 
            entre outros de linha reformada. Além de admirar alguns de linha arminiana como Isaltino Gomes Coelho Filho, Max Lucado, A. W. Tozer. 
            Escatologicamente defendo a linha pré-milenista pós-tribulacionista. Mas as afirmações pessoais só devem ser feitas se pedidas ou necessárias para algum contexto específico.\n\n";

            $prompt_conteudo  = $prompt_base . "\n\n";
            $prompt_conteudo .= $instrucao_tipo;

            if ($tipo_descricao !== '') {
                $prompt_conteudo .= "Tipo de conteúdo: {$tipo_descricao}\n";
            }
            if ($subtipo_descricao !== '') {
                $prompt_conteudo .= "Subtipo: {$subtipo_descricao}\n";
            }
            if ($titulo !== '') {
                $prompt_conteudo .= "Título: {$titulo}\n";
            }
            if ($passagem !== '') {
                $prompt_conteudo .= "Passagem bíblica (abreviada): {$passagem}\n";
            }

            $conteudo_gerado = $this->chamarClaude($prompt_conteudo);

            $somente_sermon_block = $this->extrairSermonBlock($conteudo_gerado);

            $data->conteudo = $somente_sermon_block ?? $conteudo_gerado;

            $this->form->setData($data);

            TToast::show('success', 'Conteúdo gerado com sucesso. Revise o texto antes de salvar.', 'bottom center', 'far:check-circle');
        } catch (Exception $e) {
            $this->form->setData($this->form->getData());
            new TMessage('error', $e->getMessage());
        }
    }

    public function onPreencherResumo($param)
    {
        try {
            set_time_limit(120);

            $data     = $this->form->getData();
            $conteudo = trim($data->conteudo ?? '');
            $titulo   = trim($data->titulo ?? '');

            if ($conteudo === '') {
                throw new Exception('Preencha o campo Conteúdo antes de gerar o resumo.');
            }

            if ($titulo !== '') {
                $conteudo = "TÍTULO: {$titulo}\n\n" . $conteudo;
            } else {
                throw new Exception('O campo Título é necessário para gerar um resumo mais preciso. Por favor, preencha o título e tente novamente.');
            }

            $caminho_md = 'app/resources/app/resumos.md';
            if (!is_readable($caminho_md)) {
                throw new Exception('Arquivo de prompt de resumo não encontrado: ' . $caminho_md);
            }

            $prompt_md = file_get_contents($caminho_md);

            $prompt = $prompt_md . "\n\n"
                . "----- TEXTO A SER RESUMIDO -----\n"
                . $conteudo . "\n"
                . "----- FIM DO TEXTO -----\n";

            $resumo_gerado = $this->chamarClaude($prompt);

            $somente_resumo_block = $this->extrairSermonBlock($resumo_gerado);
            $data->resumo = $somente_resumo_block ?? $resumo_gerado;
            $this->form->setData($data);

            TToast::show('success', 'Resumo gerado com sucesso. Revise o texto antes de salvar.', 'bottom center', 'far:check-circle');
        } catch (Exception $e) {
            $this->form->setData($this->form->getData());
            new TMessage('error', $e->getMessage());
        }
    }

    /**
     * method onSave()
     * Executed whenever the user clicks at the save button
     */
    public function onSave($param)
    {

        $raw = $this->form->getData();
        // Debug temporário — remova após confirmar
        new TMessage(
            'info',
            'publica_postagem raw: [' . var_export($raw->publica_postagem, true) . '] | ' .
                'publica_resumo raw: ['   . var_export($raw->publica_resumo,   true) . ']'
        );
        return;
        try {
            TTransaction::open('sample');

            $object = $this->form->getData('Postagens');

            $this->form->validate();

            // Garante que valores 0 do TCombo não sejam perdidos pelo Adianti
            $raw = $this->form->getData();
            $object->publica_postagem = isset($raw->publica_postagem) ? (int)$raw->publica_postagem : 0;
            $object->publica_resumo   = isset($raw->publica_resumo)   ? (int)$raw->publica_resumo   : 0;

            $object->store();

            if (!empty($this->afterSaveCallback)) {
                $callback = $this->afterSaveCallback;
                $callback($object, $this->form->getData());
            }

            TTransaction::close();

            if (isset($this->useMessages) && $this->useMessages === false) {
                AdiantiCoreApplication::loadPageURL($this->afterSaveAction->serialize());
            } else {
                TToast::show('success', 'Registro salvo com sucesso', 'bottom center', 'far:check-circle');

                // Recarrega o formulário com o id salvo para exibir botão PDF
                // e ocultar botão Gera IA corretamente
                AdiantiCoreApplication::loadPage(
                    'FormMeusPosts',
                    'onEdit',
                    ['key' => $object->id, 'id' => $object->id, 'id_tipo' => $object->id_tipo]
                );
            }

            return $object;
        } catch (Exception $e) {
            $object = $this->form->getData();
            $this->form->setData($object);
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    function onPdf($param)
    {
        $this->form->setData($this->form->getData());
        $this->onGeraPDF($param);
    }

    protected function extrairSermonBlock(?string $html): ?string
    {
        if (empty($html)) {
            return null;
        }

        $padrao = '/<div\s+class=("|\')sermon-block("|\')(.*?)<\/div>/is';

        if (preg_match($padrao, $html, $matches)) {
            return $matches[0];
        }

        return null;
    }

    public function onAddTag($param)
    {
        try {
            set_time_limit(120);

            $data     = $this->form->getData();
            $conteudo = trim($data->conteudo ?? '');
            $titulo   = trim($data->titulo ?? '');

            if ($conteudo === '') {
                throw new Exception('Preencha o campo Conteúdo antes de gerar as palavras-chave.');
            }

            if ($titulo !== '') {
                $conteudo = "TÍTULO: {$titulo}\n\n" . $conteudo;
            } else {
                throw new Exception('O campo Título é necessário para gerar palavras-chave mais precisas. Por favor, preencha o título e tente novamente.');
            }

            $caminho_md = 'app/resources/app/palavras_chaves.md';
            if (!is_readable($caminho_md)) {
                throw new Exception('Arquivo de prompt de palavras-chave não encontrado: ' . $caminho_md);
            }

            $prompt_md = file_get_contents($caminho_md);

            $prompt = $prompt_md . "\n\n"
                . "----- TEXTO A SER ANALISADO -----\n"
                . $conteudo . "\n"
                . "----- FIM DO TEXTO -----\n";

            $palavras_chaves = $this->chamarClaude($prompt);

            $data->tags = $palavras_chaves;
            $this->form->setData($data);

            TToast::show('info', 'Palavras-chave geradas com sucesso. Revise antes de salvar.', 'bottom center', 'far:check-circle');
        } catch (Exception $e) {
            $this->form->setData($this->form->getData());
            new TMessage('error', $e->getMessage());
        }
    }
}
