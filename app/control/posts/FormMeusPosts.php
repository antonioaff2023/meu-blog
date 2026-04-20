<?php

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
    private $form;

    use Adianti\Base\AdiantiStandardFormTrait;
    use app\MeuTrait;

    public function __construct($param)
    {
        parent::__construct();

        $this->setDatabase('sample');
        $this->setActiveRecord('Postagens');

        TPage::include_css('app/control/posts/css/form_meus_posts.css');

        $this->form = new BootstrapFormBuilder('form_meus_posts');
        $this->form->setFormTitle('<strong>MEUS POSTS</strong>');

        $fundo_campo = 'background-color:rgb(252, 252, 238);';

        // ── Tipo ──────────────────────────────────────────────────────────
        $tipo = (int)($param['id_tipo'] ?? $param['tipo'] ?? 1);

        // ── Campos ────────────────────────────────────────────────────────
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
        $id_tipo->setValue($tipo);

        TTransaction::open('sample');
        $ultima_devocional = Postagens::where('id_tipo', '=', 4)->orderBy('data_postagem', 'desc')->first();
        TTransaction::close();

        if ($ultima_devocional) {
            $data_sugerida = date('Y-m-d', strtotime($ultima_devocional->data_postagem . ' +1 day'));
            $datapostagem->setValue(TDate::date2br($data_sugerida));
        }

        $filter = new TCriteria;
        $filter->add(new TFilter('id', '<', '0'));
        $id_subtipo = new TDBCombo('id_subtipo', 'sample', 'SubTipoConteudo', 'id', 'descricao', 'id', $filter);
        $this->id_subtipo = $id_subtipo;
        $id_subtipo->style = $fundo_campo;

        $indice = new TEntry('indice');
        $indice->style = $fundo_campo;
        $indice->setMaxLength(15);
        $indice->{'placeholder'} = 'ex: 01, 01.01, 01.01.01';

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

        // ── Labels ────────────────────────────────────────────────────────
        $titulo_lbl           = new TLabel('Título');
        $subtitulo_lbl        = new TLabel('Subtítulo');
        $publica_resumo_lbl   = new TLabel('Publicar Resumo');
        $datapostagem_lbl     = new TLabel('Data Postagem');
        $passagem_lbl         = new TLabel('Passagem');
        $id_tema_lbl          = new TLabel('Tema');
        $id_tipo_lbl          = new TLabel('Tipo');
        $id_subtipo_lbl       = new TLabel('Subtipo');
        $indice_lbl           = new TLabel('Índice');
        $id_serie_lbl         = new TLabel('Série');
        $tag_lbl              = new TLabel('Tag');
        $publica_postagem_lbl = new TLabel('Status');

        $id->setProperty('style', 'display: none;');

        // ── setFields ─────────────────────────────────────────────────────
        $this->form->setFields([
            $id, $titulo, $id_serie, $subtitulo, $datapostagem, $passagem,
            $id_tema, $id_tipo, $id_subtipo, $indice, $conteudo, $tag, $resumo,
            $btn_tema, $btn_serie, $btn_tag, $btn_pericope, $publica_postagem, $publica_resumo
        ]);

        // ── Layout ────────────────────────────────────────────────────────
        $dv_geral = new TElement('div');
        $dv_geral->style = 'display: flex; width: 100%;';

        $dv_esquerda = new TElement('div');
        $dv_esquerda->style = 'display: inline-block; flex: 40%';

        $dv_direita = new TElement('div');
        $dv_direita->style = 'display: inline-block; flex: 60%';

        $dv_linha = [];
        for ($i = 1; $i <= 7; $i++) {
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

        // Passagem + botão bíblia
        $dv_passagem = new TElement('div');
        $dv_passagem->style = 'margin-right: 10px; margin-top: 10px; display: inline-block; flex: 25%';
        $dv_passagem->add("<span>$passagem_lbl</span><span><sup>$btn_pericope</sup></span>");
        $dv_passagem->add($passagem);

        $btn_biblia = new TElement('a');
        $btn_biblia->href  = 'javascript:void(0)';
        $btn_biblia->style = 'display: inline-block; margin-top: 4px; font-size: 11px; color: #5a3e1b; text-decoration: none; background: #f0e4d0; border: 1px solid #c8a87a; border-radius: 4px; padding: 2px 8px; cursor: pointer;';
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
                .then(response => { if (!response.ok) throw new Error('Erro: ' + response.statusText); return response.json(); })
                .then(data => {
                    btn.innerHTML = originalText;
                    btn.style.pointerEvents = 'auto';
                    if (data.ok) {
                        var editor = \$('[name=\"conteudo\"]');
                        if (editor.length) {
                            var htmlAtual = editor.summernote('code');
                            htmlAtual = (htmlAtual.indexOf('{{passagem}}') !== -1)
                                ? htmlAtual.replace('{{passagem}}', data.html)
                                : htmlAtual + '<br>' + data.html;
                            editor.summernote('code', htmlAtual);
                            if (typeof TToast !== 'undefined') TToast.show('success', 'Passagem inserida!', 'bottom center', 'far:check-circle');
                        } else { alert('Editor não encontrado.'); }
                    } else { alert('Erro: ' + data.error); }
                })
                .catch(error => { btn.innerHTML = originalText; btn.style.pointerEvents = 'auto'; alert('Falha: ' + error); });
            return true;";
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

        // Índice
        $display_indice = ($tipo == 3) ? 'inline-block' : 'none';
        $dv_indice = new TElement('div');
        $dv_indice->style = "margin-right: 10px; margin-top: 10px; display: {$display_indice}; flex: 15%";
        $dv_indice->add($indice_lbl);
        $dv_indice->add($indice);

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
        $publica_postagem_lbl->style = 'display: block; margin-bottom: 4px;';
        $dv_publica = new TElement('div');
        $dv_publica->style = 'margin-right: 10px; margin-top: 10px; display: inline-block; width: 25%;';
        $dv_publica->add($publica_postagem_lbl);
        $dv_publica->add($publica_postagem);

        // Publicar resumo
        $publica_resumo_lbl->style = 'display: block; margin-bottom: 4px;';
        $display_pub_resumo = ($tipo == 1 || $tipo == 2) ? 'inline-block' : 'none';
        $dv_pub_resumo = new TElement('div');
        $dv_pub_resumo->style = "margin-right: 10px; margin-top: 10px; width: 25%; display: {$display_pub_resumo};";
        $dv_pub_resumo->add($publica_resumo_lbl);
        $dv_pub_resumo->add($publica_resumo);

        if ($tipo != 1 && $tipo != 2) {
            $publica_resumo->setValue(0);
        }

        // ── Monta linhas ──────────────────────────────────────────────────
        $dv_linha[1]->add($dv_titulo);
        $dv_linha[2]->add($dv_subtitulo);
        $dv_linha[3]->add($dv_tema);
        $dv_linha[3]->add($dv_serie);
        $dv_linha[4]->add($dv_postagem);
        $dv_linha[4]->add($dv_passagem);
        $dv_linha[5]->add($dv_tipo);
        $dv_linha[5]->add($dv_subtipo);
        $dv_linha[5]->add($dv_indice);
        $dv_linha[6]->add($dv_tag);
        $dv_linha[7]->add($dv_publica);
        $dv_linha[7]->add($dv_pub_resumo);

        // ── Botões de ação ────────────────────────────────────────────────
        $tamanho_botao = 'width: 25mm;';

        $btn = $this->form->addAction(_t('Save'), new TAction([$this, 'onSave']), 'fa:save white');
        $btn->class = 'btn btn-sm btn-success';
        $btn->style = $tamanho_botao;

        $btn2 = $this->form->addAction('Limpar', new TAction([$this, 'onClear']), 'fa:eraser white');
        $btn2->class = 'btn btn-sm btn-danger';
        $btn2->style = $tamanho_botao;

        $btn3 = $this->form->addAction('Novo', new TAction([$this, 'onClear'], ['registro' => 'novo']), 'fa:plus black');
        $btn3->class = 'btn btn-sm btn-info';
        $btn3->style = $tamanho_botao;

        $id_registro = (int)($param['key'] ?? $param['id'] ?? 0);

        if ($id_registro) {
            $btn_pdf = $this->form->addAction('PDF', new TAction([$this, 'onPdf'], ['id' => $id_registro]), 'fa:file-pdf black');
            $btn_pdf->class = 'btn btn-sm btn-secondary';
            $btn_pdf->style = $tamanho_botao;
        }

        if (!$id_registro) {
            $btn_ia = $this->form->addAction('Gera IA', new TAction([$this, 'onGerarSermao']), 'fa:magic orange');
            $btn_ia->class = 'btn btn-sm btn-warning';
            $btn_ia->style = $tamanho_botao;
        }

        if ($tipo != 4) {
            $btn_resumo = $this->form->addAction('Resumo', new TAction([$this, 'onPreencherResumo']), 'fa:copy blue');
            $btn_resumo->class = 'btn btn-sm btn-primary';
            $btn_resumo->style = $tamanho_botao;
        }

        // ── Botões de navegação anterior / próximo ────────────────────────
        // Aparecem somente quando o formulário é aberto a partir da lista
        $ids      = TSession::getValue('ListaSermoes_ids_lista') ?? [];
        $id_atual = (int)($param['key'] ?? $param['id'] ?? 0);
        $pos      = $id_atual ? array_search($id_atual, $ids) : false;

        if ($ids && $pos !== false) {
            $id_anterior = isset($ids[$pos - 1]) ? $ids[$pos - 1] : null;
            $id_proximo  = isset($ids[$pos + 1]) ? $ids[$pos + 1] : null;
            $tipo_lista  = TSession::getValue('ListaSermoes_tipo_lista') ?? $tipo;
            $total       = count($ids);
            $posicao     = $pos + 1; // base 1 para exibição

            // Barra de navegação
            $dv_nav = new TElement('div');
            $dv_nav->style = 'display: flex; align-items: center; gap: 8px; margin-bottom: 8px; padding: 6px 10px; background: #f0f0f0; border-radius: 8px; width: fit-content;';

            // Botão anterior
            $btn_ant = new TElement('button');
            $btn_ant->type  = 'button';
            $btn_ant->style = 'padding: 4px 12px; border-radius: 6px; border: 1px solid #bbb; background: #fff; cursor: pointer; font-size: 16px; font-weight: bold; transition: background 0.15s;';
            $btn_ant->{'onmouseover'} = "this.style.background='#1a3a2a';this.style.color='#fff'";
            $btn_ant->{'onmouseout'}  = "this.style.background='#fff';this.style.color='inherit'";

            if ($id_anterior) {
                $url_ant = "engine.php?class=FormMeusPosts&method=onEdit&key={$id_anterior}&id={$id_anterior}&tipo={$tipo_lista}";
                $btn_ant->onclick = "__adianti_load_page('{$url_ant}')";
                $btn_ant->add('←');
            } else {
                $btn_ant->style .= ' opacity: 0.3; cursor: not-allowed;';
                $btn_ant->add('←');
            }

            // Indicador de posição
            $span_pos = new TElement('span');
            $span_pos->style = 'font-size: 12px; color: #666; white-space: nowrap;';
            $span_pos->add("{$posicao} de {$total}");

            // Botão próximo
            $btn_prox = new TElement('button');
            $btn_prox->type  = 'button';
            $btn_prox->style = 'padding: 4px 12px; border-radius: 6px; border: 1px solid #bbb; background: #fff; cursor: pointer; font-size: 16px; font-weight: bold; transition: background 0.15s;';
            $btn_prox->{'onmouseover'} = "this.style.background='#1a3a2a';this.style.color='#fff'";
            $btn_prox->{'onmouseout'}  = "this.style.background='#fff';this.style.color='inherit'";

            if ($id_proximo) {
                $url_prox = "engine.php?class=FormMeusPosts&method=onEdit&key={$id_proximo}&id={$id_proximo}&tipo={$tipo_lista}";
                $btn_prox->onclick = "__adianti_load_page('{$url_prox}')";
                $btn_prox->add('→');
            } else {
                $btn_prox->style .= ' opacity: 0.3; cursor: not-allowed;';
                $btn_prox->add('→');
            }

            $dv_nav->add($btn_ant);
            $dv_nav->add($span_pos);
            $dv_nav->add($btn_prox);

            // Insere a barra de navegação como primeira linha
            array_unshift($dv_linha, $dv_nav);
            // Renumera para o loop abaixo funcionar
            $dv_linha = array_values($dv_linha);
        }

        // ── Coluna esquerda ───────────────────────────────────────────────
        foreach ($dv_linha as $linha) {
            $dv_esquerda->add($linha);
        }

        // ── Coluna direita — Notebook ─────────────────────────────────────
        $notebook = new BootstrapNotebookWrapper(new TNotebook(400, 230), 'bordered');
        $notebook->appendPage('Conteúdo', $conteudo);
        if ($tipo != 4) {
            $notebook->appendPage('Resumo', $resumo);
        }
        $dv_direita->add($notebook);

        $dv_geral->add($dv_esquerda);
        $dv_geral->add($dv_direita);
        $this->form->addFields([$dv_geral]);

        $this->onChangeTipo(['id_tipo' => $tipo]);

        $vbox = new TVBox();
        $vbox->style = 'width: 100%';
        $vbox->add($this->form);

        parent::add($vbox);
    }

    // ──────────────────────────────────────────────────────────────────────

    public function onClear($param)
    {
        $this->form->clear();

        $data = new stdClass();
        $data->id_tipo = $param['id_tipo'] ?? 1;

        if ($data->id_tipo == 4) {
            TTransaction::open('sample');
            $ultima = Postagens::where('id_tipo', '=', 4)->orderBy('data_postagem', 'desc')->first();
            if ($ultima) {
                $data->data_postagem = date('d/m/Y', strtotime($ultima->data_postagem . ' +1 day'));
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
                $criteria = TCriteria::create(['id_tipo' => $param['id_tipo']]);
                TDBCombo::reloadFromModel(
                    'form_meus_posts', 'id_subtipo', 'sample',
                    'SubTipoConteudo', 'id', 'descricao', 'id', $criteria, TRUE
                );

                // id_subtipo
                if ($param['id_tipo'] == 2 || $param['id_tipo'] == 4) {
                    TScript::create("document.querySelector('[name=id_subtipo]').closest('div').style.display = 'none';");
                    TScript::create("document.getElementById('id_tipo').style.width = '49.2%';");
                } else {
                    TScript::create("document.querySelector('[name=id_subtipo]').closest('div').style.display = 'inline-block';");
                    TScript::create("document.getElementById('id_tipo').style.width = '100%';");
                }

                // id_serie
                if ($param['id_tipo'] == 3) {
                    TScript::create("document.querySelector('[name=id_serie]').closest('div').style.display = 'none';");
                } else {
                    TScript::create("document.querySelector('[name=id_serie]').closest('div').style.display = 'inline-block';");
                }

                // indice
                if ($param['id_tipo'] == 3) {
                    TScript::create("document.querySelector('[name=indice]').closest('div').style.display = 'inline-block';");
                } else {
                    TScript::create("document.querySelector('[name=indice]').closest('div').style.display = 'none';");
                    TScript::create("document.querySelector('[name=indice]').value = '';");
                }

                // publica_resumo
                if ($param['id_tipo'] == 1 || $param['id_tipo'] == 2) {
                    TScript::create("document.querySelector('[name=publica_resumo]').closest('div').style.display = 'inline-block';");
                } else {
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
                $postagem = new Postagens($param['key']);

                $criteria = new TCriteria();
                $criteria->add(new TFilter('id_tipo', '=', $postagem->id_tipo));

                if ($postagem->id_tipo != 2) {
                    TDBCombo::reloadFromModel(
                        'form_meus_posts', 'id_subtipo', 'sample',
                        'SubTipoConteudo', 'id', 'descricao', 'id', $criteria, TRUE
                    );
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
            new TMessage('error', 'Erro ao carregar: ' . $e->getMessage());
            TTransaction::rollback();
        }
    }

    public function onSave($param)
    {
        try {
            $this->form->validate();

            TTransaction::open('sample');

            $object = $this->form->getData('Postagens');

            $raw = $this->form->getData();
            $object->publica_postagem = isset($raw->publica_postagem) ? (int)$raw->publica_postagem : 0;
            $object->publica_resumo   = isset($raw->publica_resumo)   ? (int)$raw->publica_resumo   : 0;

            $object->store();

            if (!empty($this->afterSaveCallback)) {
                ($this->afterSaveCallback)($object, $this->form->getData());
            }

            TTransaction::close();

            if (isset($this->useMessages) && $this->useMessages === false) {
                AdiantiCoreApplication::loadPageURL($this->afterSaveAction->serialize());
            } else {
                TToast::show('success', 'Registro salvo com sucesso', 'bottom center', 'far:check-circle');
                AdiantiCoreApplication::loadPage(
                    'FormMeusPosts', 'onEdit',
                    ['key' => $object->id, 'id' => $object->id, 'id_tipo' => $object->id_tipo]
                );
            }

            return $object;
        } catch (Exception $e) {
            $this->form->setData($this->form->getData());
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    public function onPdf($param)
    {
        // id já é passado explicitamente na TAction do botão PDF
        $this->form->setData($this->form->getData());
        $this->onGeraPDF($param);
    }

    public function onGerarSermao($param)
    {
        try {
            $data = $this->form->getData();
            set_time_limit(120);

            $titulo     = trim($data->titulo   ?? '');
            $passagem   = trim($data->passagem  ?? '');
            $id_tipo    = $data->id_tipo    ?? null;
            $id_subtipo = $data->id_subtipo ?? null;

            if ($titulo === '' && $passagem === '') {
                throw new Exception('Preencha pelo menos Título ou Passagem para gerar o conteúdo.');
            }

            $tipo_descricao = $subtipo_descricao = '';

            if ($id_tipo) {
                TTransaction::open('sample');
                $tipo_descricao = (new TipoConteudo($id_tipo))->descricao;
                TTransaction::close();
            }
            if ($id_subtipo) {
                TTransaction::open('sample');
                $subtipo_descricao = (new SubTipoConteudo($id_subtipo))->descricao;
                TTransaction::close();
            }

            $resource_file = null;

            if (mb_stripos($tipo_descricao, 'estudo') !== false) {
                $resource_file = 'app/resources/app/cria_estudos_biblicos.md';
            } elseif (mb_stripos($tipo_descricao, 'devocional') !== false) {
                $resource_file = 'app/resources/app/devocional.md';
            } elseif (mb_stripos($tipo_descricao, 'serm') !== false) {
                if (mb_stripos($subtipo_descricao, 'expositivo') !== false || mb_stripos($subtipo_descricao, 'textual') !== false) {
                    $resource_file = 'app/resources/app/sermao_expositivo.md';
                } elseif (mb_stripos($subtipo_descricao, 'tem') !== false) {
                    $resource_file = 'app/resources/app/sermao_tematico.md';
                } elseif (mb_stripos($subtipo_descricao, 'biogr') !== false) {
                    $resource_file = 'app/resources/app/sermao_biografico.md';
                }
            }

            if (!$resource_file) {
                throw new Exception('Tipo/Subtipo não reconhecido para seleção do modelo de prompt.');
            }
            if (!file_exists($resource_file)) {
                throw new Exception("Arquivo não encontrado: {$resource_file}");
            }

            $prompt_base = file_get_contents($resource_file);

            if ($titulo === '' && $passagem !== '') {
                $titulo = trim($this->chamarClaude(
                    "A partir da passagem bíblica abreviada: {$passagem}, proponha um título adequado e fiel à teologia reformada. Devolva apenas o título."
                ));
                $data->titulo = $titulo;
            }

            if ($passagem === '' && $titulo !== '') {
                $passagem = trim($this->chamarClaude(
                    "A partir do título: \"{$titulo}\", sugira uma passagem bíblica no formato abreviado (ex: Jo 3.16). Devolva apenas a referência."
                ));
                $data->passagem = $passagem;
            }

            $instrucao = "Sou calvinista moderado, uso muito material de Russell Shedd, Millard Erickson, Wayne Grudem, Augustus Nicodemos, Hernandes Dias Lopes, Charles Spurgeon, John Piper, John MacArthur Júnior, entre outros de linha reformada. Além de admirar alguns de linha arminiana como Isaltino Gomes Coelho Filho, Max Lucado, A. W. Tozer. Escatologicamente defendo a linha pré-milenista pós-tribulacionista. Mas as afirmações pessoais só devem ser feitas se pedidas ou necessárias para algum contexto específico.\n\n";

            $prompt  = $prompt_base . "\n\n" . $instrucao;
            $prompt .= $tipo_descricao    ? "Tipo de conteúdo: {$tipo_descricao}\n" : '';
            $prompt .= $subtipo_descricao ? "Subtipo: {$subtipo_descricao}\n"        : '';
            $prompt .= $titulo            ? "Título: {$titulo}\n"                    : '';
            $prompt .= $passagem          ? "Passagem bíblica: {$passagem}\n"        : '';

            $gerado = $this->chamarClaude($prompt);
            $data->conteudo = $this->extrairSermonBlock($gerado) ?? $gerado;
            $this->form->setData($data);

            TToast::show('success', 'Conteúdo gerado. Revise antes de salvar.', 'bottom center', 'far:check-circle');
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
            $titulo   = trim($data->titulo   ?? '');

            if ($conteudo === '') {
                throw new Exception('Preencha o campo Conteúdo antes de gerar o resumo.');
            }
            if ($titulo === '') {
                throw new Exception('O campo Título é necessário para gerar o resumo.');
            }

            $caminho_md = 'app/resources/app/resumos.md';
            if (!is_readable($caminho_md)) {
                throw new Exception('Arquivo de prompt não encontrado: ' . $caminho_md);
            }

            $prompt = file_get_contents($caminho_md)
                . "\n\n----- TEXTO A SER RESUMIDO -----\n"
                . "TÍTULO: {$titulo}\n\n{$conteudo}\n"
                . "----- FIM DO TEXTO -----\n";

            $gerado = $this->chamarClaude($prompt);
            $data->resumo = $this->extrairSermonBlock($gerado) ?? $gerado;
            $this->form->setData($data);

            TToast::show('success', 'Resumo gerado. Revise antes de salvar.', 'bottom center', 'far:check-circle');
        } catch (Exception $e) {
            $this->form->setData($this->form->getData());
            new TMessage('error', $e->getMessage());
        }
    }

    public function onAddTag($param)
    {
        try {
            set_time_limit(120);

            $data     = $this->form->getData();
            $conteudo = trim($data->conteudo ?? '');
            $titulo   = trim($data->titulo   ?? '');

            if ($conteudo === '') {
                throw new Exception('Preencha o Conteúdo antes de gerar palavras-chave.');
            }
            if ($titulo === '') {
                throw new Exception('O campo Título é necessário para gerar palavras-chave.');
            }

            $caminho_md = 'app/resources/app/palavras_chaves.md';
            if (!is_readable($caminho_md)) {
                throw new Exception('Arquivo de prompt não encontrado: ' . $caminho_md);
            }

            $prompt = file_get_contents($caminho_md)
                . "\n\n----- TEXTO A SER ANALISADO -----\n"
                . "TÍTULO: {$titulo}\n\n{$conteudo}\n"
                . "----- FIM DO TEXTO -----\n";

            $data->tags = $this->chamarClaude($prompt);
            $this->form->setData($data);

            TToast::show('info', 'Palavras-chave geradas. Revise antes de salvar.', 'bottom center', 'far:check-circle');
        } catch (Exception $e) {
            $this->form->setData($this->form->getData());
            new TMessage('error', $e->getMessage());
        }
    }

    protected function extrairSermonBlock(?string $html): ?string
    {
        if (empty($html)) return null;
        if (preg_match('/<div\s+class=("|\')sermon-block("|\')(.*?)<\/div>/is', $html, $matches)) {
            return $matches[0];
        }
        return null;
    }
}