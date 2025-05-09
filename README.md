# Shopify App - Sistema de Renderização de Temas

## Visão Geral do Projeto

Este é um projeto pessoal que estou desenvolvendo: uma aplicação Laravel que replica o sistema de renderização de temas da Shopify. O objetivo principal é entender e implementar o mecanismo que permite que temas Liquid funcionem de maneira similar à plataforma original da Shopify.

**Nota:** Este projeto não é afiliado à Shopify e é desenvolvido exclusivamente para fins educacionais e de aprendizado.

## O Que Já Foi Implementado

Até o momento, consegui implementar:

- **Sistema de renderização base**: Permite carregar e renderizar templates Liquid
- **Controlador principal de temas (ThemeController)**: Gerencia a renderização dos templates e injeção de dados
- **Controlador específico para produtos (ProductController)**: Gerencia a visualização de páginas de produto
- **Sistema de tradução**: Implementação inicial para suporte a múltiplos idiomas
- **Resolução de rotas**: Sistema para associar URLs a templates específicos
- **Tags e filtros básicos do Liquid**: Implementação inicial de alguns dos principais tags e filtros

## Como Funciona

### Arquitetura do Sistema

O sistema é baseado em Laravel e utiliza a biblioteca Liquid para PHP. A arquitetura é modular, dividida em serviços especializados:

- **ThemeController**: É o controlador principal que processa requisições, determina qual template deve ser renderizado, e coordena todo o processo de renderização.

- **ProductController**: Um controlador especializado para renderizar páginas de produto, que lida com detalhes específicos como variantes de produto, galeria de imagens, etc.

- **Serviços de Suporte**:
  - `ThemeContextManager`: Constrói o contexto necessário para renderização (variáveis disponíveis nos templates)
  - `ThemeSettingsService`: Gerencia as configurações de tema (settings_schema.json e settings_data.json)
  - `ThemeTranslationManager`: Gerencia as traduções e localização
  - `ThemeAssetManager`: Gerencia ativos como CSS, JavaScript e imagens
  - `GlobalObjectsProvider`: Fornece objetos globais para o contexto Liquid
  - `ThemeContentService`: Gera conteúdo específico para diferentes partes do tema
  - `LiquidTagsAndFiltersManager`: Registra e gerencia tags e filtros personalizados

### Fluxo de Renderização

1. Uma requisição chega ao sistema (ex: `/products/camiseta-azul`)
2. O router do Laravel direciona a requisição para o controlador apropriado
3. O controlador identifica o tema ativo para a loja
4. O sistema resolve o template correto a ser usado (ex: `product.json`)
5. Um contexto é construído com todos os dados necessários (produto, loja, configurações)
6. O motor Liquid renderiza o template com o contexto fornecido
7. Pós-processamento é aplicado (substituição de URLs, otimizações)
8. O HTML final é retornado para o navegador

## Instalação e Configuração

### Pré-requisitos

- PHP 8.0+
- Composer
- MySQL ou outro banco de dados compatível
- Extensões PHP: BCMath, Ctype, Fileinfo, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML
- Node.js e NPM (para compilação de assets)

### Passos de Instalação

```bash
# Clone o repositório
git clone https://github.com/seu-usuario/shopify-app.git
cd shopify-app

# Instale dependências PHP
composer install

# Configure o ambiente
cp .env.example .env
php artisan key:generate

# Edite o arquivo .env com suas configurações de banco de dados

# Execute migrações
php artisan migrate
```

### Estrutura de Diretórios do Tema

Os temas são armazenados no filesystem do Laravel e seguem uma estrutura similar à da Shopify:

```
storage/app/themes/
  ├── {store_id}/                # ID da loja
  │   ├── {theme_id}/            # ID do tema
  │   │   ├── assets/            # CSS, JS, imagens e outros arquivos estáticos
  │   │   ├── config/            # Arquivos de configuração (settings_schema.json)
  │   │   ├── layout/            # Layouts (theme.liquid)
  │   │   ├── locales/           # Arquivos de tradução (pt-BR.json, en.json)
  │   │   ├── sections/          # Seções do tema (header.liquid, footer.liquid)
  │   │   ├── snippets/          # Snippets reutilizáveis (product-card.liquid)
  │   │   └── templates/         # Templates para páginas específicas
  │   │       ├── index.liquid   # Página inicial
  │   │       ├── product.liquid # Página de produto
  │   │       ├── collection.liquid # Página de coleção
  │   │       └── page.liquid    # Página de conteúdo
```

## Funcionalidades Técnicas Detalhadas

### Sistema de Tags Liquid

Implementei as seguintes tags Liquid:

- `if`, `else`, `elsif`, `endif`: Lógica condicional
- `for`, `endfor`: Loops com suporte para `limit`, `offset` e `reversed`
- `assign`: Atribuição de variáveis
- `include`: Para incluir snippets
- `render`: Versão melhorada do include
- `section`: Para incluir seções de tema

### Sistema de Filtros

Implementei os seguintes filtros:

- `asset_url`: Gera URLs para ativos do tema
- `img_url`: Gera URLs para imagens com suporte a dimensionamento
- `money`: Formatação monetária
- `translate`: Tradução de textos

### Contexto de Template

O sistema constrói automaticamente diversos objetos de contexto, incluindo:

- `shop`: Informações sobre a loja
- `product`: Detalhes do produto (na página de produto)
- `collection`: Detalhes da coleção (na página de coleção)
- `cart`: Conteúdo do carrinho
- `settings`: Configurações do tema
- `request`: Detalhes da requisição atual
- `locale`: Informações de localização

### Renderização de Produto

O `ProductController` implementa várias funcionalidades específicas:

- Carregamento de dados do produto
- Processamento de mídia (imagens, vídeos)
- Suporte para variantes de produto
- Galeria de imagens com suporte para zoom
- Cálculo de preços com descontos

## Desafios Atuais e Próximos Passos

Estou trabalhando ativamente nos seguintes desafios:

1. **Modularidade completa das tags**: Algumas tags não estão completamente modulares e precisam ser refatoradas.

2. **Implementação de filtros avançados**: Vários filtros ainda precisam ser implementados.

3. **Visualizador de produtos**: A renderização de páginas de produto ainda precisa de melhorias para ser totalmente compatível com qualquer tema.

4. **Visualizador de página inicial**: A renderização da página inicial também precisa de aprimoramentos.

5. **Sistema de cache**: Implementação de cache para melhorar o desempenho.

## Licença

Este projeto é licenciado sob a licença MIT - veja o arquivo [LICENSE](LICENSE) para detalhes.

## Inspiração e Agradecimentos

Este projeto foi inspirado pelo sistema de temas da Shopify e pelo mecanismo de template Liquid. Agradeço à comunidade Laravel e aos desenvolvedores das bibliotecas utilizadas neste projeto.

---

**Nota Legal**: Este projeto não é afiliado, associado, autorizado, endossado por, ou de qualquer forma oficialmente conectado à Shopify Inc. ou qualquer de suas subsidiárias ou afiliadas. Os nomes Shopify bem como nomes relacionados, marcas, emblemas e imagens são marcas registradas de seus respectivos proprietários.