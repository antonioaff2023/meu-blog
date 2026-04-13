# Buscador Bíblico Multi-Versão (PHP + SQLite)

Sistema web completo para busca em múltiplas versões da Bíblia.  
Desenvolvido para rodar em XAMPP, servidor local ou qualquer servidor PHP 8.1+.

---

## Requisitos

- PHP 8.1 ou superior
- Extensão `pdo_sqlite` habilitada (padrão no XAMPP)
- SQLite 3 (embutido no PHP)

---

## Instalação

```bash
# 1. Copie a pasta para o htdocs do XAMPP (ou raiz do servidor)
#    Exemplo: C:\xampp\htdocs\biblia-buscador\
#    Ou:      /var/www/html/biblia-buscador/

# 2. Coloque seus arquivos SQLite em:
data/bibles/acf.sqlite
data/bibles/nvi.sqlite
data/bibles/ara.sqlite
# ... etc.

# 3. Importe para o banco central
php import/import.php

# 4. Acesse no navegador
http://localhost/biblia-buscador/
```

---

## Comandos de importação

```bash
# Importa todas as versões configuradas
php import/import.php

# Importa versões específicas
php import/import.php NVI ACF KJV

# Lista todas as versões configuradas e se o arquivo existe
php import/import.php --list

# Exibe estatísticas do banco central
php import/import.php --stats

# Inspeciona o schema de um SQLite externo
php import/import.php --inspect data/bibles/meu-arquivo.sqlite

# Gera um SQLite de exemplo para teste rápido
php import/generate_sample.php
```

---

## Schemas SQLite suportados (auto-detectados)

| Schema | Formato | Tabelas/Colunas |
|--------|---------|----------------|
| **A** | Open.Bible / theographic | `bible_books` + `bible_verses` |
| **B** | bibliadigital.com.br | `books(id,name,abbrev)` + `verses(book_id,chapter,verse,text)` |
| **C** | Popular GitHub SQLite | `verse(b, c, v, t)` |
| **D** | YouVersion / eb.lc | `t_acf(b, c, v, t)` |
| **E** | Genérico | Qualquer tabela com colunas book/chapter/verse/text |

Se o seu arquivo não for reconhecido, execute:
```bash
php import/import.php --inspect data/bibles/seu-arquivo.sqlite
```
Isso mostrará as tabelas e colunas. Em seguida, crie um adapter customizado em `includes/adapters/`.

---

## Onde obter arquivos SQLite bíblicos

| Fonte | URL | Versões |
|-------|-----|---------|
| thiagobodruk/biblia | https://github.com/thiagobodruk/biblia | ACF, ARA, NVI e outras |
| scrollmapper/bible_databases | https://github.com/scrollmapper/bible_databases | KJV e outras |
| ph4.org (mobile) | https://www.ph4.org/b4_mobi.php | Várias versões |
| eb.lc | https://github.com/the-rccg/YouVersion-sqlite | Formato YouVersion |

> **Atenção:** Verifique a licença de cada versão antes de distribuir.  
> Versões como NVI e NVT têm copyright restrito.

---

## Adicionar uma nova versão

1. Edite `includes/config.php` e adicione na constante `BIBLE_VERSIONS`:
   ```php
   'MINHA' => ['label' => 'Minha Tradução', 'file' => 'minha.sqlite'],
   ```
2. Coloque o arquivo em `data/bibles/minha.sqlite`
3. Execute `php import/import.php MINHA`

---

## Estrutura do projeto

```
biblia-buscador/
├── index.php              ← Interface principal
├── search.php             ← API JSON (busca via AJAX)
├── includes/
│   ├── config.php         ← Configurações (versões, livros canônicos)
│   ├── BibleDB.php        ← Gerenciador do banco central
│   └── adapters/
│       ├── AdapterInterface.php
│       ├── AdapterFactory.php   ← Detecção automática de schema
│       ├── SingleTableAdapter.php
│       ├── BooksVersesAdapter.php
│       ├── YouVersionAdapter.php
│       └── GenericAdapter.php
├── import/
│   ├── import.php         ← CLI de importação
│   └── generate_sample.php← Gera dados de exemplo
├── data/
│   ├── central.sqlite     ← Banco central unificado (gerado automaticamente)
│   └── bibles/            ← Coloque seus .sqlite aqui
├── assets/
│   ├── css/style.css
│   └── js/app.js
└── cache/                 ← Reservado para cache futuro
```

---

## API de busca (search.php)

Pode ser consumida diretamente via HTTP:

```
# Busca por palavra-chave
GET search.php?mode=keyword&q=graça&versions=ACF,NVI&page=1&per_page=50

# Busca por referência
GET search.php?mode=reference&book=43&chapter=3&verse=16&versions=ACF,NVI,ARA

# Capítulo completo
GET search.php?mode=reference&book=43&chapter=3&versions=NVI

# Capítulos disponíveis num livro
GET search.php?mode=book&book=43

# Versões disponíveis no banco
GET search.php?mode=versions

# Estatísticas gerais
GET search.php?mode=stats
```

---

## Funcionalidades

- **Busca por palavra-chave** com filtro por livro e versão
- **Busca por referência** (Jo 3:16, Rm 8:28…) com parser inteligente
- **Navegação por passagem** — capítulo completo com seletor de livro/capítulo
- **Comparação de versões** — mesmo versículo em todas as versões lado a lado
- **Paginação** automática nos resultados de busca
- **Copiar / Exportar** resultados em .txt
- **Destaque** do termo buscado nos resultados
- **Banco central unificado** — importação incremental, sem duplicatas

---

## Créditos

Desenvolvido com **Perplexity Computer** — https://www.perplexity.ai/computer
