<?php

/**
 * InicioPublicoView
 *
 * Página pública principal com abas por tipo:
 *   1 = Sermão | 2 = Estudo Bíblico | 3 = Teologia Sistemática | 4 = Devocional
 *
 * Regras de exibição:
 *   - Apenas postagens com publica_postagem = '1' e data_postagem <= hoje
 *   - Contador da aba = total real do banco (mesmos filtros)
 *   - Tela inicial: 4 mais recentes por aba; busca: todos
 *
 * IMPORTANTE: usa uma única conexão PDO aberta no __construct para evitar
 * conflito de TTransaction entre os métodos.
 *
 * @author Antonio Affonso
 */
class InicioPublicoView extends TPage
{
    use app\MeuTrait;

    public function __construct()
    {
        parent::__construct();

        // ── Uma única transação para todas as queries ──────────────────────
        TTransaction::open('sample');
        $conn = TTransaction::get();
        $hoje = date('Y-m-d');

        // Subtipos
        $subtipos = self::querySubtipos($conn);

        // Postagens por tipo
        $sermoes     = self::queryPostagens($conn, 1, $hoje);
        $estudos     = self::queryPostagens($conn, 2, $hoje);
        $teologia    = self::queryPostagens($conn, 3, $hoje);
        $devocionais = self::queryPostagens($conn, 4, $hoje);

        TTransaction::close();
        // ──────────────────────────────────────────────────────────────────

        $total_sermoes     = count($sermoes);
        $total_estudos     = count($estudos);
        $total_teologia    = count($teologia);
        $total_devocionais = count($devocionais);

        // ── Tabs ──────────────────────────────────────────────────────────
        $tabs_html = "
        <div> São exibidos apenas os quatro mais recentes de cada tipo. Mas você pode ver outros digitando no campo de busca.</div>
        <div class='ts-tabs-wrapper' role='tablist'>
            <button class='ts-tab-btn ativo' role='tab' aria-selected='true'
                    aria-controls='panel-sermoes' onclick='tsTab(\"sermoes\",this)'>
                Sermões <span class='ts-tab-count'>{$total_sermoes}</span>
            </button>
            <button class='ts-tab-btn' role='tab' aria-selected='false'
                    aria-controls='panel-estudos' onclick='tsTab(\"estudos\",this)'>
                Estudos Bíblicos <span class='ts-tab-count'>{$total_estudos}</span>
            </button>
            <button class='ts-tab-btn' role='tab' aria-selected='false'
                    aria-controls='panel-teologia' onclick='tsTab(\"teologia\",this)'>
                Teologia Sistemática <span class='ts-tab-count'>{$total_teologia}</span>
            </button>
            <button class='ts-tab-btn' role='tab' aria-selected='false'
                    aria-controls='panel-devocionais' onclick='tsTab(\"devocionais\",this)'>
                Devocionais <span class='ts-tab-count'>{$total_devocionais}</span>
            </button>
        </div>";

        // ── Painéis ────────────────────────────────────────────────────────
        $painel_sermoes     = self::montarPainel('sermoes',     $sermoes,     1, $subtipos);
        $painel_estudos     = self::montarPainel('estudos',     $estudos,     2, $subtipos);
        $painel_teologia    = self::montarPainel('teologia',    $teologia,    3, $subtipos);
        $painel_devocionais = self::montarPainel('devocionais', $devocionais, 4, $subtipos);

        // ── Script abas + busca ───────────────────────────────────────────
        $script = "
        <script>
        function tsSalvaEstado(panelId, termo) {
            try {
                sessionStorage.setItem(TS_STATE_KEY, JSON.stringify({ aba: panelId, termo: termo }));
            } catch(e) {}
        }

        function tsTab(id, btn) {
            document.querySelectorAll('.ts-tab-btn').forEach(function(b){
                b.classList.remove('ativo');
                b.setAttribute('aria-selected', 'false');
            });
            document.querySelectorAll('.ts-panel').forEach(function(p){
                p.classList.remove('ativo');
            });
            btn.classList.add('ativo');
            btn.setAttribute('aria-selected', 'true');
            document.getElementById('panel-' + id).classList.add('ativo');
            var input = document.querySelector('#panel-' + id + ' .ts-busca-input');
            if (input) input.value = '';
            tsAplicaFiltro(id, '');
            tsSalvaEstado(id, '');
        }

        function tsBusca(input, panelId) {
            var termo = input.value.toLowerCase().trim();
            tsAplicaFiltro(panelId, termo);
            tsSalvaEstado(panelId, termo);
        }
        </script>";

        // ── Monta container ────────────────────────────────────────────────
        $wrapper = new TElement('div');
        $wrapper->id = 'ts-public-root';
        $wrapper->add($tabs_html);
        $wrapper->add($painel_sermoes);
        $wrapper->add($painel_estudos);
        $wrapper->add($painel_teologia);
        $wrapper->add($painel_devocionais);
        $wrapper->add($script);

        $container = new TVBox;
        $container->style = 'width: 100%; padding: 0;';
        $container->add($wrapper);

        parent::add($container);
    }

    // ──────────────────────────────────────────────────────────────────────
    // MÉTODO PÚBLICO ESTÁTICO
    // ──────────────────────────────────────────────────────────────────────

    /**
     * Retorna o HTML do devocional do dia para o hero.
     * Chamado pelo index.php: str_replace('{DEVOCIONAL}', InicioPublicoView::renderDevocionalHero(), $content)
     */
    public static function renderDevocionalHero(): string
    {
        try {
            TTransaction::open('sample');
            $conn = TTransaction::get();
            $hoje = date('Y-m-d');
            $rows = self::queryPostagens($conn, 4, $hoje, 1);
            TTransaction::close();
            return self::montarDevocionalHero($rows[0] ?? null);
        } catch (Exception $e) {
            try {
                TTransaction::rollback();
            } catch (Exception $ex) {
            }
            return '';
        }
    }

    // ──────────────────────────────────────────────────────────────────────
    // QUERIES PDO — sem TTransaction aninhado
    // ──────────────────────────────────────────────────────────────────────

    /**
     * Busca postagens públicas de um tipo.
     * Filtro: publica_postagem = b'1' AND data_postagem <= hoje.
     * Retorna array de arrays associativos.
     */
    private static function queryPostagens(PDO $conn, int $tipo, string $hoje, int $limite = 99999): array
    {
        $stmt = $conn->prepare("
            SELECT id, id_tipo, id_subtipo, titulo, passagem,
                   data_postagem, resumo, publica_resumo, tags
            FROM   tbl_postagem
            WHERE  id_tipo          = :tipo
              AND  data_postagem    <= :hoje
              AND  publica_postagem = '1'
            ORDER  BY data_postagem DESC
            LIMIT  :limite
        ");
        $stmt->bindValue(':tipo',   $tipo,   PDO::PARAM_INT);
        $stmt->bindValue(':hoje',   $hoje,   PDO::PARAM_STR);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Carrega todos os subtipos em um único SELECT.
     * Tenta os nomes de tabela mais comuns; se falhar retorna array vazio.
     * Retorna array [id => descricao].
     */
    private static function querySubtipos(PDO $conn): array
    {
        $mapa    = [];
        $tabelas = ['tbl_sub_tipo', 'tbl_subtipo', 'sub_tipo', 'postagens_tipo'];
        foreach ($tabelas as $tabela) {
            try {
                $stmt = $conn->query("SELECT id, descricao FROM {$tabela}");
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $mapa[(int)$row['id']] = $row['descricao'];
                }
                break;
            } catch (Exception $e) {
                // tenta o próximo nome
            }
        }
        return $mapa;
    }

    // ──────────────────────────────────────────────────────────────────────
    // MONTAGEM HTML
    // ──────────────────────────────────────────────────────────────────────

    /**
     * Monta o card do devocional do dia exibido no hero — clicável.
     */
    private static function montarDevocionalHero(?array $post): string
    {
        if (!$post) {
            return '';
        }

        $id       = (int)$post['id'];
        $url      = "index.php?class=InicioPublicoView&method=onVerPost&id={$id}";
        $titulo   = htmlspecialchars($post['titulo']   ?? '');
        $passagem = htmlspecialchars($post['passagem'] ?? '');

        $publica_resumo = $post['publica_resumo'] ?? null;
        $exibe_resumo   = ($publica_resumo === "\x01" || $publica_resumo === '1' || $publica_resumo === 1);
        $fonte          = ($exibe_resumo && !empty($post['resumo'])) ? $post['resumo'] : ($post['conteudo'] ?? '');
        $verso          = trim(preg_replace('/\s+/', ' ', strip_tags($fonte)));
        if (mb_strlen($verso) > 200) {
            $corte = mb_strrpos(mb_substr($verso, 0, 200), ' ');
            $verso = mb_substr($verso, 0, $corte ?: 200) . '…';
        }
        $verso = htmlspecialchars($verso);

        return "
        <div class='devocional-card'>
            <div class='devocional-icone'>✦</div>
            <div>
                <div class='devocional-label'>Devocional do dia</div>
                <a href='{$url}' class='devocional-titulo-link'>{$titulo}</a>
                <p class='devocional-verso'>{$verso}</p>
                " . ($passagem ? "<p class='devocional-ref'>{$passagem}</p>" : "") . "
            </div>
        </div>";
    }

    /**
     * Monta o painel HTML de cards de um tipo.
     */
    private static function montarPainel(
        string $id,
        array  $postagens,
        int    $tipo,
        array  $subtipos
    ): string {
        $ativo = ($id === 'sermoes') ? 'ativo' : '';

        if (empty($postagens)) {
            return "
            <div id='panel-{$id}' class='ts-panel {$ativo}' role='tabpanel'>
                <div class='ts-empty'>Nenhum conteúdo publicado ainda.</div>
            </div>";
        }

        $placeholders = [
            1 => 'Buscar por título, passagem, assunto ou data...',
            2 => 'Buscar por título ou tema...',
            3 => 'Buscar por título ou subtipo...',
            4 => 'Buscar por título ou data...',
        ];
        $placeholder = $placeholders[$tipo] ?? 'Buscar...';

        $textos_ajuda = [
            1 => [
                'titulo' => 'Como buscar sermões',
                'texto'  => '
            <p>Você pode buscar sermões por diferentes critérios:</p>
            <ul>
                <li><strong>Título</strong> — ex.: <em>graça</em>, <em>fé</em></li>
                <li><strong>Passagem bíblica</strong> — ex.: <em>Sempre use as siglas, ex.: Jo 3</em>, <em>Rm 8</em></li>
                <li><strong>Assunto ou série</strong> — ex.: <em>família</em>, <em>salvação</em></li>
                <li><strong>Data completa</strong> — ex.: <em>10/04/2025</em></li>
                <li><strong>Mês e ano</strong> — ex.: <em>04/2025</em></li>
                <li><strong>Apenas o ano</strong> — ex.: <em>2025</em></li>
            </ul>
            <p>Por padrão são exibidos os 4 mais recentes. Ao digitar qualquer termo, todos os sermões que correspondem aparecem automaticamente.</p>
        ',
            ],
            2 => [
                'titulo' => 'Como buscar estudos bíblicos',
                'texto'  => '
            <p>Você pode buscar estudos por:</p>
            <ul>
                <li><strong>Título do estudo</strong> — ex.: <em>reino de Deus</em></li>
                <li><strong>Tema ou assunto</strong> — ex.: <em>oração</em>, <em>discipulado</em></li>
                <li><strong>Passagem bíblica</strong> — ex.: <em>Sempre use as siglas, ex.: Jo 3</em>, <em>Rm 8</em></li>
                <li><strong>Data</strong> — ex.: <em>2024</em> ou <em>03/2024</em></li>
            </ul>
            <p>Por padrão são exibidos os 4 mais recentes. A busca percorre todos os estudos publicados.</p>
        ',
            ],
            3 => [
                'titulo' => 'Como buscar artigos de teologia',
                'texto'  => '
            <p>Os artigos de Teologia Sistemática podem ser buscados por:</p>
            <ul>
                <li><strong>Título</strong> — ex.: <em>trindade</em>, <em>eleição</em></li>
                <li><strong>Subtipo / área temática</strong> — ex.: <em>soteriologia</em>, <em>escatologia</em>, <em>cristologia</em></li>
                <li><strong>Data</strong> — ex.: <em>2023</em></li>
            </ul>
            <p>Por padrão são exibidos os 4 mais recentes. Digite qualquer termo para expandir a listagem.</p>
        ',
            ],
            4 => [
                'titulo' => 'Como buscar devocionais',
                'texto'  => '
            <p>Você pode buscar devocionais por:</p>
            <ul>
                <li><strong>Título, assunto ou tema</strong> — ex.: <em>misericórdia</em>, <em>paz</em></li>
                <li><strong>Passagem bíblica</strong> — ex.: <em>Sempre use as siglas, ex.: Jo 3</em>, <em>Rm 8</em></li>
                <li><strong>Data completa</strong> — ex.: <em>15/01/2025</em></li>
                <li><strong>Mês e ano</strong> — ex.: <em>01/2025</em></li>
                <li><strong>Apenas o ano</strong> — ex.: <em>2025</em></li>
            </ul>
            <p>O devocional mais recente também aparece em destaque no topo da página principal.</p>
        ',
            ],
        ];

        $ajuda        = $textos_ajuda[$tipo];
        $modal_id     = "ts-modal-busca-{$id}";
        $titulo_ajuda = htmlspecialchars($ajuda['titulo']);
        $texto_ajuda  = $ajuda['texto'];

        $busca_html = "
                        <!-- Modal de ajuda da busca -->
                        <div id='{$modal_id}'
                            style='display:none; position:fixed; inset:0; z-index:2000;
                                background:rgba(0,0,0,0.5); align-items:center; justify-content:center; padding:24px;'
                            onclick='if(event.target===this)this.style.display=\"none\"'>
                            <div style='background:#fff; border-radius:14px; max-width:480px; width:100%;
                                        box-shadow:0 20px 60px rgba(0,0,0,0.25); font-family:\"DM Sans\",sans-serif;'>
                                <div style='background:#0f2316; border-radius:14px 14px 0 0;
                                            padding:16px 20px; display:flex; align-items:center; justify-content:space-between;'>
                                    <span style='font-family:\"Playfair Display\",serif; font-size:16px;
                                                color:#f0ebe0; font-weight:600;'>{$titulo_ajuda}</span>
                                    <button onclick='document.getElementById(\"{$modal_id}\").style.display=\"none\"'
                                        style='background:none; border:none; color:rgba(240,235,224,0.6);
                                            font-size:20px; cursor:pointer; line-height:1; padding:0 4px;'
                                        onmouseover='this.style.color=\"#f0ebe0\"'
                                        onmouseout='this.style.color=\"rgba(240,235,224,0.6)\"'>✕</button>
                                </div>
                                <div style='padding:22px 24px 10px; font-size:13px; color:#333; line-height:1.7;'>
                                    {$texto_ajuda}
                                </div>
                                <div style='padding:12px 24px 18px; text-align:right;'>
                                    <button onclick='document.getElementById(\"{$modal_id}\").style.display=\"none\"'
                                        style='background:#0f2316; color:#f0ebe0; border:none; border-radius:8px;
                                            padding:8px 20px; font-family:\"DM Sans\",sans-serif; font-size:13px;
                                            font-weight:600; cursor:pointer;'
                                        onmouseover='this.style.opacity=\"0.85\"'
                                        onmouseout='this.style.opacity=\"1\"'>Entendi</button>
                                </div>
                            </div>
                        </div>

                        <div class='ts-busca-wrapper'>
                            <div class='ts-busca-inner' style='display:flex; align-items:center; gap:8px; max-width:460px;'>
                                <div style='position:relative; flex:1;'>
                                    <svg class='ts-busca-icon' viewBox='0 0 16 16' fill='none' xmlns='http://www.w3.org/2000/svg'>
                                        <circle cx='6.5' cy='6.5' r='5' stroke='currentColor' stroke-width='1.5'/>
                                        <path d='M10.5 10.5L14 14' stroke='currentColor' stroke-width='1.5' stroke-linecap='round'/>
                                    </svg>
                                    <input
                                        type='search'
                                        class='ts-busca-input'
                                        placeholder='{$placeholder}'
                                        oninput='tsBusca(this, &quot;{$id}&quot;)'
                                        autocomplete='off'
                                    >
                                </div>
                                <button
                                    onclick='document.getElementById(\"{$modal_id}\").style.display=\"flex\"'
                                    title='Como buscar'
                                    style='flex-shrink:0; width:32px; height:32px; border-radius:50%;
                                        background:#ece9e3; border:0.5px solid #dedad4; cursor:pointer;
                                        display:flex; align-items:center; justify-content:center;
                                        font-family:\"DM Sans\",sans-serif; font-size:14px; font-weight:700;
                                        color:#666; transition:background 0.15s, color 0.15s;'
                                    onmouseover='this.style.background=\"#1a3a2a\";this.style.color=\"#f0ebe0\"'
                                    onmouseout='this.style.background=\"#ece9e3\";this.style.color=\"#666\"'>
                                    ?
                                </button>
                            </div>
                        </div>";
        $cards = '';
        foreach ($postagens as $idx => $post) {
            $cards .= self::montarCard($post, $tipo, $subtipos, $idx);
        }

        return "
        <div id='panel-{$id}' class='ts-panel {$ativo}' role='tabpanel'>
            {$busca_html}
            <div class='ts-grid'>
                {$cards}
            </div>
            <div id='ts-sem-resultado-{$id}' class='ts-empty' style='display:none;'>
                Nenhum resultado encontrado para sua busca.
            </div>
        </div>";
    }

    /**
     * Monta um card individual.
     * Recebe array associativo (linha PDO) em vez de objeto.
     */
    private static function montarCard(array $post, int $tipo, array $subtipos, int $idx = 0): string
    {
        // ── Data formatada ──
        $data_fmt     = '';
        $data_br      = '';
        $data_mes_ano = '';
        $data_ano     = '';
        try {
            $dt           = new DateTime($post['data_postagem']);
            $data_fmt     = $dt->format('d/m/Y');
            $data_br      = $data_fmt;
            $data_mes_ano = $dt->format('m/Y');
            $data_ano     = $dt->format('Y');
        } catch (Exception $e) {
            $data_fmt = $post['data_postagem'] ?? '';
        }

        // ── Badge por tipo ──
        $badge_labels = [1 => 'Sermão', 2 => 'Estudo', 3 => 'Teologia', 4 => 'Devocional'];
        $badge_label  = $badge_labels[$tipo] ?? 'Conteúdo';

        // ── Subtipo ──
        $id_subtipo    = $post['id_subtipo'] ?? null;
        $subtipo_nome  = '';
        $subtipo_label = '';
        if ($id_subtipo !== null && $id_subtipo !== '' && isset($subtipos[(int)$id_subtipo])) {
            $subtipo_nome  = $subtipos[(int)$id_subtipo];
            $subtipo_label = "<span class='ts-subtipo'>" . htmlspecialchars($subtipo_nome) . "</span>";
        }

        // ── Campos básicos ──
        $titulo   = htmlspecialchars($post['titulo']   ?? '');
        $passagem = htmlspecialchars($post['passagem'] ?? '');
        $id       = (int)$post['id'];
        $url      = "index.php?class=InicioPublicoView&method=onVerPost&id={$id}";

        // ── data-busca ──
        $tags_txt   = strip_tags($post['tags'] ?? '');
        $data_busca = htmlspecialchars(mb_strtolower(
            ($post['titulo']   ?? '') . ' ' .
                ($post['passagem'] ?? '') . ' ' .
                $data_br      . ' ' .
                $data_mes_ano . ' ' .
                $data_ano     . ' ' .
                $subtipo_nome . ' ' .
                $tags_txt
        ), ENT_QUOTES, 'UTF-8');

        $extra_class = ($idx >= 4) ? ' ts-card-extra' : '';

        return "
        <article class=\"ts-card{$extra_class}\" data-busca=\"{$data_busca}\" data-idx=\"{$idx}\">
            <div class='ts-card-header'>
                <span class='ts-badge ts-badge-{$tipo}'>{$badge_label}</span>
                {$subtipo_label}
            </div>
            <div class='ts-data'>{$data_fmt}</div>
            <h2 class='ts-card-titulo'>
                <a href='{$url}' class='ts-card-titulo-link'>{$titulo}</a>
            </h2>
            " . ($passagem ? "
            <div class='ts-card-passagem'>
                <span class='ts-passagem-dot'></span>
                {$passagem}
            </div>" : "") . "
        </article>";
    }

    // ──────────────────────────────────────────────────────────────────────
    // VISUALIZAÇÃO DE POSTAGEM EM PÁGINA SEPARADA
    // ──────────────────────────────────────────────────────────────────────

    /**
     * Renderiza uma postagem como página HTML completa em aba separada.
     * Cabeçalho (título, passagem, meta) fixo no topo via sticky.
     * Toolbar (Fechar / Gerar PDF) fixa na base.
     */
    public function onVerPost($param)
    {
        $id = (int)($param['id'] ?? 0);
        if (!$id) {
            echo '<p>Postagem não encontrada.</p>';
            return;
        }

        TTransaction::open('sample');
        $conn = TTransaction::get();

        // Query principal — sem JOIN em tbl_sub_tipo (nome incerto)
        $stmt = $conn->prepare("
            SELECT p.*, t.descricao AS tipo_nome
            FROM   tbl_postagem p
            LEFT JOIN tbl_tipo t ON t.id = p.id_tipo
            WHERE  p.id = :id
              AND  p.publica_postagem = '1'
            LIMIT 1
        ");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $post = $stmt->fetch(PDO::FETCH_ASSOC);

        // Busca subtipo de forma defensiva
        $subtipo_nome = '';
        if ($post && !empty($post['id_subtipo'])) {
            $tabelas = ['tbl_sub_tipo', 'tbl_subtipo', 'sub_tipo', 'postagens_tipo'];
            foreach ($tabelas as $tabela) {
                try {
                    $stSub = $conn->prepare("SELECT descricao FROM {$tabela} WHERE id = :id LIMIT 1");
                    $stSub->bindValue(':id', (int)$post['id_subtipo'], PDO::PARAM_INT);
                    $stSub->execute();
                    $row = $stSub->fetch(PDO::FETCH_ASSOC);
                    if ($row) {
                        $subtipo_nome = $row['descricao'];
                    }
                    break;
                } catch (Exception $e) {
                    // tenta o próximo
                }
            }
        }

        TTransaction::close();

        if (!$post) {
            echo '<p>Postagem não encontrada ou não publicada.</p>';
            return;
        }

        $titulo    = htmlspecialchars($post['titulo']    ?? '');
        $passagem  = htmlspecialchars($post['passagem']  ?? '');
        $tipo_nome = htmlspecialchars($post['tipo_nome'] ?? '');
        $subtipo   = htmlspecialchars($subtipo_nome);
        $data_fmt  = '';
        try {
            $data_fmt = (new DateTime($post['data_postagem']))->format('d/m/Y');
        } catch (Exception $e) {
        }

        // Sempre exibe o conteúdo completo; fallback para resumo se vazio
        $conteudo = $post['conteudo'] ?? '';
        if (empty(trim(strip_tags($conteudo))) && !empty($post['resumo'])) {
            $conteudo = $post['resumo'];
        }

        $url_pdf = "index.php?class=InicioPublicoView&method=onGeraPDF&id={$id}";

        $subtipo_extra = $subtipo
            ? "<span class='meta-subtipo'>{$subtipo}</span>"
            : '';

        $passagem_html = $passagem
            ? "<p class='post-passagem'>{$passagem}</p>"
            : '';

        $html = <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$titulo}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        html, body {
            font-family: Georgia, 'Times New Roman', serif;
            background: #f5f4f0;
            color: #222;
        }

        /* ── Cabeçalho sticky — filho direto do body ── */
        .post-header {
            position: sticky;
            top: 0;
            z-index: 50;
            background: rgba(245, 244, 240, 0.95);
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
            border-bottom: 1px solid rgba(0, 0, 0, .10);
        }
        .post-header-inner {
            max-width: 780px;
            margin: 0 auto;
            padding: 16px 24px 14px;
        }
        .post-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
            margin-bottom: 10px;
        }
        .meta-badge {
            background: #1a2a4a;
            color: #f0d060;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .06em;
            padding: 3px 10px;
            border-radius: 20px;
        }
        .meta-subtipo {
            font-size: 13px;
            color: #666;
            font-style: italic;
        }
        .meta-data {
            margin-left: auto;
            font-size: 13px;
            color: #888;
        }
        .post-titulo {
            font-size: 2rem;
            font-weight: 700;
            line-height: 1.25;
            margin-bottom: 8px;
            color: #1a2a4a;
        }

        .post-passagem {
            font-size: 1.05rem;
            color: #555;
            margin-bottom: 0;
            padding-left: 12px;
            border-left: 3px solid #b8972a;
        }

        /* ── Corpo do conteúdo — filho direto do body ── */
        .post-container {
            max-width: 780px;
            margin: 0 auto;
            padding: 28px 24px 100px;
        }
        .post-body {
            font-size: 1rem;
            line-height: 1.5;
            text-align: justify;
        }
        .post-body h1,
        .post-body h2 {
            margin-top: 28px;
            margin-bottom: 8px;
            color: #1a2a4a;
        }
        .post-body h3 {
            margin-top: 20px;
            margin-bottom: 6px;
            color: #2e4a7a;
        }
        .post-body p { margin-bottom: 14px; }

        /* ── Toolbar fixa na base ── */
        .post-toolbar {
            position: fixed;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 780px;
            max-width: 100%;
            z-index: 100;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding: 12px 24px;
            background: #1a2a4a;
            box-shadow: 0 -2px 8px rgba(0, 0, 0, .25);
            border-radius: 8px 8px 0 0;
        }
        .post-toolbar button,
        .post-toolbar a {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 18px;
            border-radius: 6px;
            font-size: 14px;
            font-family: inherit;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            border: none;
            transition: opacity .2s;
        }
        .post-toolbar button:hover,
        .post-toolbar a:hover { opacity: .85; }
        .btn-fechar {
            background: transparent;
            color: #ccc;
            border: 1px solid #555 !important;
        }
        .btn-fechar:hover { color: #fff; border-color: #aaa !important; }
        .btn-pdf {
            background: #b8972a;
            color: #fff;
        }
        @media (max-width: 600px) {
            .post-titulo {
                font-size: 1.1rem;
            }
            .post-meta {
                display: none;
            }
            .post-body {
                font-size: 0.95rem;
                line-height: 1.2;
            }
             .post-toolbar {
                width: 100%;
                left: 0;
                transform: none;
                border-radius: 0;
            }
        }
        @media print {
            .post-toolbar { display: none; }
            body { background: #fff; }
            .post-header {
                position: static;
                background: transparent;
                border: none;
                backdrop-filter: none;
            }
        }
    </style>
</head>
<body>

<!-- Cabeçalho sticky: irmão direto do body para sticky funcionar corretamente -->
<div class="post-header">
    <div class="post-header-inner">
        <div class="post-meta">
            <span class="meta-badge">{$tipo_nome}</span>
            {$subtipo_extra}
            <span class="meta-data">{$data_fmt}</span>
        </div>
        <h1 class="post-titulo">{$titulo}</h1>
        {$passagem_html}
    </div>
</div>

<!-- Corpo do conteúdo -->
<div class="post-container">
    <div class="post-body">
        {$conteudo}
    </div>
</div>

<!-- Toolbar fixa na base -->
<div class="post-toolbar">
    <button class="btn-fechar" onclick="window.opener ? window.close() : window.location.href='index.php'">
        ✕ Fechar
    </button>
    <a class="btn-pdf" href="{$url_pdf}">
        ⬇ Gerar PDF
    </a>
</div>

</body>
</html>
HTML;

        ob_end_clean();
        echo $html;
        exit;
    }

    // onGeraPDF($param) vem do MeuTrait
}
