/**
 * Buscador Bíblico Multi-Versão — app.js
 * Gerencia abas, busca via AJAX e renderização de resultados
 */

'use strict';

// ─── Estado global ────────────────────────────────────────────────────────────

const state = {
  mode:                  'keyword',
  query:                 {},
  currentPage:           1,
  totalPages:            1,
  lastResults:           [],
  // Passagem: dados por versão (chave = sigla)
  passageData:           {},   // { NVI: [rows...], ACF: [rows...] }
  passageActive:         '',   // versão atualmente exibida
  passageRef:            '',   // rótulo da referência atual
  passagePrimaryVersion: '',   // versão preferida ao abrir (NAA ou recebida via URL)
};

// ─── DOM refs ─────────────────────────────────────────────────────────────────

const $ = id => document.getElementById(id);

const el = {
  tabs:         document.querySelectorAll('.tab'),
  panels:       document.querySelectorAll('.search-panel'),
  resultsList:  $('results-list'),
  resultsHdr:   $('results-header'),
  resultsCount: $('results-count'),
  pagination:   $('pagination'),
  loading:      $('loading'),
  errorMsg:     $('error-msg'),
  btnCopy:      $('btn-copy'),
  btnExport:    $('btn-export'),
};

// ─── Troca de abas ───────────────────────────────────────────────────────────

el.tabs.forEach(tab => {
  tab.addEventListener('click', () => {
    el.tabs.forEach(t => { t.classList.remove('active'); t.setAttribute('aria-selected', 'false'); });
    el.panels.forEach(p => p.classList.remove('active'));
    tab.classList.add('active');
    tab.setAttribute('aria-selected', 'true');
    const panel = document.getElementById('tab-' + tab.dataset.tab);
    if (panel) panel.classList.add('active');
    state.mode = tab.dataset.tab;
    clearResults();
  });
});

// ─── Formulários ────────────────────────────────────────────────────────────

// Keyword
$('form-keyword').addEventListener('submit', e => {
  e.preventDefault();
  const q    = $('kw-input').value.trim();
  const book = parseInt($('kw-book').value) || 0;
  const vers = getChecked('kw-versions');
  if (!q) return showError('Digite o termo de busca.');
  state.currentPage = 1;
  doSearch({ mode:'keyword', q, book, versions: vers.join(','), page:1 });
});

// Reference
$('form-reference').addEventListener('submit', e => {
  e.preventDefault();
  const ref  = $('ref-text').value.trim();
  const vers = getChecked('ref-versions').join(',');
  let bookNum, chapter, verse;

  if (ref) {
    const parsed = parseReference(ref);
    if (!parsed) return showError('Referência não reconhecida. Use: Jo 3:16 ou Rm 8:28');
    ({ bookNum, chapter, verse } = parsed);
  } else {
    bookNum = parseInt($('ref-book').value)    || 0;
    chapter = parseInt($('ref-chapter').value) || 0;
    verse   = parseInt($('ref-verse').value)   || 0;
    if (!bookNum || !chapter) return showError('Selecione livro e capítulo.');
  }
  doSearch({ mode:'reference', book:bookNum, chapter, verse, versions:vers });
});

// ─── Aba de Passagem ─────────────────────────────────────────────────────────

$('form-passage').addEventListener('submit', async e => {
  e.preventDefault();
  const raw  = $('pass-input').value.trim();
  if (!raw) return showError('Digite a referência da passagem.');

  const parsed = parseRangeReference(raw);
  if (!parsed) {
    return showError(
      'Referência não reconhecida. Use o formato: Jo 3.1-21 ou Hb 5.11-6.6'
    );
  }

  const { bookNum, startChapter, startVerse, endChapter, endVerse } = parsed;
  const versions = getChecked('pass-versions');
  if (versions.length === 0) return showError('Selecione pelo menos uma versão.');

  clearError();
  clearResults();
  showLoading(true);
  hideVersionNav();

  // Busca cada versão em paralelo
  const requests = versions.map(ver =>
    apiGet({
      mode:          'range',
      book:          bookNum,
      start_chapter: startChapter,
      start_verse:   startVerse,
      end_chapter:   endChapter,
      end_verse:     endVerse,
      versions:      ver,
    }).then(data => ({ ver, data }))
  );

  const results = await Promise.all(requests);
  showLoading(false);

  // Organiza por versão
  state.passageData   = {};
  state.passageRef    = '';
  let anyResults = false;

  results.forEach(({ ver, data }) => {
    if (data && data.results && data.results.length > 0) {
      state.passageData[ver] = data.results;
      state.passageRef       = data.reference || '';
      anyResults = true;
    }
  });

  if (!anyResults) {
    el.resultsList.innerHTML =
      '<p style="text-align:center;color:var(--text-2);padding:40px">Nenhum resultado. Verifique se as versões foram importadas.</p>';
    el.resultsHdr.classList.add('hidden');
    return;
  }

  // Define versão ativa: prefere a versão principal (NAA ou a recebida), senão a primeira com dados
  const available = Object.keys(state.passageData);
  const preferred  = state.passagePrimaryVersion;
  state.passageActive = (preferred && available.includes(preferred))
    ? preferred
    : available[0];

  // Exibe
  renderPassageNav(available);
  renderPassageContent(state.passageActive);

  el.resultsHdr.classList.remove('hidden');
  el.resultsCount.textContent =
    `${state.passageRef}` +
    (available.length > 1 ? ` — ${available.length} versão(ões) carregadas` : '');

  // Armazena para copiar/exportar
  state.lastResults = state.passageData[state.passageActive] || [];
});

// ─── Renderização da Passagem ─────────────────────────────────────────────────

function renderPassageNav(available) {
  const nav   = $('pass-version-nav');
  const pills = $('pass-version-pills');
  nav.classList.remove('hidden');

  pills.innerHTML = available.map(ver => `
    <button type="button"
            class="version-pill ${ver === state.passageActive ? 'active' : ''}"
            data-version="${ver}">
      ${esc(ver)}
    </button>
  `).join('');

  pills.querySelectorAll('.version-pill').forEach(pill => {
    pill.addEventListener('click', () => {
      const ver = pill.dataset.version;
      if (ver === state.passageActive) return;
      state.passageActive = ver;
      state.lastResults   = state.passageData[ver] || [];
      // Atualiza pills
      pills.querySelectorAll('.version-pill').forEach(p => {
        p.classList.toggle('active', p.dataset.version === ver);
      });
      renderPassageContent(ver);
    });
  });
}

function renderPassageContent(version) {
  const rows = state.passageData[version] || [];
  if (!rows.length) {
    el.resultsList.innerHTML =
      `<p style="text-align:center;color:var(--text-2);padding:30px">Nenhum versículo para ${esc(version)}.</p>`;
    return;
  }

  // Agrupa por capítulo
  const byChapter = {};
  rows.forEach(r => {
    const ch = r.chapter;
    if (!byChapter[ch]) byChapter[ch] = [];
    byChapter[ch].push(r);
  });

  const bookName = rows[0].book_name;
  const verLabel = rows[0].version_label || version;

  const html = Object.entries(byChapter).map(([ch, chRows]) => `
    <div class="passage-section">
      <div class="passage-section-header">
        <strong>${esc(bookName)} ${ch}</strong>
        <span>${esc(verLabel)}</span>
      </div>
      ${chRows.map(r => `
        <div class="passage-verse">
          <span class="passage-verse-num">${r.verse}</span>
          <span class="passage-verse-text">${esc(r.text)}</span>
        </div>
      `).join('')}
    </div>
  `).join('');

  el.resultsList.innerHTML = html;
}

function hideVersionNav() {
  const nav = $('pass-version-nav');
  if (nav) nav.classList.add('hidden');
}

// ─── Compare
$('form-compare').addEventListener('submit', e => {
  e.preventDefault();
  const ref = $('cmp-ref').value.trim();
  if (!ref) return showError('Digite uma referência.');
  const parsed = parseReference(ref);
  if (!parsed) return showError('Referência não reconhecida. Use: Jo 3:16 ou Rm 8:28');
  const { bookNum, chapter, verse } = parsed;
  if (!verse) return showError('Informe o versículo para comparação. Ex: Jo 3:16');
  doSearch({ mode:'reference', book:bookNum, chapter, verse, versions:'' });
});

// ─── Busca principal ─────────────────────────────────────────────────────────

async function doSearch(params, appendMode = false) {
  clearError();
  showLoading(true);
  if (!appendMode) clearResults();
  state.query = params;

  const data = await apiGet(params);
  showLoading(false);
  if (!data) return;

  state.lastResults = appendMode ? [...state.lastResults, ...data.results] : data.results;
  state.totalPages  = data.total_pages || 1;
  state.currentPage = data.page        || 1;

  renderResults(data, params);
}

// ─── API ─────────────────────────────────────────────────────────────────────

async function apiGet(params) {
  const qs = new URLSearchParams(
    Object.fromEntries(
      Object.entries(params).filter(([,v]) => v !== '' && v !== undefined && v !== null && v !== 0)
    )
  );
  try {
    const resp = await fetch('search.php?' + qs);
    const json = await resp.json();
    if (!json.ok) { showError(json.error); return null; }
    return json.data;
  } catch (err) {
    showError('Falha na requisição. Verifique se o servidor PHP está ativo.');
    return null;
  }
}

// ─── Renderização geral ───────────────────────────────────────────────────────

function renderResults(data, params) {
  const { results, total, total_pages, page, reference } = data;
  const tab = params.mode;

  if (!results || results.length === 0) {
    el.resultsList.innerHTML =
      '<p style="text-align:center;color:var(--text-2);padding:40px">Nenhum resultado encontrado.</p>';
    el.resultsHdr.classList.add('hidden');
    return;
  }

  el.resultsHdr.classList.remove('hidden');
  if (total !== undefined) {
    el.resultsCount.textContent = `${total.toLocaleString('pt-BR')} resultado(s)` +
      (reference ? ` em "${reference}"` : (params.q ? ` para "${params.q}"` : ''));
  } else {
    el.resultsCount.textContent = `${results.length} versículo(s)`;
  }

  // Modo comparação: todos têm o mesmo chapter+verse
  const isCompare = tab === 'compare' ||
    (results.length > 1 && results.every(r =>
      r.chapter === results[0].chapter && r.verse === results[0].verse
    ));

  if (isCompare && results[0].verse) {
    renderCompare(results);
  } else if (tab === 'reference' && !params.verse) {
    renderPassageSimple(results);
  } else {
    renderCards(results, params.q);
  }

  renderPagination(page, total_pages);
}

function renderCards(rows, keyword) {
  el.resultsList.innerHTML = rows.map(r => `
    <article class="verse-card">
      <div class="verse-ref">
        <span>${esc(r.reference || `${r.book_name} ${r.chapter}:${r.verse}`)}</span>
        <span class="verse-version">${esc(r.version)}</span>
      </div>
      <p class="verse-text">${keyword ? highlight(r.text, keyword) : esc(r.text)}</p>
    </article>
  `).join('');
}

// Passagem simples (aba Referência, capítulo inteiro, sem troca de versão)
function renderPassageSimple(rows) {
  const byVersion = {};
  rows.forEach(r => {
    if (!byVersion[r.version]) byVersion[r.version] = [];
    byVersion[r.version].push(r);
  });

  const versions = Object.keys(byVersion);
  el.resultsList.innerHTML = versions.map(ver => {
    const vRows   = byVersion[ver];
    const bName   = vRows[0].book_name;
    const chapter = vRows[0].chapter;
    return `
      <div class="passage-section">
        <div class="passage-section-header">
          <strong>${esc(bName)} ${chapter}</strong>
          <span>${esc(ver)}</span>
        </div>
        ${vRows.map(r => `
          <div class="passage-verse">
            <span class="passage-verse-num">${r.verse}</span>
            <span class="passage-verse-text">${esc(r.text)}</span>
          </div>
        `).join('')}
      </div>`;
  }).join('');
}

function renderCompare(rows) {
  const ref = rows[0] ? `${rows[0].book_name} ${rows[0].chapter}:${rows[0].verse}` : '';
  el.resultsList.innerHTML = `
    <p style="margin-bottom:12px;font-family:'Lora',serif;font-size:1.05rem;color:var(--accent)">
      <strong>${esc(ref)}</strong> — ${rows.length} versão(ões)
    </p>
    <div class="compare-grid">
      ${rows.map(r => `
        <div class="compare-card">
          <h4>${esc(r.version)} — ${esc(r.version_label || r.version)}</h4>
          <p>${esc(r.text)}</p>
        </div>
      `).join('')}
    </div>`;
}

function renderPagination(page, totalPages) {
  el.pagination.classList.toggle('hidden', totalPages <= 1);
  if (totalPages <= 1) return;

  const pages = buildPageRange(page, totalPages);
  el.pagination.innerHTML = pages.map(p =>
    p === '…'
      ? `<span style="color:var(--text-3)">…</span>`
      : `<button class="${p === page ? 'current' : ''}" data-page="${p}">${p}</button>`
  ).join('');

  el.pagination.querySelectorAll('button[data-page]').forEach(btn => {
    btn.addEventListener('click', () => {
      doSearch({ ...state.query, page: parseInt(btn.dataset.page) });
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  });
}

function buildPageRange(cur, total) {
  const delta = 2;
  const range = [];
  for (let i = Math.max(1, cur - delta); i <= Math.min(total, cur + delta); i++) range.push(i);
  const result = [];
  if (range[0] > 1) { result.push(1); if (range[0] > 2) result.push('…'); }
  result.push(...range);
  if (range[range.length-1] < total) {
    if (range[range.length-1] < total-1) result.push('…');
    result.push(total);
  }
  return result;
}

// ─── Copiar / Exportar ───────────────────────────────────────────────────────

el.btnCopy.addEventListener('click', () => {
  const text = buildCopyText();
  navigator.clipboard.writeText(text).then(() => {
    el.btnCopy.textContent = 'Copiado!';
    setTimeout(() => el.btnCopy.textContent = 'Copiar', 2000);
  });
});

// Converte número inteiro em seus dígitos sobrescritos Unicode
// Ex: 16 → ¹⁶  |  3 → ³  |  119 → ¹¹⁹
function toSuperscript(n) {
  const map = { '0':'\u2070','1':'\u00b9','2':'\u00b2','3':'\u00b3','4':'\u2074',
                '5':'\u2075','6':'\u2076','7':'\u2077','8':'\u2078','9':'\u2079' };
  return String(n).split('').map(d => map[d] ?? d).join('');
}

function buildCopyText() {
  const rows = state.lastResults;
  if (!rows.length) return '';

  // Agrupa por (versão, livro, capítulo) para gerar um cabeçalho por bloco
  const blocks = [];
  let current  = null;

  rows.forEach(r => {
    const key = `${r.version}|${r.book_num}|${r.chapter}`;
    if (!current || current.key !== key) {
      current = { key, version: r.version, book_name: r.book_name, chapter: r.chapter, rows: [] };
      blocks.push(current);
    }
    current.rows.push(r);
  });

  // Se todos os blocos têm a mesma versão, consolida o cabeçalho
  const versions = [...new Set(rows.map(r => r.version))];
  const isSingleVersion = versions.length === 1;

  // Monta rótulo da referência
  const firstRow = rows[0];
  const lastRow  = rows[rows.length - 1];
  const bookName = firstRow.book_name;

  let refLabel;
  if (firstRow.chapter === lastRow.chapter) {
    refLabel = `${bookName} ${firstRow.chapter}:${firstRow.verse}–${lastRow.verse}`;
  } else {
    refLabel = `${bookName} ${firstRow.chapter}:${firstRow.verse}–${lastRow.chapter}:${lastRow.verse}`;
  }
  if (isSingleVersion) refLabel += ` (${versions[0]})`;

  const lines = [refLabel, ''];

  blocks.forEach(block => {
    // Cabeçalho de bloco apenas quando há múltiplas versões ou múltiplos capítulos
    if (!isSingleVersion) {
      lines.push(`— ${block.version} — ${block.book_name} ${block.chapter}`);
    } else if (blocks.length > 1) {
      lines.push(`${block.book_name} ${block.chapter}`);
    }
    block.rows.forEach(r => {
      lines.push(`${toSuperscript(r.verse)} ${r.text}`);
    });
    lines.push('');
  });

  return lines.join('\n').trimEnd();
}

el.btnExport.addEventListener('click', () => {
  const text = buildCopyText();
  const blob = new Blob([text], { type: 'text/plain;charset=utf-8' });
  const url  = URL.createObjectURL(blob);
  const a    = document.createElement('a');
  a.href = url; a.download = 'versiculos.txt'; a.click();
  URL.revokeObjectURL(url);
});

// ─── Parser de referência pontual (Jo 3:16) ──────────────────────────────────

const BOOK_MAP = {
  gn:1, genesis:1, 'gênesis':1,
  ex:2, 'êx':2, exodo:2, 'êxodo':2, exodus:2,
  lv:3, lev:3, levitico:3, 'levítico':3,
  nm:4, num:4, numeros:4, 'números':4,
  dt:5, deu:5, deuteronomio:5, 'deuteronômio':5,
  js:6, jos:6, josue:6, 'josué':6,
  jz:7, jui:7, juizes:7, 'juízes':7,
  rt:8, rute:8,
  '1sm':9,'1samuel':9,
  '2sm':10,'2samuel':10,
  '1rs':11,'1reis':11,
  '2rs':12,'2reis':12,
  '1cr':13,'1cronicas':13,
  '2cr':14,'2cronicas':14,
  ed:15, esdras:15,
  ne:16, neemias:16,
  et:17, ester:17, 'éster':17,
  'jó':18, job:18,
  sl:19, salmos:19, ps:19, psalms:19,
  pv:20, prov:20, proverbios:20, 'provérbios':20,
  ec:21, eclesiastes:21, ecl:21,
  ct:22, cantares:22,
  is:23, isaias:23, 'isaías':23,
  jr:24, jeremias:24,
  lm:25, lamentacoes:25, 'lamentações':25,
  ez:26, ezequiel:26,
  dn:27, daniel:27,
  os:28, oseias:28, 'oséias':28,
  jl:29, joel:29,
  am:30, amos:30, 'amós':30,
  ob:31, obadias:31,
  jn:32, jonas:32,
  mq:33, miqueias:33, 'miquéias':33,
  na:34, naum:34,
  hc:35, habacuque:35,
  sf:36, sofonias:36,
  ag:37, ageu:37,
  zc:38, zacarias:38,
  ml:39, malaquias:39,
  mt:40, mateus:40,
  mc:41, marcos:41,
  lc:42, lucas:42,
  jo:43, joao:43, 'joão':43, john:43,
  at:44, atos:44, acts:44,
  rm:45, romanos:45, romans:45,
  '1co':46,'1corintios':46,
  '2co':47,'2corintios':47,
  gl:48, galatas:48, 'gálatas':48,
  ef:49, efesios:49, 'efésios':49,
  fp:50, filipenses:50,
  cl:51, colossenses:51,
  '1ts':52,'1tessalonicenses':52,
  '2ts':53,'2tessalonicenses':53,
  '1tm':54,'1timoteo':54,
  '2tm':55,'2timoteo':55,
  tt:56, tito:56,
  fm:57, filemom:57,
  hb:58, hebreus:58, hebrews:58,
  tg:59, tiago:59, james:59,
  '1pe':60,'1pedro':60,
  '2pe':61,'2pedro':61,
  '1jo':62,'1joao':62,
  '2jo':63,'2joao':63,
  '3jo':64,'3joao':64,
  jd:65, judas:65,
  ap:66, apocalipse:66, revelation:66,
};

/** Normaliza abreviação: remove acentos, pontos, espaços */
function normalizeAbbr(str) {
  return str
    .toLowerCase()
    .replace(/\./g, '')
    .normalize('NFD').replace(/[\u0300-\u036f]/g, '') // remove diacríticos
    .replace(/\s+/g, '');
}

/** Procura bookNum no BOOK_MAP tentando com e sem diacríticos */
function lookupBook(raw) {
  // Tenta direto
  if (BOOK_MAP[raw.toLowerCase()]) return BOOK_MAP[raw.toLowerCase()];
  // Tenta normalizado
  const norm = normalizeAbbr(raw);
  // Reconstrói chave com possível número inicial
  if (BOOK_MAP[norm]) return BOOK_MAP[norm];
  // Busca parcial
  for (const [k, v] of Object.entries(BOOK_MAP)) {
    if (normalizeAbbr(k) === norm) return v;
  }
  return null;
}

/** Referência pontual: Jo 3:16 ou Jo 3.16 */
function parseReference(text) {
  text = text.trim().replace(/\s+/g, ' ');
  const m = text.match(/^(\d?\s?[a-záàâãéèêíìîóòôõúùûçñü]+\.?)\s*(\d+)[:.。](\d+)?$/i);
  if (!m) {
    // Só livro+capítulo sem versículo
    const m2 = text.match(/^(\d?\s?[a-záàâãéèêíìîóòôõúùûçñü]+\.?)\s*(\d+)$/i);
    if (!m2) return null;
    const bookNum = lookupBook(m2[1]);
    if (!bookNum) return null;
    return { bookNum, chapter: parseInt(m2[2]), verse: 0 };
  }
  const bookNum = lookupBook(m[1]);
  if (!bookNum) return null;
  return { bookNum, chapter: parseInt(m[2]), verse: m[3] ? parseInt(m[3]) : 0 };
}

// ─── Parser de intervalo de passagem ─────────────────────────────────────────
/**
 * Suporta:
 *   Jo 3.1-15         → mesmo capítulo
 *   Hb 5.11-6.6       → capítulos diferentes
 *   Rm 8.1-39         → mesmo capítulo
 *   Sl 23             → capítulo inteiro
 *   Jo 3              → capítulo inteiro
 *
 * Separadores aceitos: . : espaço
 * Intervalo: - ou –
 */
function parseRangeReference(text) {
  text = text.trim().replace(/\s+/g, ' ');

  // Extrai a parte do livro (pode ser "1Co", "2 Co", "Jo", etc.)
  const bookMatch = text.match(/^(\d?\s?[a-záàâãéèêíìîóòôõúùûçñü]+\.?)/i);
  if (!bookMatch) return null;

  const bookRaw = bookMatch[1].trim();
  const bookNum = lookupBook(bookRaw);
  if (!bookNum) return null;

  // Resto após o nome do livro
  const rest = text.slice(bookMatch[0].length).trim().replace(/^[:\s]+/, '');
  if (!rest) return null;

  // Separa início e fim pelo traço
  const parts = rest.split(/\s*[–\-]\s*/);

  if (parts.length === 1) {
    // Sem traço: pode ser "3.1" (cap+vs) ou "3" (só capítulo)
    const single = parseChapterVerse(parts[0]);
    if (!single) return null;
    if (single.verse) {
      // Ex: Jo 3.16 → intervalo de 1 versículo
      return {
        bookNum,
        startChapter: single.chapter,
        startVerse:   single.verse,
        endChapter:   single.chapter,
        endVerse:     single.verse,
      };
    } else {
      // Capítulo inteiro
      return {
        bookNum,
        startChapter: single.chapter,
        startVerse:   1,
        endChapter:   single.chapter,
        endVerse:     999,
      };
    }
  }

  // Tem traço: partes[0] = início, partes[1] = fim
  const start = parseChapterVerse(parts[0]);
  if (!start) return null;

  const endRaw = parts[1].trim();

  // Fim pode ser só o número do versículo (mesmo capítulo) ou cap.vs
  let endChapter, endVerse;
  if (/^\d+$/.test(endRaw)) {
    // Só um número: se start.verse existe → é versículo final do mesmo capítulo
    //               se start.verse não existe → é o capítulo final (capítulo inteiro)
    if (start.verse) {
      endChapter = start.chapter;
      endVerse   = parseInt(endRaw);
    } else {
      // Ex: Rm 8-9 (capítulos 8 e 9 inteiros)
      endChapter = parseInt(endRaw);
      endVerse   = 999;
    }
  } else {
    const end = parseChapterVerse(endRaw);
    if (!end) return null;
    endChapter = end.chapter;
    endVerse   = end.verse || 999;
  }

  // Validação básica
  if (endChapter < start.chapter) return null;
  if (endChapter === start.chapter && start.verse && endVerse < start.verse) return null;

  return {
    bookNum,
    startChapter: start.chapter,
    startVerse:   start.verse || 1,
    endChapter,
    endVerse,
  };
}

/** Analisa "3.16" ou "3:16" ou "3" → { chapter, verse } */
function parseChapterVerse(str) {
  str = str.trim();
  const m = str.match(/^(\d+)[.:](\d+)$/);
  if (m) return { chapter: parseInt(m[1]), verse: parseInt(m[2]) };
  const m2 = str.match(/^(\d+)$/);
  if (m2) return { chapter: parseInt(m2[1]), verse: 0 };
  return null;
}

// ─── Utilidades ──────────────────────────────────────────────────────────────

function esc(str) {
  return String(str ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

function highlight(text, keyword) {
  const escaped = esc(text);
  const kEsc    = keyword.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  return escaped.replace(new RegExp(`(${kEsc})`, 'gi'), '<mark>$1</mark>');
}

function getChecked(containerId) {
  return [...document.querySelectorAll(`#${containerId} input[type="checkbox"]:checked`)]
    .map(cb => cb.value);
}

function showLoading(show) {
  el.loading.classList.toggle('hidden', !show);
}

function clearResults() {
  el.resultsList.innerHTML = '';
  el.resultsHdr.classList.add('hidden');
  el.pagination.classList.add('hidden');
  state.lastResults = [];
}

function showError(msg) {
  el.errorMsg.textContent = msg;
  el.errorMsg.classList.remove('hidden');
  showLoading(false);
}

function clearError() {
  el.errorMsg.classList.add('hidden');
}

// ─── Abertura automática via parâmetros da URL (← chamado pelo FormMeusPosts) ─────

(function initAuto() {
  const cfg = window.BIBLE_AUTO;
  if (!cfg) return;

  // Garante que a aba Passagem esteja ativa (já é a padrão, mas por segurança)
  el.tabs.forEach(t => {
    const isPass = t.dataset.tab === 'passage';
    t.classList.toggle('active', isPass);
    t.setAttribute('aria-selected', isPass ? 'true' : 'false');
  });
  el.panels.forEach(p => {
    p.classList.toggle('active', p.id === 'tab-passage');
  });
  state.mode = 'passage';

  // Preenche o campo de passagem com o valor recebido
  const passInput = $('pass-input');
  if (passInput && cfg.passagem) {
    passInput.value = cfg.passagem;
  }

  // Marca TODAS as versões disponíveis nos checkboxes (não desabilita nenhuma)
  // A versão principal (NAA ou a recebida) será a pill ativa após a busca
  document.querySelectorAll('#pass-versions input[type="checkbox"]:not(:disabled)').forEach(cb => {
    cb.checked = true;
  });
  // Guarda a versão principal para que renderPassageNav a coloque como ativa
  state.passagePrimaryVersion = cfg.versao || 'NAA';

  // Se há passagem, dispara a busca automaticamente
  if (cfg.ativo && cfg.passagem) {
    // Pequeno delay para garantir que o DOM está pronto
    setTimeout(() => {
      $('form-passage').dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
    }, 120);
  }
})();
