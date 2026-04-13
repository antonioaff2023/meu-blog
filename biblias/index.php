<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/BibleDB.php';

// Carrega versões disponíveis no banco central
$db = new BibleDB();
$availableVersions = $db->getAvailableVersions();
$allVersions       = BIBLE_VERSIONS;
$books             = BIBLE_BOOKS;

$hasData = !empty($availableVersions);

// Parâmetros de abertura automática vindos da URL
// Ex: ?passagem=Jo+3.1-21&versao=NAA
$autoPassagem = trim($_GET['passagem'] ?? '');
$autoVersao   = strtoupper(trim($_GET['versao'] ?? 'NAA'));
// Valida versão: se não estiver disponível, cai para NAA ou primeira disponível
if (!isset($availableVersions[$autoVersao])) {
    $autoVersao = isset($availableVersions['NAA'])
        ? 'NAA'
        : (array_key_first($availableVersions) ?? '');
}
$autoConfig = json_encode([
    'passagem' => $autoPassagem,
    'versao'   => $autoVersao,
    'ativo'    => $autoPassagem !== '',
]);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="generator" content="Perplexity Computer" />
<meta name="application-name" content="Perplexity Computer" />
<title>Buscador Bíblico Multi-Versão</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,600;1,400&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
<link rel="icon" href="images/icon.png" type="image/png">
</head>
<body>

<!-- ═══════════════════ HEADER ═════════════════════════ -->
<header class="site-header">
  <div class="container header-inner">
    <div class="logo">
      <svg aria-label="Buscador Bíblico" viewBox="0 0 40 40" width="40" height="40" fill="none">
        <rect x="4" y="4" width="22" height="30" rx="3" stroke="currentColor" stroke-width="2" fill="none"/>
        <line x1="8" y1="14" x2="22" y2="14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
        <line x1="8" y1="19" x2="22" y2="19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
        <line x1="8" y1="24" x2="17" y2="24" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
        <circle cx="30" cy="29" r="7" stroke="currentColor" stroke-width="2" fill="none"/>
        <line x1="35" y1="34" x2="39" y2="38" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
      </svg>
      <span class="logo-text">Buscador Bíblico</span>
    </div>
    <div class="header-meta">
      <span id="db-stats" class="db-badge">
        <?php if ($hasData):
          $stats = $db->getStats(); ?>
          <?= number_format($stats['total'], 0, ',', '.') ?> versículos
          · <?= count($availableVersions) ?> versão(ões)
        <?php else: ?>
          Nenhuma versão importada
        <?php endif; ?>
      </span>
    </div>
  </div>
</header>

<!-- ═══════════════════ PAINEL DE BUSCA ════════════════ -->
<main class="container">

  <?php if (!$hasData): ?>
  <div class="notice notice-warn">
    <strong>Nenhuma versão bíblica importada ainda.</strong>
    Coloque seus arquivos <code>.sqlite</code> na pasta <code>data/bibles/</code>
    e execute <code>php import/import.php</code> no terminal.
    <a href="#instrucoes">Ver instruções</a>
  </div>
  <?php endif; ?>

  <!-- ABAS -->
  <div class="tabs" role="tablist">
    <button class="tab active" data-tab="passage" role="tab" aria-selected="true">
      Passagem
    </button>
    <button class="tab" data-tab="keyword" role="tab" aria-selected="false">
      Palavra-chave
    </button>
    <button class="tab" data-tab="reference" role="tab" aria-selected="false">
      Referência
    </button>
    <button class="tab" data-tab="compare" role="tab" aria-selected="false">
      Comparar Versões
    </button>
  </div>

  <!-- ── Busca por Palavra-chave ────────────────────── -->
  <section class="search-panel" id="tab-keyword">
    <form class="search-form" id="form-keyword">
      <div class="input-row">
        <input type="search" id="kw-input" name="q"
               placeholder="Ex: ressurreição, graça, fé…"
               autocomplete="off" spellcheck="false">
        <button type="submit" class="btn btn-primary">Buscar</button>
      </div>
      <div class="filters">
        <div class="filter-group">
          <label>Livro <small>(opcional)</small></label>
          <select id="kw-book">
            <option value="0">— Todos os livros —</option>
            <optgroup label="Antigo Testamento">
              <?php foreach ($books as $n => $b):
                if ($n > 39) break; ?>
                <option value="<?= $n ?>"><?= htmlspecialchars($b['name']) ?></option>
              <?php endforeach; ?>
            </optgroup>
            <optgroup label="Novo Testamento">
              <?php foreach ($books as $n => $b):
                if ($n <= 39) continue; ?>
                <option value="<?= $n ?>"><?= htmlspecialchars($b['name']) ?></option>
              <?php endforeach; ?>
            </optgroup>
          </select>
        </div>
        <div class="filter-group">
          <label>Versões <small>(múltiplas)</small></label>
          <div class="version-chips" id="kw-versions">
            <?php foreach ($allVersions as $sig => $info):
              $active = isset($availableVersions[$sig]); ?>
              <label class="chip <?= $active ? '' : 'chip-disabled' ?>">
                <input type="checkbox" value="<?= $sig ?>"
                       <?= $active ? 'checked' : 'disabled' ?>>
                <?= $sig ?>
              </label>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </form>
  </section>

  <!-- ── Busca por Referência ───────────────────────── -->
  <section class="search-panel" id="tab-reference">
    <form class="search-form" id="form-reference">
      <p class="help-text">
        Digite a referência: <strong>Jo 3:16</strong>, <strong>Rm 8</strong>, <strong>Gn 1:1</strong>…
        ou use os campos abaixo.
      </p>
      <div class="ref-row">
        <input type="text" id="ref-text" placeholder="Ex: Jo 3:16 ou Romanos 8:28"
               autocomplete="off" style="flex:1">
        <button type="submit" class="btn btn-primary">Buscar</button>
      </div>
      <div class="filters" style="margin-top:12px">
        <div class="filter-group">
          <label>Ou selecione</label>
          <div class="ref-selects">
            <select id="ref-book">
              <option value="">Livro</option>
              <optgroup label="Antigo Testamento">
                <?php foreach ($books as $n => $b):
                  if ($n > 39) break; ?>
                  <option value="<?= $n ?>"><?= htmlspecialchars($b['name']) ?></option>
                <?php endforeach; ?>
              </optgroup>
              <optgroup label="Novo Testamento">
                <?php foreach ($books as $n => $b):
                  if ($n <= 39) continue; ?>
                  <option value="<?= $n ?>"><?= htmlspecialchars($b['name']) ?></option>
                <?php endforeach; ?>
              </optgroup>
            </select>
            <input type="number" id="ref-chapter" placeholder="Cap." min="1" max="150" style="width:80px">
            <input type="number" id="ref-verse"   placeholder="Vs."  min="1" max="200" style="width:80px">
          </div>
        </div>
        <div class="filter-group">
          <label>Versões</label>
          <div class="version-chips" id="ref-versions">
            <?php foreach ($allVersions as $sig => $info):
              $active = isset($availableVersions[$sig]); ?>
              <label class="chip <?= $active ? '' : 'chip-disabled' ?>">
                <input type="checkbox" value="<?= $sig ?>"
                       <?= $active ? 'checked' : 'disabled' ?>>
                <?= $sig ?>
              </label>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </form>
  </section>

  <!-- ── Passagem ──────────────────────────────────── -->
  <section class="search-panel active" id="tab-passage">
    <form class="search-form" id="form-passage">
      <p class="help-text">
        Digite a passagem completa com intervalo de versículos.<br>
        Exemplos: <strong>Jo 3.1-21</strong> &nbsp;·&nbsp; <strong>Hb 5.11-6.6</strong> &nbsp;·&nbsp; <strong>Rm 8.1-39</strong> &nbsp;·&nbsp; <strong>Sl 23.1-6</strong>
      </p>
      <div class="input-row">
        <input type="text" id="pass-input"
               placeholder="Ex: Jo 3.1-21 ou Hb 5.11-6.6"
               autocomplete="off" spellcheck="false" style="flex:1">
        <button type="submit" class="btn btn-primary">Exibir</button>
      </div>
      <div class="filters" style="margin-top:4px">
        <div class="filter-group" style="flex:1;min-width:240px">
          <label>Versões <small>(selecione uma ou mais)</small></label>
          <div class="version-chips" id="pass-versions">
            <?php foreach ($allVersions as $sig => $info):
              $active = isset($availableVersions[$sig]); ?>
              <label class="chip <?= $active ? '' : 'chip-disabled' ?>">
                <input type="checkbox" value="<?= $sig ?>"
                       <?= $active ? 'checked' : 'disabled' ?>>
                <?= $sig ?>
              </label>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </form>
    <!-- Seletor de versão ativa — aparece após a busca -->
    <div id="pass-version-nav" class="version-nav hidden">
      <span class="version-nav-label">Lendo em:</span>
      <div id="pass-version-pills" class="version-pills"></div>
    </div>
  </section>

  <!-- ── Comparar Versões ───────────────────────────── -->
  <section class="search-panel" id="tab-compare">
    <form class="search-form" id="form-compare">
      <p class="help-text">Exibe um versículo em todas as versões disponíveis lado a lado.</p>
      <div class="ref-row">
        <input type="text" id="cmp-ref" placeholder="Ex: Jo 3:16 ou Rm 8:28"
               autocomplete="off" style="flex:1">
        <button type="submit" class="btn btn-primary">Comparar</button>
      </div>
    </form>
  </section>

  <!-- ═══════════════ RESULTADOS ═══════════════════════ -->
  <section id="results-area">
    <div id="results-header" class="results-header hidden">
      <span id="results-count"></span>
      <div class="results-actions">
        <button id="btn-copy" class="btn btn-sm" title="Copiar todos">Copiar</button>
        <button id="btn-export" class="btn btn-sm" title="Exportar TXT">Exportar .txt</button>
      </div>
    </div>
    <div id="results-list"></div>
    <div id="pagination" class="pagination hidden"></div>
    <div id="loading" class="loading hidden">Buscando…</div>
    <div id="error-msg" class="notice notice-error hidden"></div>
  </section>

</main>

<!-- ═════════════════ INSTRUÇÕES ═══════════════════════ -->
<!-- <section class="container instrucoes" id="instrucoes">
  <h2>Como importar versões</h2>
  <ol>
    <li>Obtenha os arquivos <code>.sqlite</code> da versão desejada (formatos suportados: <em>YouVersion, bibliadigital, Open.Bible</em> e outros).</li>
    <li>Coloque-os em <code>data/bibles/</code> com o nome exato configurado em <code>config.php</code>.<br>
        Ex: <code>acf.sqlite</code>, <code>nvi.sqlite</code>, etc.</li>
    <li>Execute no terminal dentro da pasta do projeto:<br>
        <code>php import/import.php</code> &nbsp;→ importa todas as versões configuradas<br>
        <code>php import/import.php NVI ACF</code> &nbsp;→ importa versões específicas<br>
        <code>php import/import.php --list</code> &nbsp;→ lista todas e mostra se o arquivo existe<br>
        <code>php import/import.php --stats</code> &nbsp;→ exibe estatísticas do banco central<br>
        <code>php import/import.php --inspect data/bibles/arquivo.sqlite</code> &nbsp;→ inspeciona schema
    </li>
    <li>Recarregue esta página.</li>
  </ol>
  <p><strong>Fontes de SQLite bíblico:</strong>
    <a href="https://github.com/thiagobodruk/biblia" target="_blank">thiagobodruk/biblia</a> ·
    <a href="https://github.com/scrollmapper/bible_databases" target="_blank">scrollmapper/bible_databases</a> ·
    <a href="https://www.ph4.org/b4_mobi.php" target="_blank">ph4.org</a>
  </p>
</section> -->

<footer class="site-footer">
  <div class="container">
    <a href="https://www.perplexity.ai/computer" target="_blank" rel="noopener noreferrer"
       style="color:inherit;opacity:0.5;font-size:0.75rem">
      Criado por Antonio Carlos G. Affonso com Perplexity Computer
    </a>
  </div>
</footer>

<script>
  // Configuração de abertura automática gerada pelo PHP
  window.BIBLE_AUTO = <?= $autoConfig ?>;
</script>
<script src="assets/js/app.js"></script>
</body>
</html>
