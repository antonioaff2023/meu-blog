<?php

use Adianti\Database\TExpression;
use Adianti\Widget\Form\THidden;
use App\Traits\MinhaTrait;

/**
 * ListaSermoes
 *
 * Lista unificada para todos os tipos de postagem:
 *   1 = Sermão | 2 = Estudo Bíblico | 3 = Teologia Sistemática | 4 = Devocional
 *
 * Salva na sessão a lista de IDs carregados para permitir navegação
 * anterior/próximo no FormMeusPosts sem retornar ao filtro.
 *
 * @version    8.4
 * @author     Antonio Affonso
 */
class ListaSermoes extends TStandardList
{
    protected $form;
    protected $datagrid;
    protected $pageNavigation;
    protected $formgrid;
    protected $deleteButton;
    protected $transformCallback;

    use app\MeuTrait;

    public function __construct($param = null)
    {
        parent::__construct();

        parent::setDatabase('sample');
        parent::setActiveRecord('Postagens');

        $tipo = (int)($param['id_tipo'] ?? 1);

        if ($tipo == 3) {
            parent::setDefaultOrder('id_subtipo', 'asc');
        } else {
            parent::setDefaultOrder('data_postagem', 'desc');
        }

        // ── Títulos por tipo ──────────────────────────────────────────────
        $titulos = [
            1 => ['form' => 'Pesquisa de Sermões',              'panel' => 'Sermões'],
            2 => ['form' => 'Pesquisa de Estudos Bíblicos',     'panel' => 'Estudos Bíblicos'],
            3 => ['form' => 'Pesquisa de Teologia Sistemática', 'panel' => 'Teologia Sistemática'],
            4 => ['form' => 'Pesquisa de Devocionais',          'panel' => 'Devocionais'],
        ];
        $tituloform  = $titulos[$tipo]['form']  ?? 'Pesquisa';
        $titulopanel = $titulos[$tipo]['panel'] ?? 'Postagens';

        // ── Formulário de pesquisa ────────────────────────────────────────
        $this->form = new BootstrapFormBuilder('form_search_Sermoes');
        $this->form->setFormTitle($tituloform);

        $id      = new THidden('id');
        $id_tipo = new THidden('id_tipo');
        $id_tipo->setValue($tipo);

        $titulo  = new TEntry('titulo');
        $data_postagem = new TEntry('data_postagem');
        $data_postagem->{'placeholder'} = 'ex: 2025, 04/2025, 10/04/2025';
        $passagem = new TEntry('passagem');

        $fields = [$id, $id_tipo, $titulo, $data_postagem, $passagem];

        // ── Linha de filtros ──────────────────────────────────────────────
        $dv_linha = new TElement('div');
        $dv_linha->style = 'display: flex; width: 100%; flex-wrap: wrap;';

        $dv_titulo = new TElement('div');
        $dv_titulo->style = 'margin-right: 10px; margin-top: 10px; display: inline-block; width: 20%;';
        $dv_titulo->add(new TLabel('Pesquisar por título, subtítulo ou tag'));
        $dv_titulo->add($titulo);

        $dv_data = new TElement('div');
        $dv_data->style = 'margin-right: 10px; margin-top: 10px; display: inline-block; width: 10%';
        $dv_data->add(new TLabel('Data'));
        $dv_data->add($data_postagem);

        $dv_passagem = new TElement('div');
        $dv_passagem->style = 'margin-right: 10px; margin-top: 10px; display: inline-block; width: 10%';
        $dv_passagem->add(new TLabel('Passagem'));
        $dv_passagem->add($passagem);

        $dv_linha->add($dv_titulo);
        $dv_linha->add($dv_data);
        $dv_linha->add($dv_passagem);

        // Série — tipos 1 e 2
        if ($tipo == 1 || $tipo == 2) {
            $criteria_serie = new TCriteria;
            $criteria_serie->add(new TFilter(
                'id', 'in',
                "(SELECT DISTINCT id_serie FROM tbl_postagem WHERE id_tipo = {$tipo} AND id_serie IS NOT NULL)"
            ));
            $serie = new TDBCombo('id_serie', 'sample', 'Series', 'id', 'nome', 'nome', $criteria_serie);
            $serie->setSize('100%');
            $serie->enableSearch();
            $serie->setChangeAction(new TAction([$this, 'onSearch'], ['id_tipo' => $tipo, 'static' => 1]));

            $dv_serie = new TElement('div');
            $dv_serie->style = 'margin-right: 10px; margin-top: 10px; display: inline-block; width: 20%';
            $dv_serie->add(new TLabel('Série'));
            $dv_serie->add($serie);
            $dv_linha->add($dv_serie);
            $fields[] = $serie;
        }

        // Subtipo — tipos 1 e 3
        if ($tipo == 1 || $tipo == 3) {
            $criteria_subtipo = new TCriteria;
            $criteria_subtipo->add(new TFilter('id_tipo', '=', $tipo));
            $subtipo = new TDBCombo('id_subtipo', 'sample', 'SubTipoConteudo', 'id', 'descricao', 'descricao', $criteria_subtipo);
            $subtipo->setSize('100%');
            $subtipo->enableSearch();
            if ($tipo == 3) {
                $subtipo->setChangeAction(new TAction([$this, 'onSearch'], ['id_tipo' => $tipo, 'static' => 1]));
            }

            $dv_subtipo = new TElement('div');
            $dv_subtipo->style = 'margin-right: 10px; margin-top: 10px; display: inline-block; width: 20%';
            $dv_subtipo->add(new TLabel($tipo == 3 ? 'Área' : 'Tipo'));
            $dv_subtipo->add($subtipo);
            $dv_linha->add($dv_subtipo);
            $fields[] = $subtipo;
        }

        $this->form->setFields($fields);
        $this->form->addFields([$dv_linha]);

        // ── Botões ────────────────────────────────────────────────────────
        $estilo_btn = 'color: black; width: 100px;';

        $btn = $this->form->addAction('Pesquisar', new TAction([$this, 'onSearch'], ['id_tipo' => $tipo]), 'fa:search black');
        $btn->class = 'btn btn-sm btn-primary';
        $btn->style = $estilo_btn;

        $btn = $this->form->addAction('Limpar', new TAction([$this, 'onLimpa'], ['id_tipo' => $tipo]), 'fa:eraser black');
        $btn->class = 'btn btn-sm btn-danger';
        $btn->style = $estilo_btn;

        $btn = $this->form->addAction('Novo', new TAction(['FormMeusPosts', 'onEdit'], ['tipo' => $tipo]), 'fa:plus black');
        $btn->class = 'btn btn-sm btn-success';
        $btn->style = $estilo_btn;

        // ── Datagrid ──────────────────────────────────────────────────────
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = 'width: 100%';
        $this->datagrid->setHeight(320);

        $col_id = new TDataGridColumn('id', 'ID', 'center', 50);
        $col_id->setVisibility(false);

        if ($tipo == 2) {
            $col_serie = new TDataGridColumn('serie->nome', 'Série', 'left');
            $this->datagrid->addColumn($col_id);
            $this->datagrid->addColumn($col_serie);
        } else {
            $this->datagrid->addColumn($col_id);
        }

        if ($tipo == 3) {
            $col_subtipo_area = new TDataGridColumn('subtipo->descricao', 'Área', 'left', '20%');
            $col_indice       = new TDataGridColumn('indice', 'Índice', 'center', '8%');
            $this->datagrid->addColumn($col_subtipo_area);
            $this->datagrid->addColumn($col_indice);
        } else {
            $col_passagem = new TDataGridColumn('passagem', 'Passagem', 'left', '10%');
            $this->datagrid->addColumn($col_passagem);
        }

        $col_titulo = new TDataGridColumn('titulo', 'Título', 'left', '25%');
        $col_titulo->setTransformer(fn($v) => strtoupper($v));

        $col_data_postagem = new TDataGridColumn('data_postagem', 'Data', 'center', '10%');
        $col_data_postagem->setTransformer(function ($value) {
            if ($value) {
                $date = new DateTime($value);
                $dow  = $date->format('w');
                $fmt  = $date->format('d/m/Y');
                if ($dow == 0) return "<span style='color:red;'>{$fmt}</span>";
                if ($dow == 6) return "<span style='color:blue;'>{$fmt}</span>";
                return $fmt;
            }
            return '';
        });

        $col_tags = new TDataGridColumn('tags', 'Tags', 'left');

        if ($tipo == 1) {
            $col_subtipo = new TDataGridColumn('subtipo->descricao', 'Tipo', 'left', '5%');
            $this->datagrid->addColumn($col_titulo);
            $this->datagrid->addColumn($col_subtipo);
            $this->datagrid->addColumn($col_data_postagem);
            $this->datagrid->addColumn($col_tags);
        } else {
            $this->datagrid->addColumn($col_titulo);
            $this->datagrid->addColumn($col_data_postagem);
            $this->datagrid->addColumn($col_tags);
        }

        // ── Ordenação das colunas ─────────────────────────────────────────
        $order_titulo = new TAction([$this, 'onReload'], ['id_tipo' => $tipo]);
        $order_titulo->setParameter('order', 'titulo');
        $col_titulo->setAction($order_titulo);

        $order_data = new TAction([$this, 'onReload'], ['id_tipo' => $tipo]);
        $order_data->setParameter('order', 'data_postagem');
        $col_data_postagem->setAction($order_data);

        if ($tipo == 3) {
            $order_area = new TAction([$this, 'onReload'], ['id_tipo' => $tipo]);
            $order_area->setParameter('order', 'id_subtipo');
            $col_subtipo_area->setAction($order_area);

            $order_indice = new TAction([$this, 'onReload'], ['id_tipo' => $tipo]);
            $order_indice->setParameter('order', 'indice');
            $col_indice->setAction($order_indice);
        }

        if ($tipo == 1) {
            $order_subtipo = new TAction([$this, 'onReload'], ['id_tipo' => $tipo]);
            $order_subtipo->setParameter('order', 'subtipo');
            $col_subtipo->setAction($order_subtipo);
        }

        // ── Ações do datagrid ─────────────────────────────────────────────
        $action1 = new TDataGridAction(['FormMeusPosts', 'onEdit'], ['id' => '{id}', 'tipo' => $tipo]);
        $action2 = new TDataGridAction([$this, 'onDelete'],  ['id' => '{id}', 'id_tipo' => $tipo]);
        $action3 = new TDataGridAction([$this, 'onGeraPDF'], ['id' => '{id}']);

        $this->datagrid->addAction($action1, 'Editar',     'fa:pen-to-square blue');
        $this->datagrid->addAction($action2, 'Apagar',     'far:trash-alt red');
        $this->datagrid->addAction($action3, 'Visualizar', 'fa:eye green');

        $this->datagrid->createModel();

        // ── Navegação ─────────────────────────────────────────────────────
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->setAction(new TAction([$this, 'onReload'], ['id_tipo' => $tipo]));
        $this->pageNavigation->setWidth('100%');
        $this->pageNavigation->enableCounters();

        // ── Layout ────────────────────────────────────────────────────────
        $panel = new TPanelGroup($titulopanel);
        $panel->add($this->datagrid);
        $panel->addFooter($this->pageNavigation);

        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add($this->form);
        $container->add($panel);

        parent::add($container);
    }

    public function onReload($param = null)
    {
        try {
            TTransaction::open('sample');

            $repository = new TRepository('Postagens');

            if (isset($param['id_tipo'])) {
                TSession::setValue(__CLASS__ . '_id_tipo', $param['id_tipo']);
            }
            $id_tipo = TSession::getValue(__CLASS__ . '_id_tipo') ?? 1;

            $limit = ($id_tipo == 4) ? 7 : 4;

            $criteria = new TCriteria;
            if ($id_tipo) {
                $criteria->add(new TFilter('id_tipo', '=', $id_tipo));
            }

            $data = TSession::getValue(__CLASS__ . '_search_data');
            if ($data) {
                if (!empty($data->id_serie)) {
                    $criteria->add(new TFilter('id_serie', '=', $data->id_serie));
                }
                if (!empty($data->id_subtipo)) {
                    $criteria->add(new TFilter('id_subtipo', '=', $data->id_subtipo));
                }
                if (!empty($data->titulo)) {
                    $criteria_or = new TCriteria;
                    $criteria_or->add(new TFilter('titulo',    'like', "%{$data->titulo}%"));
                    $criteria_or->add(new TFilter('subtitulo', 'like', "%{$data->titulo}%"), TExpression::OR_OPERATOR);
                    $criteria_or->add(new TFilter('tags',      'like', "%{$data->titulo}%"), TExpression::OR_OPERATOR);
                    $criteria->add($criteria_or);
                }
                if (!empty($data->passagem)) {
                    $criteria->add(new TFilter('passagem', 'like', "%{$data->passagem}%"));
                }
                if (!empty($data->data_postagem)) {
                    $termo = trim($data->data_postagem);

                    // Detecta o formato digitado e converte para padrão do banco (yyyy-mm-dd)
                    if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $termo)) {
                        // dd/mm/yyyy → yyyy-mm-dd (data completa, busca exata)
                        $partes = explode('/', $termo);
                        $valor  = "{$partes[2]}-{$partes[1]}-{$partes[0]}";
                        $criteria->add(new TFilter('data_postagem', '=', $valor));

                    } elseif (preg_match('/^\d{2}\/\d{4}$/', $termo)) {
                        // mm/yyyy → busca por ano-mês com LIKE
                        $partes = explode('/', $termo);
                        $valor  = "{$partes[1]}-{$partes[0]}";
                        $criteria->add(new TFilter('data_postagem', 'like', "{$valor}%"));

                    } elseif (preg_match('/^\d{4}$/', $termo)) {
                        // yyyy → busca por ano com LIKE
                        $criteria->add(new TFilter('data_postagem', 'like', "{$termo}%"));
                    }
                }
            }

            $criteria->setProperties($param);
            $criteria->setProperty('limit', $limit);

            if (empty($criteria->getProperty('order'))) {
                if ($id_tipo == 3) {
                    $criteria->setProperty('order', 'id_subtipo');
                    $criteria->setProperty('direction', 'asc');
                } else {
                    $criteria->setProperty('order', $this->order);
                    $criteria->setProperty('direction', $this->direction);
                }
            }

            $objects = $repository->load($criteria);

            $this->datagrid->clear();
            if ($objects) {
                foreach ($objects as $object) {
                    $this->datagrid->addItem($object);
                }
            }

            // ── Salva TODOS os IDs (sem limit) para navegação anterior/próximo ──
            // Reutiliza os mesmos filtros e ordenação, mas sem paginação
            $criteria_nav = clone $criteria;
            $criteria_nav->resetProperties();

            // Reaplica ordenação
            $order     = $criteria->getProperty('order')     ?? ($id_tipo == 3 ? 'id_subtipo' : $this->order);
            $direction = $criteria->getProperty('direction') ?? ($id_tipo == 3 ? 'asc'        : $this->direction);
            $criteria_nav->setProperty('order',     $order);
            $criteria_nav->setProperty('direction', $direction);

            $todos = $repository->load($criteria_nav, FALSE);
            $ids   = $todos ? array_map(fn($o) => (int)$o->id, $todos) : [];

            TSession::setValue('ListaSermoes_ids_lista',  $ids);
            TSession::setValue('ListaSermoes_tipo_lista', $id_tipo);

            // ── Contagem total ────────────────────────────────────────────
            $criteria->resetProperties();
            if ($id_tipo) {
                $criteria->add(new TFilter('id_tipo', '=', $id_tipo));
            }
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
        TSession::setValue(__CLASS__ . '_search_data', $data);
        $this->onReload($param);
    }

    public function onLimpa($param = null)
    {
        $this->form->clear();
        TSession::setValue(__CLASS__ . '_search_data', null);
        $this->onReload($param);
    }
}