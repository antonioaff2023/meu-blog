---
name: gerador-sermao-tematico-html
description: >
  Gere sermões temáticos em português podendo ser a partir de um texto bíblico proposto ou propondo um texto dentro do assunto, tema ou título proposto,
  seguindo a estrutura Introdução, Desenvolvimento, Aplicações (3 a 6) e
  Conclusão, usando NAA, e retornando o resultado também em HTML no
  modelo especificado. Use quando o usuário pedir sermão temático.
---

# Gerador de Sermão Temático em HTML

## Quando usar

Use esta habilidade sempre que o usuário pedir:
- sermão temático ou mensagem tópica baseada em um texto bíblico, assunto ou tema proposto
- bosquejo ou esboço de sermão com aplicações
- saída em HTML usando um modelo pré-definido

Se o usuário não especificar a versão bíblica, assuma que sempre deve ser usada a NAA (Nova Almeida Atualizada) nas citações. Não reproduza longos trechos literais da Bíblia; prefira paráfrases curtas e referências.

## Instruções gerais

1. Leia com atenção o texto bíblico e qualquer título ou tema sugerido pelo usuário.
2. Se o usuário não propuser um título, crie um título claro, cristocêntrico e fiel ao tema central do texto e do seu contexto imediato.
3. Estruture o sermão sempre nestas partes, nesta ordem:
   - Introdução
   - Desenvolvimento
   - Aplicações
   - Conclusão
4. Trabalhe em linguagem pastoral, clara e coloquial, mas com profundidade bíblica e teológica.
5. Sempre que citar a Bíblia, utilize a versão NAA (Nova Almeida Atualizada), em forma de referência ou paráfrase breve.
6. No final, um bloco de HTML seguindo exatamente o modelo informado.

## Estrutura detalhada do sermão

### Introdução

- Comece com uma introdução criativa e reflexiva relacionada ao texto ou assunto.
- Quando possível, inclua um relato cristão histórico, ilustração ou exemplo que se conecte diretamente ao tema do texto.
- Em seguida, apresente brevemente o livro bíblico até o capítulo atual, quando for possível:
  - resuma o livro
  - mostre a relação do capítulo e da passagem com o contexto anterior e posterior
- Depois, estabeleça, quando possível, o contexto:
  - histórico
  - político
  - geográfico
- A introdução deve preparar o ouvinte para entender o fluxo do sermão, sem entrar ainda em explicações detalhadas de cada versículo.

### Desenvolvimento

- Explique como o tema pode ser visto ao longo da Bíblia.
- Separe o desenvolvimento em blocos citando outros textos bíblicos que possam corroborar com a exposição.
- Para cada bloco:
  - apresente o resumo do conteúdo do bloco
  - explique o sentido original (gramatical, histórico e teológico, quando possível)
  - mostre conexões com o contexto imediato e com o restante da Bíblia
- Evite discussões técnicas muito longas; traduza conceitos teológicos em linguagem acessível.

### Aplicações

- Crie entre **3 e 6** aplicações, nunca menos de 3 nem mais de 6.
- Cada aplicação deve ter:
  - um subtítulo em formato de ponto (curto, prático e memorável)
  - um parágrafo de aplicação com profundidade, porém em linguagem coloquial
- Para cada aplicação:
  - conecte, sempre que possível, com a pessoa e a obra de Jesus Cristo,
    mostrando:
    - seu exemplo
    - a tipologia
    - a alegoria
    - ou qualquer relação cristocêntrica presente no texto
  - mostre como o ouvinte hoje pode responder à verdade bíblica apresentada
- O parágrafo de cada aplicação deve ter, aproximadamente, de 10 a 15 linhas
  considerando uma página A4 em formato retrato (um parágrafo robusto,
  não apenas 2 ou 3 frases).
- Foque em aplicações para:
  - mente (crenças e convicções)
  - coração (afetos, desejos)
  - vontade (decisões, práticas concretas)

### Conclusão

- Faça uma conclusão que retome os principais pontos do sermão, de forma breve.
- Inclua elementos de apelo:
  - chamado à conversão
  - chamado ao arrependimento
  - chamado à entrega total a Jesus Cristo
- Mostre, na conclusão, todas as conexões possíveis entre o texto e:
  - a pessoa de Jesus
  - sua vida
  - sua obra redentora
  - sua cruz e ressurreição
- Termine com um tom pastoral e convidativo, adequado para um apelo em um culto cristão.

## Formato de saída em HTML

Depois de escrever o sermão em texto corrido, gere **também** o HTML seguindo exatamente a seguinte estrutura, preenchendo os campos com o conteúdo produzido:

```html
<div class="tudo" style="text-align: justify">
  <h1 style="font-weight: bolder; text-transform: uppercase">TÍTULO DO SERMÃO</h1>

  <h2 style="font-weight: bold;">Referência Bíblica</h2>
  <p>REFERÊNCIA BÍBLICA (ex.: João 3.16-21 - NAA)</p>

  <h2 style="font-weight: bold;">Introdução</h2>
  <p>TEXTO DA INTRODUÇÃO</p>

  <h2 style="font-weight: bold;">Desenvolvimento</h2>
  <p>TEXTO DO DESENVOLVIMENTO</p>

  <h2 style="font-weight: bold;">Aplicações</h2>

  <h3 style="font-style: italic">TÍTULO DA APLICAÇÃO 1</h3>
  <p>TEXTO DA APLICAÇÃO 1</p>

  <h3 style="font-style: italic">TÍTULO DA APLICAÇÃO 2</h3>
  <p>TEXTO DA APLICAÇÃO 2</p>

  <!-- Repita o padrão até a aplicação 6, se houver -->

  <h2 style="font-weight: bold;">Conclusão</h2>
  <p>TEXTO DA CONCLUSÃO</p>
</div>
