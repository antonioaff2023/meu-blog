#!/usr/bin/env php
<?php
/**
 * generate_sample.php — Gera um SQLite de exemplo com versículos reais
 * para testar o buscador sem precisar de um arquivo externo.
 *
 * Usa o formato "verse" (b, c, v, t) — schema C.
 *
 * USO:
 *   php import/generate_sample.php
 *   (gera data/bibles/sample.sqlite e registra no banco central como "SAMPLE")
 */

define('CLI', php_sapi_name() === 'cli');
if (!CLI) die("Execute via terminal: php import/generate_sample.php\n");

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/BibleDB.php';

$outFile = BIBLES_PATH . '/sample.sqlite';

echo "\nGerando SQLite de exemplo em $outFile…\n";

// Cria o SQLite externo no formato "SingleTable" (b, c, v, t)
$pdo = new PDO('sqlite:' . $outFile);
$pdo->exec("
    DROP TABLE IF EXISTS verse;
    CREATE TABLE verse (b INTEGER, c INTEGER, v INTEGER, t TEXT);
");

// Amostra de versículos (João 1 e João 3, Romans 8)
$verses = [
    // João 1
    [43,1,1,'No princípio era o Verbo, e o Verbo estava com Deus, e o Verbo era Deus.'],
    [43,1,2,'Ele estava no princípio com Deus.'],
    [43,1,3,'Todas as coisas foram feitas por ele, e sem ele nada do que foi feito se fez.'],
    [43,1,14,'E o Verbo se fez carne e habitou entre nós, cheio de graça e de verdade; e vimos a sua glória, glória como do unigênito do Pai.'],
    // João 3
    [43,3,16,'Porque Deus amou o mundo de tal maneira que deu o seu Filho unigênito, para que todo aquele que nele crê não pereça, mas tenha a vida eterna.'],
    [43,3,17,'Porque Deus enviou o seu Filho ao mundo, não para que julgasse o mundo, mas para que o mundo fosse salvo por ele.'],
    [43,3,36,'Aquele que crê no Filho tem a vida eterna; mas aquele que rejeita o Filho não verá a vida, mas a ira de Deus sobre ele permanece.'],
    // Romanos 8
    [45,8,1,'Portanto, agora nenhuma condenação há para os que estão em Cristo Jesus.'],
    [45,8,28,'E sabemos que todas as coisas contribuem juntamente para o bem daqueles que amam a Deus, daqueles que são chamados segundo o seu propósito.'],
    [45,8,31,'Que diremos, pois, a estas coisas? Se Deus é por nós, quem será contra nós?'],
    [45,8,38,'Porque estou certo de que nem a morte, nem a vida, nem os anjos, nem os principados, nem as potestades, nem o presente, nem o porvir,'],
    [45,8,39,'Nem a altura, nem a profundidade, nem alguma outra criatura nos poderá separar do amor de Deus, que está em Cristo Jesus nosso Senhor.'],
    // Salmos 23
    [19,23,1,'O Senhor é o meu pastor; nada me faltará.'],
    [19,23,2,'Ele me faz repousar em pastos verdejantes. Guia-me mansamente a águas tranquilas.'],
    [19,23,3,'Refrigera a minha alma; guia-me pelas veredas da justiça por amor do seu nome.'],
    [19,23,4,'Ainda que eu andasse pelo vale da sombra da morte, não temeria mal algum, porque tu és comigo; o teu bordão e o teu cajado me consolam.'],
    [19,23,5,'Preparas uma mesa perante mim na presença dos meus adversários; unges a minha cabeça com óleo; o meu cálice transborda.'],
    [19,23,6,'Certamente que a bondade e a misericórdia me seguirão todos os dias da minha vida; e habitarei na casa do Senhor por longos dias.'],
    // Efésios 2
    [49,2,8,'Porque pela graça sois salvos, por meio da fé; e isto não vem de vós; é dom de Deus.'],
    [49,2,9,'Não vem das obras, para que ninguém se glorie.'],
    [49,2,10,'Porque somos feitura dele, criados em Cristo Jesus para as boas obras, as quais Deus preparou para que andássemos nelas.'],
];

$ins = $pdo->prepare("INSERT INTO verse (b,c,v,t) VALUES (?,?,?,?)");
foreach ($verses as $v) {
    $ins->execute($v);
}

$count = count($verses);
echo "  ✓ $count versículos gravados no SQLite de exemplo.\n";

// Importa para o banco central como "SAMPLE"
echo "  Importando para o banco central como 'SAMPLE'…";
$db = new BibleDB();

// Temporariamente adiciona SAMPLE ao array de versões
$file = $outFile;
$importCount = $db->importBible('SAMPLE', $file);
echo " ✓ $importCount versículos importados.\n";

echo "\nPronto! Recarregue o index.php no navegador para ver a versão SAMPLE.\n\n";
