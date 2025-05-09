<?php

namespace App\Liquid\CustomTags;

use Liquid\AbstractTag;
use Liquid\Context;
use App\Services\TranslationService;
use Illuminate\Support\Facades\Log;

/**
 * {% schema %} ... {% endschema %}
 * Captura o JSON bruto no corpo do bloco, grava em $context->registers['section_schema']
 * e também disponibiliza o schema diretamente no contexto como variável acessível.
 * 
 * Implementado conforme a documentação do Shopify.
 */
class SchemaTag extends AbstractTag
{
    /**
     * @var TranslationService
     */
    protected $translationService;
    
    /**
     * Construtor
     *
     * @param string $markup
     * @param array $tokens
     * @param mixed $fileSystem
     */
    public function __construct($markup, &$tokens, $fileSystem = null)
    {
        parent::__construct($markup, $tokens, $fileSystem);
        
        // Obter o serviço de tradução do container
        $this->translationService = app(TranslationService::class);
    }
    
    /**
     * Nome da tag no parser do Liquid.
     */
    public static function tagName(): string
    {
        return 'schema';
    }

    /**
     * Parseia o conteúdo entre {% schema %} e {% endschema %}
     * Garante que a tag schema não possa ser aninhada dentro de outra.
     */
    public function parse(array &$tokens)
    {
        $this->nodelist = [];
        $schemaContent = '';
        
        // A tag schema não deve ser aninhada, então usamos um contador simples
        // para verificar se encontramos endschema do nível correto
        while (count($tokens) > 0) {
            $token = array_shift($tokens);
            
            // Verificar se é uma tag endschema
            if (is_string($token) && preg_match('/\{%\s*endschema\s*%\}/', $token)) {
                break;
            }
            
            // Acumula o conteúdo se não for uma tag schema/endschema
            if (!preg_match('/\{%\s*(end)?schema\s*%\}/', $token)) {
                $schemaContent .= $token;
            }
        }
        
        // Armazena o conteúdo na propriedade nodelist
        $this->nodelist[] = trim($schemaContent);
        
        return $this;
    }

    /**
     * Renderiza o conteúdo do schema.
     * Não produz saída HTML visível, mas armazena os dados para uso posterior
     * e também disponibiliza no contexto Liquid.
     */
    public function render(Context $context): string
    {
        // Junta todos os tokens coletados
        $rawJson = trim(implode('', $this->nodelist));
        
        // Remove possíveis comentários Liquid do schema
        $rawJson = preg_replace('/{%-?\s*comment\s*-?%}.*?{%-?\s*endcomment\s*-?%}/s', '', $rawJson);
        $rawJson = preg_replace('/{#.*?#}/', '', $rawJson);
        
        try {
            // Verifica se o JSON é válido antes de decodificar
            if (!empty($rawJson)) {
                // Usa flags para evitar o erro de array para string
                $data = json_decode($rawJson, true, 512, JSON_THROW_ON_ERROR);
              
                
                // Processa strings de tradução no schema
                $data = $this->processTranslations($data, $context);
                
                // Importante: definir o schema diretamente no contexto para que
                // seja acessível nos templates Liquid
                $context->set('section_schema', $data);
                
                // Registra o schema para uso posterior nos registers
                $context->registers['section_schema'] = $data;
                
                // Também aplicar configurações padrão ao contexto
                $this->applyDefaultSettings($context, $data);
                
            }
        } catch (\JsonException $e) {
            // Registra o erro sem interromper o fluxo
            Log::error("Erro ao decodificar JSON no schema: " . $e->getMessage(), [
                'error' => $e->getMessage(),
                'raw_json_sample' => substr($rawJson, 0, 500)
            ]);
        } catch (\Exception $e) {
            Log::error("Erro geral no processamento do schema: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
        }

        // Não gera saída HTML (conforme documentação)
        return '';
    }
    
    /**
     * Processa recursivamente strings de tradução no schema.
     * 
     * @param mixed $data Os dados do schema
     * @param Context $context O contexto Liquid
     * @return mixed Os dados com as traduções processadas
     */
    private function processTranslations($data, Context $context)
    {
        // Se o serviço de tradução não estiver disponível, não faz nada
        if (!$this->translationService) {
            return $data;
        }
        
        // Processa as traduções usando o serviço
        try {
            $processed = $this->translationService->processTranslations($data, $context);
            return $processed;
        } catch (\Exception $e) {
            Log::error("Erro ao processar traduções no schema: " . $e->getMessage());
            return $data;
        }
    }
    
    /**
     * Aplica as configurações padrão do schema ao contexto
     * para que possam ser acessadas diretamente via {{ settings.X }}
     * 
     * @param Context $context O contexto Liquid
     * @param array $data Os dados do schema
     */
    private function applyDefaultSettings(Context $context, array $data): void
    {
        // Verifica se temos settings no schema
        if (!isset($data['settings']) || !is_array($data['settings'])) {
            return;
        }
        
        // Obtém as configurações atuais do contexto, ou inicializa como array vazio
        $settings = $context->get('settings');
        if (!is_array($settings)) {
            $settings = [];
        }
        
        // Processa cada configuração no schema
        foreach ($data['settings'] as $setting) {
            // Verifica se temos id e valor padrão
            if (isset($setting['id']) && isset($setting['default'])) {
                $id = $setting['id'];
                $default = $setting['default'];
                
                // Só aplica o valor padrão se não existir no contexto
                if (!isset($settings[$id])) {
                    $settings[$id] = $default;
                }
            }
        }
        
        // Também processa blocos padrão se estiverem presentes
        if (isset($data['blocks']) && is_array($data['blocks'])) {
            // Ver se temos um bloco section no contexto
            $section = $context->get('section');
            if (is_array($section)) {
                // Se não existirem blocos na seção, adiciona os padrões
                if (!isset($section['blocks']) || empty($section['blocks'])) {
                    $defaultBlocks = [];
                    $defaultBlockOrder = [];
                    
                    foreach ($data['blocks'] as $blockType => $blockData) {
                        // Verifica se há blocos padrão definidos nos presets
                        if (isset($data['presets']) && isset($data['presets'][0]['blocks'])) {
                            foreach ($data['presets'][0]['blocks'] as $presetBlock) {
                                if ($presetBlock['type'] === $blockType) {
                                    $blockId = 'block_' . uniqid();
                                    $defaultBlocks[$blockId] = [
                                        'type' => $blockType,
                                        'settings' => []
                                    ];
                                    
                                    // Aplica configurações padrão ao bloco
                                    if (isset($blockData['settings'])) {
                                        foreach ($blockData['settings'] as $blockSetting) {
                                            if (isset($blockSetting['id']) && isset($blockSetting['default'])) {
                                                $defaultBlocks[$blockId]['settings'][$blockSetting['id']] = 
                                                    $blockSetting['default'];
                                            }
                                        }
                                    }
                                    
                                    $defaultBlockOrder[] = $blockId;
                                }
                            }
                        }
                    }
                    
                    // Se encontrarmos blocos padrão, atualiza a seção
                    if (!empty($defaultBlocks)) {
                        $section['blocks'] = $defaultBlocks;
                        $section['block_order'] = $defaultBlockOrder;
                        $context->set('section', $section);
                    }
                }
            }
        }
        
        // Atualiza as configurações no contexto
        $context->set('settings', $settings);
        
        // Também registra para acesso via context.registers
        $context->registers['settings'] = $settings;
    }
}