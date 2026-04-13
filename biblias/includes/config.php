<?php
/**
 * Buscador Bíblico Multi-Versão
 * Configurações globais
 */

define('ROOT_PATH',    __DIR__ . '/..');
define('DATA_PATH',    ROOT_PATH . '/data');
define('BIBLES_PATH',  ROOT_PATH . '/data/bibles');
define('CACHE_PATH',   ROOT_PATH . '/cache');
define('CENTRAL_DB',   ROOT_PATH . '/data/central.sqlite');

// Versões registradas — adicione novos arquivos aqui
// 'sigla' => ['label' => '...', 'file' => 'nome-do-arquivo.sqlite']
define('BIBLE_VERSIONS', [
    'ACF'  => ['label' => 'Almeida Corrigida Fiel',          'file' => 'acf.sqlite'],
    'ARA'  => ['label' => 'Almeida Revista e Atualizada',    'file' => 'ara.sqlite'],
    'ARC'  => ['label' => 'Almeida Revista e Corrigida',     'file' => 'arc.sqlite'],
    'AS21' => ['label' => 'Almeida Século 21',               'file' => 'as21.sqlite'],
    'JFAA' => ['label' => 'João Ferreira de Almeida Atualizada', 'file' => 'jfaa.sqlite'],
    'KJA'  => ['label' => 'King James Atualizada (pt)',       'file' => 'kja.sqlite'],
    'KJF'  => ['label' => 'King James Fiel 2000 (pt)',        'file' => 'kjf.sqlite'],
    // 'KJV'  => ['label' => 'King James Version (en)',          'file' => 'kjv.sqlite'],
    'NAA'  => ['label' => 'Nova Almeida Atualizada',          'file' => 'naa.sqlite'],
    'NVI'  => ['label' => 'Nova Versão Internacional',        'file' => 'nvi.sqlite'],
    'NVT'  => ['label' => 'Nova Versão Transformadora',       'file' => 'nvt.sqlite'],
]);

// Livros canônicos (Bíblia Protestante — 66 livros)
define('BIBLE_BOOKS', [
     1 => ['abbr' => 'Gn',  'name' => 'Gênesis',              'en' => 'Genesis'],
     2 => ['abbr' => 'Êx',  'name' => 'Êxodo',                'en' => 'Exodus'],
     3 => ['abbr' => 'Lv',  'name' => 'Levítico',             'en' => 'Leviticus'],
     4 => ['abbr' => 'Nm',  'name' => 'Números',              'en' => 'Numbers'],
     5 => ['abbr' => 'Dt',  'name' => 'Deuteronômio',         'en' => 'Deuteronomy'],
     6 => ['abbr' => 'Js',  'name' => 'Josué',                'en' => 'Joshua'],
     7 => ['abbr' => 'Jz',  'name' => 'Juízes',               'en' => 'Judges'],
     8 => ['abbr' => 'Rt',  'name' => 'Rute',                 'en' => 'Ruth'],
     9 => ['abbr' => '1Sm', 'name' => '1 Samuel',             'en' => '1 Samuel'],
    10 => ['abbr' => '2Sm', 'name' => '2 Samuel',             'en' => '2 Samuel'],
    11 => ['abbr' => '1Rs', 'name' => '1 Reis',               'en' => '1 Kings'],
    12 => ['abbr' => '2Rs', 'name' => '2 Reis',               'en' => '2 Kings'],
    13 => ['abbr' => '1Cr', 'name' => '1 Crônicas',           'en' => '1 Chronicles'],
    14 => ['abbr' => '2Cr', 'name' => '2 Crônicas',           'en' => '2 Chronicles'],
    15 => ['abbr' => 'Ed',  'name' => 'Esdras',               'en' => 'Ezra'],
    16 => ['abbr' => 'Ne',  'name' => 'Neemias',              'en' => 'Nehemiah'],
    17 => ['abbr' => 'Et',  'name' => 'Ester',                'en' => 'Esther'],
    18 => ['abbr' => 'Jó',  'name' => 'Jó',                   'en' => 'Job'],
    19 => ['abbr' => 'Sl',  'name' => 'Salmos',               'en' => 'Psalms'],
    20 => ['abbr' => 'Pv',  'name' => 'Provérbios',           'en' => 'Proverbs'],
    21 => ['abbr' => 'Ec',  'name' => 'Eclesiastes',          'en' => 'Ecclesiastes'],
    22 => ['abbr' => 'Ct',  'name' => 'Cantares',             'en' => 'Song of Solomon'],
    23 => ['abbr' => 'Is',  'name' => 'Isaías',               'en' => 'Isaiah'],
    24 => ['abbr' => 'Jr',  'name' => 'Jeremias',             'en' => 'Jeremiah'],
    25 => ['abbr' => 'Lm',  'name' => 'Lamentações',          'en' => 'Lamentations'],
    26 => ['abbr' => 'Ez',  'name' => 'Ezequiel',             'en' => 'Ezekiel'],
    27 => ['abbr' => 'Dn',  'name' => 'Daniel',               'en' => 'Daniel'],
    28 => ['abbr' => 'Os',  'name' => 'Oséias',               'en' => 'Hosea'],
    29 => ['abbr' => 'Jl',  'name' => 'Joel',                 'en' => 'Joel'],
    30 => ['abbr' => 'Am',  'name' => 'Amós',                 'en' => 'Amos'],
    31 => ['abbr' => 'Ob',  'name' => 'Obadias',              'en' => 'Obadiah'],
    32 => ['abbr' => 'Jn',  'name' => 'Jonas',                'en' => 'Jonah'],
    33 => ['abbr' => 'Mq',  'name' => 'Miquéias',             'en' => 'Micah'],
    34 => ['abbr' => 'Na',  'name' => 'Naum',                 'en' => 'Nahum'],
    35 => ['abbr' => 'Hc',  'name' => 'Habacuque',            'en' => 'Habakkuk'],
    36 => ['abbr' => 'Sf',  'name' => 'Sofonias',             'en' => 'Zephaniah'],
    37 => ['abbr' => 'Ag',  'name' => 'Ageu',                 'en' => 'Haggai'],
    38 => ['abbr' => 'Zc',  'name' => 'Zacarias',             'en' => 'Zechariah'],
    39 => ['abbr' => 'Ml',  'name' => 'Malaquias',            'en' => 'Malachi'],
    40 => ['abbr' => 'Mt',  'name' => 'Mateus',               'en' => 'Matthew'],
    41 => ['abbr' => 'Mc',  'name' => 'Marcos',               'en' => 'Mark'],
    42 => ['abbr' => 'Lc',  'name' => 'Lucas',                'en' => 'Luke'],
    43 => ['abbr' => 'Jo',  'name' => 'João',                 'en' => 'John'],
    44 => ['abbr' => 'At',  'name' => 'Atos',                 'en' => 'Acts'],
    45 => ['abbr' => 'Rm',  'name' => 'Romanos',              'en' => 'Romans'],
    46 => ['abbr' => '1Co', 'name' => '1 Coríntios',          'en' => '1 Corinthians'],
    47 => ['abbr' => '2Co', 'name' => '2 Coríntios',          'en' => '2 Corinthians'],
    48 => ['abbr' => 'Gl',  'name' => 'Gálatas',              'en' => 'Galatians'],
    49 => ['abbr' => 'Ef',  'name' => 'Efésios',              'en' => 'Ephesians'],
    50 => ['abbr' => 'Fp',  'name' => 'Filipenses',           'en' => 'Philippians'],
    51 => ['abbr' => 'Cl',  'name' => 'Colossenses',          'en' => 'Colossians'],
    52 => ['abbr' => '1Ts', 'name' => '1 Tessalonicenses',    'en' => '1 Thessalonians'],
    53 => ['abbr' => '2Ts', 'name' => '2 Tessalonicenses',    'en' => '2 Thessalonians'],
    54 => ['abbr' => '1Tm', 'name' => '1 Timóteo',            'en' => '1 Timothy'],
    55 => ['abbr' => '2Tm', 'name' => '2 Timóteo',            'en' => '2 Timothy'],
    56 => ['abbr' => 'Tt',  'name' => 'Tito',                 'en' => 'Titus'],
    57 => ['abbr' => 'Fm',  'name' => 'Filemom',              'en' => 'Philemon'],
    58 => ['abbr' => 'Hb',  'name' => 'Hebreus',              'en' => 'Hebrews'],
    59 => ['abbr' => 'Tg',  'name' => 'Tiago',                'en' => 'James'],
    60 => ['abbr' => '1Pe', 'name' => '1 Pedro',              'en' => '1 Peter'],
    61 => ['abbr' => '2Pe', 'name' => '2 Pedro',              'en' => '2 Peter'],
    62 => ['abbr' => '1Jo', 'name' => '1 João',               'en' => '1 John'],
    63 => ['abbr' => '2Jo', 'name' => '2 João',               'en' => '2 John'],
    64 => ['abbr' => '3Jo', 'name' => '3 João',               'en' => '3 John'],
    65 => ['abbr' => 'Jd',  'name' => 'Judas',                'en' => 'Jude'],
    66 => ['abbr' => 'Ap',  'name' => 'Apocalipse',           'en' => 'Revelation'],
]);
