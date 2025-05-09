<?php

namespace App\Liquid\CustomTags;

use Liquid\AbstractTag;
use Liquid\Context;
use Liquid\FileSystem;
use Liquid\Template;
use Liquid\Exception\ParseException;
use Liquid\Exception\NotFoundException;
use Liquid\Regexp;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SectionsTag extends AbstractTag
{
    /**
     * Nome do grupo de sections
     */
    private $groupName;
    
    /**
     * Sistema de arquivos para acesso aos templates
     */
    protected $fileSystem;
    
    /**
     * Store ID e Theme ID
     */
    private $storeId;
    private $themeId;
    
    /**
     * Constructor para a tag sections
     */
    public function __construct($markup, array &$tokens, FileSystem $fileSystem = null)
    {
        parent::__construct($markup, $tokens, $fileSystem);
        $this->fileSystem = $fileSystem;
        $this->parseGroupName($markup);
        
        // Não tentamos obter store_id e theme_id aqui
        // Eles serão obtidos no método render()
    }

    /**
     * Nome da tag
     */
    public static function tagName(): string
    {
        return 'sections';
    }

    /**
     * Parse o nome do grupo a partir do markup
     */
    private function parseGroupName(string $markup): void
    {
        $regex = new Regexp('/["\']?([\w-]+)["\']?/');
        if ($regex->match($markup)) {
            $this->groupName = $regex->matches[1];
        } else {
            throw new ParseException("Syntax Error in 'sections' - Missing group name");
        }
    }

    /**
     * Renderiza a tag sections
     */
    public function render(Context $context): string
    {
        try {
            // Obter store_id e theme_id do contexto Liquid
            $this->storeId = $context->get('theme.store_id');
            $this->themeId = $context->get('theme.id');
            
            // Log dos valores para debug
            
            if (!$this->storeId || !$this->themeId) {
                // Se não encontrados no contexto, tenta buscar diretamente
                $theme = $context->get('theme');
                
                if (is_array($theme)) {
                    if (isset($theme['store_id'])) $this->storeId = $theme['store_id'];
                    if (isset($theme['id'])) $this->themeId = $theme['id'];
                }
                
            }
            
            if (!$this->storeId || !$this->themeId) {
                // Ainda não conseguimos encontrar, retorna erro
                return "<!-- Erro: store_id ou theme_id não disponíveis no contexto Liquid -->";
            }
            
            // Carrega o JSON de configuração do grupo usando Storage
            $groupConfig = $this->loadGroupConfig();
            
            // Renderiza as sections conforme a ordem definida no JSON
            $renderedSections = $this->renderSections($groupConfig, $context);
            
            // Retorna o conteúdo renderizado dentro de um wrapper
            return $this->wrapGroup($renderedSections, $groupConfig);
        } catch (\Exception $e) {
            Log::warning("Error rendering sections group: {$this->groupName} - " . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            // Retorna comentário em HTML para debug
            return "<!-- Error loading sections group '{$this->groupName}': " . $e->getMessage() . " -->";
        }
    }
    
    /**
     * Carrega a configuração JSON do grupo usando Storage do Laravel
     */
    private function loadGroupConfig(): array
    {
        // Construir caminho completo para o arquivo
        $path = "{$this->storeId}/{$this->themeId}/sections/{$this->groupName}.json";
        
        
        // Verifica se o arquivo existe no storage
        if (!Storage::disk('themes')->exists($path)) {
            throw new NotFoundException("Sections group configuration not found at: {$path}");
        }
        
        // Lê o conteúdo do arquivo
        $content = Storage::disk('themes')->get($path);
        
        // Processa o conteúdo JSON
        $config = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Invalid JSON in group configuration: " . json_last_error_msg());
        }
        
        
        return $config;
    }

    // Os outros métodos permanecem iguais...
    
    /**
     * Renderiza as sections individuais baseadas na configuração do grupo
     */
    private function renderSections(array $groupConfig, Context $context): string
    {
        $output = '';
        
        // Verifica se temos sections e order no config
        if (!isset($groupConfig['sections']) || !isset($groupConfig['order'])) {
            throw new \Exception("Invalid group configuration: missing sections or order");
        }
        
        // Processa as sections na ordem definida
        foreach ($groupConfig['order'] as $sectionId) {
            if (isset($groupConfig['sections'][$sectionId])) {
                $sectionConfig = $groupConfig['sections'][$sectionId];
                $output .= $this->renderSection($sectionId, $sectionConfig, $context);
            } else {
                $output .= "<!-- Section '{$sectionId}' referenced in order but not defined -->";
            }
        }
        
        return $output;
    }

    /**
     * Carrega o template da section usando Storage do Laravel
     */
    private function loadSectionTemplate(string $sectionType): string
    {
        // Construir caminho completo para o arquivo
        $path = "{$this->storeId}/{$this->themeId}/sections/{$sectionType}.liquid";
        

        // Verifica se o arquivo existe no storage
        if (!Storage::disk('themes')->exists($path)) {
            // Tenta também procurar no diretório snippets como fallback
            $snippetPath = "{$this->storeId}/{$this->themeId}/snippets/{$sectionType}.liquid";
            
            if (Storage::disk('themes')->exists($snippetPath)) {
                // Registra o caminho encontrado para debug
                return Storage::disk('themes')->get($snippetPath);
            }
            
            throw new NotFoundException("Section template not found at: {$path} or in snippets");
        }
        
        // Lê o conteúdo do arquivo
        $content = Storage::disk('themes')->get($path);
        
        
        return $content;
    }

    /**
     * Renderiza uma section individual com otimização de memória
     */
    private function renderSection(string $sectionId, array $sectionConfig, Context $context): string
    {
        // Verifica se o tipo de section está definido
        if (!isset($sectionConfig['type'])) {
            return "<!-- Missing type for section '{$sectionId}' -->";
        }
        
        $sectionType = $sectionConfig['type'];
        
        try {
            // Carrega o template da section
            $sectionTemplate = $this->loadSectionTemplate($sectionType);
            
            // Process blocks according to Shopify's expected structure if they exist
            $blocks = [];
            $blocksByIndex = [];
            
            if (isset($sectionConfig['blocks']) && isset($sectionConfig['block_order'])) {
                // Add blocks in correct order with proper attributes
                foreach ($sectionConfig['block_order'] as $index => $blockId) {
                    if (isset($sectionConfig['blocks'][$blockId])) {
                        $block = $sectionConfig['blocks'][$blockId];
                        $block['id'] = $blockId;
                        $block['shopify_attributes'] = "data-shopify-editor-block=\"{$blockId}\"";
                        
                        // Add to sequential array (for iteration)
                        $blocksByIndex[] = $block;
                        
                        // Also add to associative array (for direct access)
                        $blocks[$blockId] = $block;
                    }
                }
                
                // Set size property
                $size = count($blocksByIndex);
                $blocks['size'] = $size;
                
                // Add helper properties that match Shopify's Liquid behavior
                if ($size > 0) {
                    $blocks['first'] = $blocksByIndex[0];
                    $blocks['last'] = $blocksByIndex[$size - 1];
                    
                    // Add all blocks as indexed elements for for-loop access
                    for ($i = 0; $i < $size; $i++) {
                        $blocks[$i] = $blocksByIndex[$i];
                    }
                }
            } else {
                // Initialize with empty blocks collection
                $blocks = ['size' => 0];
            }
            
            // Prepara os dados da section
            $sectionData = [
                'id' => $sectionId,
                'type' => $sectionType,
                'settings' => $sectionConfig['settings'] ?? [],
                'blocks' => $blocks,
                'block_order' => $sectionConfig['block_order'] ?? []
            ];
            
            // Cria uma cópia dos assigns atuais do contexto
            $contextAssigns = $context->getAll();
            
            // Adiciona os dados da section aos assigns
            $contextAssigns['section'] = $sectionData;
            
            // IMPORTANTE: Garantir que os registros do contexto estão sendo passados
            $contextAssigns['registers'] = $context->registers;
            
            // Renderiza o template da section com processamento otimizado
            $rendered = $this->renderTemplateOptimized($sectionTemplate, $contextAssigns);
            
            // Envolve o conteúdo renderizado em um div com os atributos apropriados
            return $this->wrapSection($sectionId, $sectionType, $rendered);
        } catch (\Exception $e) {
            return "<!-- Error rendering section '{$sectionId}' of type '{$sectionType}': " . $e->getMessage() . " -->";
        }
    }

    /**
     * Renderiza o template com otimização de memória
     */
    private function renderTemplateOptimized(string $template, array $assigns): string
    {
        // Limitar uso de memória
        $originalLimit = ini_get('memory_limit');
        if ((int)$originalLimit > 256) {
            ini_set('memory_limit', '256M');
        }
        
        // Cria um novo template para este processamento específico
        $templateObj = new Template();
        
        if ($this->fileSystem) {
            $templateObj->setFileSystem($this->fileSystem);
        }
        
        try {
            // Força coleta de lixo antes de processar
            gc_collect_cycles();
            
            // IMPORTANTE: Criar um contexto Liquid adequado para os filtros
            $liquidContext = new \Liquid\Context();
        
            // Adiciona os assigns ao contexto
            foreach ($assigns as $key => $value) {
                $liquidContext->set($key, $value);
            }
            
            // IMPORTANTE: Verificar e adicionar explicitamente as traduções
            if (isset($assigns['registers']) && isset($assigns['registers']['translations'])) {
                $liquidContext->registers['translations'] = $assigns['registers']['translations'];
                $liquidContext->registers['current_locale'] = $assigns['registers']['current_locale'] ?? 'pt-BR';
            }
                
            // Garante que o contexto tenha as informações do tema
            if (!isset($assigns['theme']) || !is_array($assigns['theme']) || 
                !isset($assigns['theme']['store_id']) || !isset($assigns['theme']['id'])) {
                $liquidContext->set('theme', [
                    'id' => $this->themeId,
                    'store_id' => $this->storeId,
                    'name' => $assigns['theme']['name'] ?? 'Theme',
                    'role' => $assigns['theme']['role'] ?? 'main'
                ]);
            }
            
            // Registra os filtros personalizados usando o contexto
            $customFilters = new \App\Liquid\CustomFilters($liquidContext);
            $templateObj->registerFilter($customFilters);
            
            // CORREÇÃO CRUCIAL: Registrar explicitamente o filtro de tradução 't'
            $templateObj->registerFilter('t', function ($input, $params = []) use ($liquidContext) {
                // Remover prefixo 't:' se existir
                $key = is_string($input) && strpos($input, 't:') === 0 
                    ? substr($input, 2) 
                    : $input;
                
                // Se a entrada não for string, retornar como está
                if (!is_string($input)) {
                    return $input;
                }
                
                // Verificar se temos traduções no contexto atual
                $translationFound = false;
                $translatedValue = $key;
                
                if (isset($liquidContext->registers['translations'])) {
                    $translations = $liquidContext->registers['translations'];
                    $parts = explode('.', $key);
                    
                    // Tenta encontrar a tradução na língua atual
                    $value = $translations;
                    $allPartsFound = true;
                    
                    foreach ($parts as $part) {
                        if (!isset($value[$part])) {
                            $allPartsFound = false;
                            break;
                        }
                        $value = $value[$part];
                    }
                    
                    if ($allPartsFound && is_string($value)) {
                        $translatedValue = $value;
                        $translationFound = true;
                    }
                }
                
                // Se não encontrou tradução, busca no arquivo en.default.json (fallback)
                if (!$translationFound) {
                    // Obter o diretório do tema atual
                    $themePath = "";
                    $theme = $liquidContext->get('theme');
                    
                    // Verificar se theme existe e tem os campos necessários
                    if ($theme && is_array($theme) && isset($theme['store_id']) && isset($theme['id'])) {
                        $themePath = "{$theme['store_id']}/{$theme['id']}";
                    }
                    
                    // Se temos o caminho do tema, tenta carregar o arquivo de fallback
                    if (!empty($themePath)) {
                        static $defaultTranslations = null;
                        
                        // Carrega o arquivo apenas uma vez por requisição para melhorar performance
                        if ($defaultTranslations === null) {
                            try {
                                $defaultFile = "{$themePath}/locales/en.default.json";
                                if (Storage::disk('themes')->exists($defaultFile)) {
                                    $content = Storage::disk('themes')->get($defaultFile);
                                    $defaultTranslations = json_decode($content, true);
                                } else {
                                    // Tenta o arquivo en.json como alternativa
                                    $defaultFile = "{$themePath}/locales/en.json";
                                    if (Storage::disk('themes')->exists($defaultFile)) {
                                        $content = Storage::disk('themes')->get($defaultFile);
                                        $defaultTranslations = json_decode($content, true);
                                    } else {
                                        // Arquivo não encontrado, usar array vazio
                                        $defaultTranslations = [];
                                    }
                                }
                            } catch (\Exception $e) {
                                // Em caso de erro, usar array vazio
                                $defaultTranslations = [];
                                \Log::warning("Erro ao carregar traduções padrão: " . $e->getMessage());
                            }
                        }
                        
                        // Agora tenta encontrar a tradução no arquivo de fallback
                        if (!empty($defaultTranslations)) {
                            $parts = explode('.', $key);
                            $value = $defaultTranslations;
                            $allPartsFound = true;
                            
                            foreach ($parts as $part) {
                                if (!isset($value[$part])) {
                                    $allPartsFound = false;
                                    break;
                                }
                                $value = $value[$part];
                            }
                            
                            if ($allPartsFound && is_string($value)) {
                                $translatedValue = $value;
                                $translationFound = true;
                            }
                        }
                    }
                    
                    // Se ainda não encontrou, tente usar valores padrão específicos como último recurso
                    if (!$translationFound) {
                        $defaults = [
                            'sections.cart.checkout' => 'Check out',
                            'general.continue_shopping' => 'Continue shopping',
                            'sections.cart.title' => 'Your cart',
                            'sections.cart.empty' => 'Your cart is empty',
                            'sections.header.cart_count' => 'Cart',
                            'customer.log_in' => 'Log in',
                            'templates.cart.cart' => 'Cart',
                            // Adicione outros valores padrão conforme necessário
                        ];
                        
                        if (isset($defaults[$key])) {
                            $translatedValue = $defaults[$key];
                            $translationFound = true;
                        }
                    }
                }
                
                // Processar substituições de parâmetros
                if (!empty($params) && $translationFound) {
                    foreach ($params as $paramKey => $paramValue) {
                        $translatedValue = str_replace("{{ {$paramKey} }}", $paramValue, $translatedValue);
                    }
                }
                
                return $translatedValue;
            });
            
            // Registrar tags customizadas
            $this->registerCustomTags($templateObj);
            
            // Faz o parsing e renderização
            $templateObj->parse($template);
            $result = $templateObj->render($liquidContext->getAll());
            
            // Processa qualquer referência a assets que não foi tratada pelo Liquid
            $result = $this->postProcessAssetReferences($result);
            
            // Libera memória
            unset($templateObj);
            gc_collect_cycles();
            
            return $result;
        } catch (\Exception $e) {
            // Garante que o template seja liberado mesmo em caso de erro
            unset($templateObj);
            gc_collect_cycles();
            
            // Restaurar limite de memória
            if ($originalLimit) {
                ini_set('memory_limit', $originalLimit);
            }
            
            throw $e;
        } finally {
            // Restaurar limite de memória
            if ($originalLimit) {
                ini_set('memory_limit', $originalLimit);
            }
        }
    }


private function postProcessAssetReferences(string $content): string
{
    // 1. Processar referências a SVGs em span.svg-wrapper
    $content = preg_replace_callback(
        '/<span class="svg-wrapper">([\w\-\.]+\.svg)<\/span>/i',
        function($matches) {
            $assetName = $matches[1];
            // Verificar se já é uma URL completa
            if (preg_match('/^(https?:)?\/\//i', $assetName)) {
                return $matches[0]; // Já é uma URL, não modificar
            }
            
            // Gerar URL completa para o asset
            $assetUrl = url("assets/{$this->storeId}/{$this->themeId}/{$assetName}");
            
            return "<span class=\"svg-wrapper\"><img src=\"{$assetUrl}\" alt=\"\"></span>";
        },
        $content
    );
    
    // 2. Processar referências a CSS em links, excluindo URLs que já são absolutas
    $content = preg_replace_callback(
        '/href="((?!https?:\/\/)(?!\/\/)[^"]+\.css)"/i',
        function($matches) {
            $cssPath = $matches[1];
            
            // Se já começa com "assets/", não adicionar o caminho novamente
            if (preg_match('/^assets\//i', $cssPath)) {
                return "href=\"{$cssPath}\"";
            }
            
            // Gerar URL completa para o CSS
            $cssUrl = url("assets/{$this->storeId}/{$this->themeId}/{$cssPath}");
            
            return "href=\"{$cssUrl}\"";
        },
        $content
    );
    
    // 3. Processar referências a JS em scripts, excluindo URLs que já são absolutas
    $content = preg_replace_callback(
        '/src="((?!https?:\/\/)(?!\/\/)[^"]+\.js)"/i',
        function($matches) {
            $jsPath = $matches[1];
            
            // Se já começa com "assets/", não adicionar o caminho novamente
            if (preg_match('/^assets\//i', $jsPath)) {
                return "src=\"{$jsPath}\"";
            }
            
            // Gerar URL completa para o JS
            $jsUrl = url("assets/{$this->storeId}/{$this->themeId}/{$jsPath}");
            
            return "src=\"{$jsUrl}\"";
        },
        $content
    );
    
    return $content;
}

private function registerCustomTags(Template $template): void
{
    // Registrar tags customizadas
    $template->registerTag('form', \App\Liquid\CustomTags\FormTag::class);
    $template->registerTag('style', \App\Liquid\CustomTags\StyleTag::class);
    $template->registerTag('section', \App\Liquid\CustomTags\SectionTag::class);
    $template->registerTag('liquid', \App\Liquid\CustomTags\LiquidTag::class);
    $template->registerTag('render', \App\Liquid\CustomTags\RenderTag::class);
    $template->registerTag('schema', \App\Liquid\CustomTags\SchemaTag::class);
    $template->registerTag('javascript', \App\Liquid\CustomTags\JavaScriptTag::class);
    $template->registerTag('sections', \App\Liquid\CustomTags\SectionsTag::class);
    
    // Registrar tags padrão do Liquid
    $template->registerTag('if', \Liquid\Tag\TagIf::class);
    $template->registerTag('for', \Liquid\Tag\TagFor::class);
    $template->registerTag('unless', \Liquid\Tag\TagUnless::class);
    $template->registerTag('case', \Liquid\Tag\TagCase::class);
    $template->registerTag('capture', \Liquid\Tag\TagCapture::class);
    $template->registerTag('comment', \Liquid\Tag\TagComment::class);
    $template->registerTag('include', \Liquid\Tag\TagInclude::class);
    // Outras tags padrão necessárias
}


    /**
     * Envolve uma section renderizada com o wrapper apropriado
     */
    private function wrapSection(string $sectionId, string $sectionType, string $content): string
    {
        return sprintf(
            '<section id="shopify-section-%s" class="shopify-section section section--%s" data-section-id="%s" data-section-type="%s">%s</section>',
            $sectionId,
            $sectionType,
            $sectionId,
            $sectionType,
            $content
        );
    }

    /**
     * Envolve o grupo inteiro de sections
     */
    private function wrapGroup(string $content, array $groupConfig): string
    {
        $groupType = $groupConfig['type'] ?? $this->groupName;
        
        return sprintf(
            '<section class="sections-group sections-group--%s" data-sections-group="%s">%s</section>',
            $groupType,
            $this->groupName,
            $content
        );
    }
}