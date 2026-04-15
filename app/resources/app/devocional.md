---
name: gerador-devocional-html
description: >
  Gere devocionais em português a partir de um texto bíblico, seguindo a
  estrutura Introdução, Desenvolvimento, Aplicações e Conclusão, usando
  sempre a versão NAA (Nova Almeida Atualizada) nas referências bíblicas
  e retornando o resultado diretamente em HTML no modelo especificado.
  Use quando o usuário pedir devocional com base em um texto bíblico
  com saída em HTML.
---

# Gerador de Devocional em HTML

## Quando usar

Use esta habilidade sempre que o usuário pedir:
- devocional baseada em um texto bíblico
- reflexão devocional com aplicações práticas
- texto curto para leitura pessoal ou em família
- saída em HTML usando um modelo pré-definido

Se o usuário não especificar a versão bíblica, assuma que sempre deve ser usada a NAA (Nova Almeida Atualizada) nas citações. Não reproduza longos trechos literais da Bíblia; prefira paráfrases curtas e referências.

## Instruções gerais

1. Leia com atenção o texto bíblico indicado e qualquer título ou tema sugerido pelo usuário.
2. Se o usuário não propuser um título, crie um título simples e inspirativo com no máximo 5 palavras.
3. Estruture a devocional sempre nestas partes, nesta ordem:
   - Introdução
   - Desenvolvimento
   - Aplicações
   - Conclusão
4. Trabalhe em linguagem pastoral, simples e calorosa, adequada à leitura devocional diária, mas com fidelidade bíblica e teológica.
5. Sempre que citar a Bíblia, utilize a versão NAA (Nova Almeida Atualizada), em forma de referência ou paráfrase breve.
6. No final, gere também um bloco de HTML seguindo exatamente o modelo informado.
7. A variável {{passagem}} não deverá ser preenchida, apenas constar para que o sistema preencha depois.

## Estrutura detalhada da devocional

### Introdução (máx. 100 palavras)

- Comece com uma introdução criativa e reflexiva relacionada ao texto ou assunto (ex.: uma pergunta, uma cena do cotidiano, uma breve ilustração).
- Quando possível, inclua um relato cristão histórico, ilustração ou exemplo que se conecte diretamente ao tema do texto.
- Em seguida, apresente brevemente o livro bíblico até o capítulo atual, quando for possível:
  - resuma o livro
  - mostre a relação do capítulo e da passagem com o contexto anterior e posterior
- Depois, estabeleça, quando possível, o contexto:
  - histórico
  - político
  - geográfico
- A introdução deve preparar o leitor para entender o fluxo da devocional, sem entrar ainda em explicações detalhadas de cada versículo.
- Use até 100 palavras.

### Desenvolvimento (máx. 100 palavras)

- Explique o texto de forma expositiva, versículo a versículo ou em blocos de versículos, com foco devocional.
- Separe o desenvolvimento em blocos conforme a estrutura natural do texto (por exemplo: vv. 1–3, vv. 4–7 etc.).
- Para cada bloco:
  - apresente o resumo do conteúdo do bloco
  - explique o sentido original (gramatical, histórico e teológico, quando possível)
  - mostre conexões com o contexto imediato e com o restante da Bíblia
- Evite discussões técnicas muito longas; traduza conceitos teológicos em linguagem simples e aplicável à vida diária.
- Use até 100 palavras.

### Aplicações (No máximo 50 palavras para cada aplicação)

- Crie entre **3 e 6** aplicações, nunca menos de 3 nem mais de 6.
- Cada aplicação deve ter:
  - um subtítulo em formato de ponto (curto, prático e memorável)
  - um parágrafo de aplicação em linguagem coloquial e pastoral
- Para cada aplicação:
  - conecte, sempre que possível, com a pessoa e a obra de Jesus Cristo,
    mostrando:
    - seu exemplo
    - a tipologia
    - a alegoria
    - ou qualquer relação cristocêntrica presente no texto
  - mostre como o leitor hoje pode responder à verdade bíblica apresentada
- Cada aplicação deve ter no máximo 50 palavras, em frases inspirativas e pastorais.


### Conclusão (máx. 100 palavras)

- Faça uma conclusão que retome os principais pontos da devocional, de forma breve.
- Inclua elementos de apelo:
  - chamado à conversão (quando adequado)
  - chamado ao arrependimento
  - chamado à entrega total a Jesus Cristo e à confiança diária nele
- Mostre, na conclusão, as conexões entre o texto e:
  - a pessoa de Jesus
  - sua vida
  - sua obra redentora
  - sua cruz e ressurreição
- Termine com um tom pastoral, consolador e convidativo, adequado à vida devocional pessoal ou familiar.
- Use até 100 palavras.

## Modelo html

<div class="sermon-block" style="text-align: justify">
  <h1 style="font-weight: bolder; text-transform: uppercase">TÍTULO DA DEVOCIONAL</h1>

  <p>{{passagem}}</p> <!-- Variável a ser preenchida pelo sistema -->

  <h2 style="font-weight: bold;">Introdução</h2>
  <p>TEXTO DA INTRODUÇÃO</p>

  <h2 style="font-weight: bold;">Desenvolvimento</h2>
  <p>TEXTO DO DESENVOLVIMENTO</p>

  <h2 style="font-weight: bold;">Aplicações</h2> <!-- Cada ponto das aplicações deve ser numerada com algarismos romanos minúsculos seguido de ')'-->

  <h3 style="font-style: italic">i) Ponto 1</h3>
  <p>TEXTO DO PONTO 1</p>

  <h3 style="font-style: italic">ii) Ponto 2</h3>
  <p>TEXTO DO PONTO 2</p>

  <!-- Repita o padrão para os demais pontos, até o ponto 6, se houver -->

  <h2 style="font-weight: bold;">Conclusão</h2>
  <p>TEXTO DA CONCLUSÃO</p>
</div>
