---
name: gerador-sermao-biografico-html
description: >
  Gere sermões biográficos em português a partir da vida de um personagem
  bíblico, seguindo a estrutura Introdução, Desenvolvimento (com blocos
  cronológicos ou temáticos), Lições (3 a 6) e Conclusão, usando NAA,
  e retornando o resultado diretamente em HTML no modelo especificado.
  Use quando o usuário pedir sermão biográfico, mensagem biográfica ou
  bosquejo biográfico com HTML.
---

# Gerador de Sermão Biográfico em HTML

## Quando usar

Use esta habilidade sempre que o usuário pedir:
- sermão biográfico sobre um personagem bíblico
- mensagem biográfica com ênfase em lições de vida
- bosquejo biográfico com aplicações práticas
- saída em HTML usando um modelo pré-definido

Se o usuário não especificar a versão bíblica, assuma que sempre deve ser usada a NAA (Nova Almeida Atualizada) nas citações. Não reproduza longos trechos literais da Bíblia; prefira paráfrases curtas e referências.

## Instruções gerais

1. Leia com atenção os textos bíblicos principais que tratam do personagem e qualquer título ou tema sugerido pelo usuário.
2. Se o usuário não propuser um título, crie um título claro, cristocêntrico e fiel ao tema central da vida do personagem.
3. Estruture o sermão sempre nestas partes, nesta ordem:
   - Introdução
   - Desenvolvimento
   - Lições
   - Conclusão
4. Trabalhe em linguagem pastoral, clara e coloquial, mas com profundidade bíblica e teológica.
5. Sempre que citar a Bíblia, utilize a versão NAA (Nova Almeida Atualizada), em forma de referência ou paráfrase breve.
6. No final, além do texto corrido do sermão, gere também um bloco de HTML seguindo exatamente o modelo informado.

## Estrutura detalhada do sermão

### Introdução

- Comece apresentando brevemente o personagem bíblico: nome, período, contexto bíblico em que aparece.
- Mostre, de forma criativa e reflexiva, por que a vida desse personagem é relevante para a igreja hoje.
- Quando possível, inclua um relato histórico da tradição cristã (pais da igreja, reformadores ou missionários) que dialogue com a temática da vida do personagem.
- Em seguida, apresente o contexto bíblico:
  - livro(s) em que o personagem aparece
  - panorama rápido da época (histórico, político, geográfico, religioso)
  - relação do personagem com os grandes movimentos da história da redenção
- A introdução deve preparar o ouvinte para entender a linha da vida do personagem, sem ainda detalhar cada episódio.
- A introdução deve ter entre 300 a 400 palavras.

### Desenvolvimento

- Apresente a vida do personagem em blocos cronológicos ou temáticos (por exemplo: “chamado”, “crises e quedas”, “restauração e maturidade”).
- Para cada bloco:
  - resuma os principais eventos daquela fase, com referências bíblicas
  - explique o sentido espiritual e teológico desses eventos no plano de Deus
  - mostre conexões com o contexto bíblico mais amplo e com a história da redenção
- Evite detalhes meramente curiosos; destaque aquilo que revela o caráter de Deus, a condição humana e os caminhos da graça.
- Sempre que possível, conecte a biografia à pessoa e à obra de Cristo, apontando para:
  - tipos
  - sombras
  - contrastes
  - ou antecipações da obra de Jesus
- O desenvolvimento deve ter entre 300 a 400 palavras.

### Lições

- Crie entre **3 e 6** lições, nunca menos de 3 nem mais de 6.
- Cada lição deve ter:
  - um subtítulo em formato de ponto (curto, prático e memorável)
  - um parágrafo de aplicação com profundidade, porém em linguagem coloquial
- Para cada lição:
  - extraia um princípio claro a partir de um momento da biografia (ex.: chamado, pecado, arrependimento, perseverança, fé em meio ao sofrimento)
  - conecte, sempre que possível, com a pessoa e a obra de Jesus Cristo,
    mostrando:
    - seu exemplo perfeito
    - a graça que supre nossas falhas
    - a esperança futura (escatológica)
  - mostre como o ouvinte hoje pode responder à verdade bíblica apresentada
- Cada lição deve ter, entre no mínimo 250 palavras e no máximo 350.
- Foque em aplicações para:
  - mente (crenças e convicções formadas a partir do exemplo do personagem)
  - coração (afetos e desejos reordenados)
  - vontade (decisões e práticas concretas no dia a dia cristão)

### Conclusão

- Faça uma conclusão que retome os principais marcos da biografia estudada.
- Mostre, de forma breve, como a vida desse personagem aponta para Cristo:
  - em suas virtudes (quando imitáveis)
  - em suas fraquezas (quando revelam nossa necessidade de graça)
- Inclua elementos de apelo:
  - chamado à conversão
  - chamado ao arrependimento
  - chamado a uma vida de maior consagração, à luz das lições aprendidas
- Na conclusão, destaque as conexões entre a biografia estudada e:
  - a pessoa de Jesus
  - sua vida perfeita
  - sua obra redentora, sua cruz e ressurreição
  - sua volta gloriosa e o chamado à perseverança
- Termine com um tom pastoral e convidativo, adequado para um apelo em um culto cristão.
- A conclusão deve ter entre 250 a 350 palavras.

## Modelo html
<div class="sermon-block" style="text-align: justify">
  <h1 style="font-weight: bolder; text-transform: uppercase">TÍTULO DO SERMÃO</h1>

  <h2 style="font-weight: bold;">Personagem Bíblico (Ex.: A vida de José no Egito)</h2>
  
  <h2 style="font-weight: bold;">Introdução</h2>
  <p>TEXTO DA INTRODUÇÃO</p>

  <h2 style="font-weight: bold;">Desenvolvimento</h2>
  <p>TEXTO DO DESENVOLVIMENTO</p> 

  <h2 style="font-weight: bold;">Lições</h2> <!-- Devem ser numeradas em algarismos romanos minúsculos seguidos de ')' - Ex.: i)  -->

  <h3 style="font-style: italic">i) TÍTULO DA LIÇÃO 1</h3> 
  <p>TEXTO DA LIÇÃO 1</p>

  <h3 style="font-style: italic">ii) TÍTULO DA LIÇÃO 2</h3>
  <p>TEXTO DA LIÇÃO 2</p>

  <!-- Repita o padrão até a lição 6, se houver -->

  <h2 style="font-weight: bold;">Conclusão</h2>
  <p>TEXTO DA CONCLUSÃO</p>
</div>
