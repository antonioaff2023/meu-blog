<?php

/**
 * InicioPublicoView
 *
 * Página pública principal com abas por tipo:
 *   1 = Sermão | 2 = Estudo Bíblico | 3 = Teologia Sistemática | 4 = Devocional
 *
 * Regras de exibição:
 *   - Apenas postagens com publica_postagem = b'1' e data_postagem <= hoje
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
        // tsRestauraEstado e MutationObserver ficam no public.html (fora do AJAX)
        $script = "
        <script>
        // Salva aba e termo no sessionStorage para sobreviver à navegação AJAX
        function tsSalvaEstado(panelId, termo) {
            try {
                sessionStorage.setItem(TS_STATE_KEY, JSON.stringify({ aba: panelId, termo: termo }));
            } catch(e) {}
        }

        // Troca de aba manual
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

        // Input de busca
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
            try { TTransaction::rollback(); } catch (Exception $ex) {}
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
              AND  publica_postagem = b'1'
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
     * Tenta os nomes de tabela mais comuns; se falhar retorna array vazio
     * (sem quebrar o restante do fluxo).
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
                break; // achou — para o loop
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
        $url      = "index.php?class=InicioPublicoView&method=onGeraPDF&id={$id}";
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
                <a href='{$url}' generator='adianti' class='devocional-titulo-link'>{$titulo}</a>
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

        $busca_html = "
        <div class='ts-busca-wrapper'>
            <div class='ts-busca-inner'>
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
        // ── Data formatada (exibição e busca) ──
        $data_fmt       = '';
        $data_br        = ''; // dd/mm/yyyy — para busca
        $data_mes_ano   = ''; // mm/yyyy — para busca por mês/ano
        $data_ano       = ''; // yyyy — para busca por ano
        try {
            $dt           = new DateTime($post['data_postagem']);
            $data_fmt     = $dt->format('d/m/Y');   // exibe e busca: 10/04/2025
            $data_br      = $data_fmt;
            $data_mes_ano = $dt->format('m/Y');     // busca: 04/2025
            $data_ano     = $dt->format('Y');       // busca: 2025
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

        // ── Resumo público ──
        $resumo_html    = '';
        $publica_resumo = $post['publica_resumo'] ?? null;
        $exibe_resumo   = ($publica_resumo === "\x01" || $publica_resumo === '1' || $publica_resumo === 1);
        if ($exibe_resumo && !empty($post['resumo'])) {
            $resumo_txt = htmlspecialchars(strip_tags($post['resumo']));
            if (mb_strlen($resumo_txt) > 120) {
                $corte      = mb_strrpos(mb_substr($resumo_txt, 0, 120), ' ');
                $resumo_txt = mb_substr($resumo_txt, 0, $corte ?: 120) . '…';
            }
            $resumo_html = "<p class='ts-card-resumo'>{$resumo_txt}</p>";
        }

        // ── Campos básicos ──
        $titulo   = htmlspecialchars($post['titulo']   ?? '');
        $passagem = htmlspecialchars($post['passagem'] ?? '');
        $id       = (int)$post['id'];
        $url      = "index.php?class=InicioPublicoView&method=onGeraPDF&id={$id}";

        // ── data-busca — título, passagem, data, subtipo e tags ──
        $tags_txt = strip_tags($post['tags'] ?? '');
        $data_busca = htmlspecialchars(mb_strtolower(
            ($post['titulo']  ?? '') . ' ' .
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
                <a href='{$url}' generator='adianti' class='ts-card-titulo-link'>{$titulo}</a>
            </h2>
            " . ($passagem ? "
            <div class='ts-card-passagem'>
                <span class='ts-passagem-dot'></span>
                {$passagem}
            </div>" : "") . "
            {$resumo_html}
        </article>";
    }

    // onGeraPDF($param) vem do MeuTrait
}