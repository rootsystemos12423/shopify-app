<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Liquid\Context;

/**
 * Serviço para gerenciar e aplicar traduções em temas Shopify
 */
class TranslationService
{
    /**
     * Tempo de vida do cache em segundos (2 horas)
     */
    const CACHE_TTL = 7200;

    /**
     * Idioma padrão quando nenhum é especificado
     */
    const DEFAULT_LOCALE = 'pt-BR';

    /**
     * Carregar todas as traduções para um idioma específico
     *
     * @param string $themePath Caminho para o diretório do tema
     * @param string $locale Código do idioma
     * @return array Dados de tradução
     */
    public function loadTranslations(string $themePath, string $locale = null): array
    {
        $locale = $locale ?? self::DEFAULT_LOCALE;
        
        // Verificar cache primeiro
        $cacheKey = "theme_translations_{$themePath}_{$locale}";
        if (Cache::has($cacheKey)) {
            $cached = Cache::get($cacheKey);
            Log::debug("Usando traduções em cache para {$locale}", [
                'cache_key' => $cacheKey,
                'item_count' => is_array($cached) ? count($cached) : 0
            ]);
            return $cached;
        }
        
        $translations = [];
        
        try {
            Log::debug("Carregando traduções para {$locale}", [
                'theme_path' => $themePath
            ]);
            
            // 1. Carregar arquivo principal do idioma (ex: pt-BR.json)
            $mainFile = "{$themePath}/locales/{$locale}.json";
            if (Storage::disk('themes')->exists($mainFile)) {
                $content = Storage::disk('themes')->get($mainFile);
                $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
                if (is_array($data)) {
                    Log::debug("Carregado arquivo principal de tradução", [
                        'file' => $mainFile,
                        'count' => count($data)
                    ]);
                    $translations = array_merge($translations, $data);
                }
            } else {
                Log::warning("Arquivo principal de tradução não encontrado", [
                    'file' => $mainFile
                ]);
            }
            
            // 2. Carregar arquivo schema (ex: pt-BR.schema.json)
            $schemaFile = "{$themePath}/locales/{$locale}.schema.json";
            if (Storage::disk('themes')->exists($schemaFile)) {
                $content = Storage::disk('themes')->get($schemaFile);
                $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
                if (is_array($data)) {
                    Log::debug("Carregado arquivo de schema", [
                        'file' => $schemaFile,
                        'count' => count($data)
                    ]);
                    
                    // Para schemas, mesclar com cuidado para preservar estrutura aninhada
                    foreach ($data as $key => $value) {
                        if (!isset($translations[$key])) {
                            $translations[$key] = $value;
                        } else if (is_array($translations[$key]) && is_array($value)) {
                            $translations[$key] = $this->mergeTranslationArrays($translations[$key], $value);
                        } else {
                            $translations[$key] = $value; // Sobrescrever com valor mais recente
                        }
                    }
                }
            }
            
            // 3. Carregar traduções específicas de seções
            $localeSectionsDir = "{$themePath}/locales/{$locale}/sections";
            if (Storage::disk('themes')->exists($localeSectionsDir)) {
                $sectionFiles = Storage::disk('themes')->files($localeSectionsDir);
                
                foreach ($sectionFiles as $file) {
                    if (pathinfo($file, PATHINFO_EXTENSION) === 'json') {
                        $content = Storage::disk('themes')->get($file);
                        $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
                        
                        if (is_array($data)) {
                            $sectionName = pathinfo($file, PATHINFO_FILENAME);
                            Log::debug("Carregado arquivo de seção", [
                                'file' => $file,
                                'section' => $sectionName,
                                'count' => count($data)
                            ]);
                            
                            // Estruturar corretamente sob a chave 'sections'
                            if (!isset($translations['sections'])) {
                                $translations['sections'] = [];
                            }
                            
                            $translations['sections'][$sectionName] = $data;
                        }
                    }
                }
            }
            
            // 4. Adicionar hardcoded translations para ajudar na depuração
            if (!isset($translations['sections'])) {
                $translations['sections'] = [];
            }

            // Carregar dados do arquivo fornecido
            $dataFile = public_path('translations-data.json');
            if (file_exists($dataFile)) {
                $translationsData = json_decode(file_get_contents($dataFile), true);
                if (json_last_error() === JSON_ERROR_NONE && isset($translationsData['sections'])) {
                    Log::debug("Usando traduções do arquivo de dados", [
                        'sections_count' => count($translationsData['sections'])
                    ]);
                    $translations = $this->mergeTranslationArrays($translations, $translationsData);
                }
            } else {
                // Adicionar traduções de exemplo para Slideshow
                if (!isset($translations['sections']['slideshow'])) {
                    $translations['sections']['slideshow'] = [
                        'name' => 'Apresentação de slides',
                        'settings' => [
                            'layout' => [
                                'label' => 'Layout',
                                'options__1' => [
                                    'label' => 'Largura total'
                                ],
                                'options__2' => [
                                    'label' => 'Grade'
                                ]
                            ],
                            'slide_height' => [
                                'label' => 'Altura do slide',
                                'options__1' => [
                                    'label' => 'Adaptar à primeira imagem'
                                ],
                                'options__2' => [
                                    'label' => 'Pequena'
                                ],
                                'options__3' => [
                                    'label' => 'Média'
                                ],
                                'options__4' => [
                                    'label' => 'Grande'
                                ]
                            ],
                            'slider_visual' => [
                                'label' => 'Estilo de paginação',
                                'options__1' => [
                                    'label' => 'Contador'
                                ],
                                'options__2' => [
                                    'label' => 'Pontos'
                                ],
                                'options__3' => [
                                    'label' => 'Números'
                                ]
                            ],
                            'auto_rotate' => [
                                'label' => 'Rodar automaticamente os slides'
                            ],
                            'change_slides_speed' => [
                                'label' => 'Mudar os slides a cada'
                            ],
                            'image_behavior' => [
                                'label' => 'Comportamento da imagem',
                                'options__1' => [
                                    'label' => 'Nenhuma'
                                ],
                                'options__2' => [
                                    'label' => 'Movimentação do ambiente'
                                ]
                            ],
                            'show_text_below' => [
                                'label' => 'Exibir conteúdo abaixo das imagens em dispositivos móveis'
                            ],
                            'accessibility' => [
                                'content' => 'Acessibilidade',
                                'label' => 'Descrição da apresentação de slides',
                                'info' => 'Descreva a apresentação de slides para clientes que usam leitores de tela.',
                                'default' => 'Apresentação de slides sobre nossa marca'
                            ]
                        ],
                        'blocks' => [
                            'slide' => [
                                'name' => 'Slide',
                                'settings' => [
                                    'image' => [
                                        'label' => 'Imagem'
                                    ],
                                    'heading' => [
                                        'label' => 'Título',
                                        'default' => 'Slide de imagem'
                                    ],
                                    'subheading' => [
                                        'label' => 'Subtítulo',
                                        'default' => 'Conte a história de sua marca com vídeos e imagens'
                                    ],
                                    'button_label' => [
                                        'label' => 'Etiqueta de botão',
                                        'info' => 'Deixe a etiqueta em branco para ocultar o botão.',
                                        'default' => 'Etiqueta de botão'
                                    ],
                                    'link' => [
                                        'label' => 'Link de botão'
                                    ],
                                    'secondary_style' => [
                                        'label' => 'Usar estilo de botão com contorno'
                                    ],
                                    'box_align' => [
                                        'label' => 'Posição do conteúdo',
                                        'options__1' => [
                                            'label' => 'Canto superior esquerdo'
                                        ],
                                        'options__2' => [
                                            'label' => 'Centralizado na parte superior'
                                        ],
                                        'options__3' => [
                                            'label' => 'Canto superior direito'
                                        ],
                                        'options__4' => [
                                            'label' => 'Centralizado à esquerda'
                                        ],
                                        'options__5' => [
                                            'label' => 'Centralizado'
                                        ],
                                        'options__6' => [
                                            'label' => 'Centralizado à direita'
                                        ],
                                        'options__7' => [
                                            'label' => 'Canto inferior esquerdo'
                                        ],
                                        'options__8' => [
                                            'label' => 'Centralizado na parte inferior'
                                        ],
                                        'options__9' => [
                                            'label' => 'Canto inferior direito'
                                        ],
                                        'info' => 'A posição é otimizada automaticamente para dispositivos móveis.'
                                    ],
                                    'show_text_box' => [
                                        'label' => 'Exibir contêiner no desktop'
                                    ],
                                    'text_alignment' => [
                                        'label' => 'Alinhamento de conteúdo',
                                        'option_1' => [
                                            'label' => 'Esquerda'
                                        ],
                                        'option_2' => [
                                            'label' => 'Centro'
                                        ],
                                        'option_3' => [
                                            'label' => 'Direita'
                                        ]
                                    ],
                                    'image_overlay_opacity' => [
                                        'label' => 'Opacidade de sobreposição de imagem'
                                    ],
                                    'text_alignment_mobile' => [
                                        'label' => 'Alinhamento de conteúdo em dispositivos móveis',
                                        'options__1' => [
                                            'label' => 'Esquerda'
                                        ],
                                        'options__2' => [
                                            'label' => 'Centro'
                                        ],
                                        'options__3' => [
                                            'label' => 'Direita'
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'presets' => [
                            'name' => 'Apresentação de slides'
                        ]
                    ];
                }
                
                // Adicionar traduções para outras seções conforme necessário
                if (!isset($translations['sections']['header'])) {
                    $translations['sections']['header'] = [
                        'name' => 'Cabeçalho',
                        'settings' => [
                            'logo_position' => [
                                'label' => 'Posição do logo no desktop',
                                'options__1' => [
                                    'label' => 'Centralizado à esquerda'
                                ],
                                'options__2' => [
                                    'label' => 'Canto superior esquerdo'
                                ],
                                'options__3' => [
                                    'label' => 'Centralizado na parte superior'
                                ],
                                'options__4' => [
                                    'label' => 'Centralizado'
                                ]
                            ]
                        ]
                    ];
                }
                
                if (!isset($translations['sections']['footer'])) {
                    $translations['sections']['footer'] = [
                        'name' => 'Rodapé',
                        'blocks' => [
                            'link_list' => [
                                'name' => 'Menu',
                                'settings' => [
                                    'heading' => [
                                        'label' => 'Título',
                                        'default' => 'Links rápidos'
                                    ],
                                    'menu' => [
                                        'label' => 'Menu',
                                        'info' => 'Mostra somente itens de menu de nível superior.'
                                    ]
                                ]
                            ]
                        ]
                    ];
                }
            }
                
            Log::debug("Traduções carregadas com sucesso", [
                'locale' => $locale,
                'total_keys' => count($translations),
                'has_sections' => isset($translations['sections']),
                'sections_count' => isset($translations['sections']) ? count($translations['sections']) : 0,
                'has_slideshow' => isset($translations['sections']['slideshow'])
            ]);
        } catch (\JsonException $e) {
            Log::error("Erro ao decodificar JSON de tradução", [
                'locale' => $locale,
                'error' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            Log::error("Erro ao carregar traduções", [
                'locale' => $locale,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
        
        // Armazenar em cache
        Cache::put($cacheKey, $translations, self::CACHE_TTL);
        
        // Armazenar também o nome da chave para limpeza posterior
        $cacheKeys = Cache::get('translation_cache_keys', []);
        if (!in_array($cacheKey, $cacheKeys)) {
            $cacheKeys[] = $cacheKey;
            Cache::put('translation_cache_keys', $cacheKeys, self::CACHE_TTL * 2);
        }
        
        return $translations;
    }
    
    /**
     * Mescla arrays de tradução preservando estrutura aninhada
     *
     * @param array $array1 Primeiro array
     * @param array $array2 Segundo array
     * @return array Array mesclado
     */
    private function mergeTranslationArrays(array $array1, array $array2): array
    {
        $merged = $array1;
        
        foreach ($array2 as $key => $value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                $merged[$key] = $this->mergeTranslationArrays($merged[$key], $value);
            } else {
                $merged[$key] = $value;
            }
        }
        
        return $merged;
    }
    
    /**
     * Inicializar traduções no contexto Liquid
     *
     * @param Context $context Contexto Liquid
     * @param string $themePath Caminho para o diretório do tema
     * @param string $locale Código do idioma
     * @return void
     */
    public function initializeTranslations(Context $context, string $themePath, string $locale = null): void
    {
        $locale = $locale ?? self::DEFAULT_LOCALE;
        
        // Carregar traduções
        $translations = $this->loadTranslations($themePath, $locale);
        
        // Registrar no contexto
        $context->registers['translations'] = $translations;
        $context->registers['current_locale'] = $locale;
        
        // Definir também como variável acessível
        $context->set('locale', $locale);
        
        // Para debug no log
        Log::debug("Contexto de tradução inicializado", [
            'locale' => $locale,
            'has_sections' => isset($translations['sections']),
            'sections_count' => isset($translations['sections']) ? count($translations['sections']) : 0
        ]);
    }
    
    /**
     * Traduzir uma string com o formato 't:namespace.key'
     *
     * @param string $key Chave de tradução
     * @param array $params Parâmetros de substituição
     * @param Context $context Contexto Liquid atual
     * @return string String traduzida ou a chave original
     */
    public function translate(string $key, array $params = [], Context $context = null): string
    {
        // Se é string vazia, retorna vazia
        if (empty($key)) {
            return '';
        }
        
        // Remove prefix 't:' if exists
        $translationKey = (strpos($key, 't:') === 0) ? substr($key, 2) : $key;
        
        // If no context or translations, return key
        if (!$context || !isset($context->registers['translations'])) {
            return $key;
        }
        
        $translations = $context->registers['translations'];
        $parts = explode('.', $translationKey);
        
        // Navigate through the nested structure for the translation
        $value = $translations;
        foreach ($parts as $part) {
            if (!isset($value[$part])) {
                
                return $key; // Key not found
            }
            $value = $value[$part];
        }
        
        // Check if result is a string
        if (is_string($value)) {
            return $this->applyParams($value, $params);
        }
        
        if (config('app.debug')) {
            Log::warning("Valor de tradução não é string", [
                'key' => $key,
                'value_type' => gettype($value),
                'locale' => $context->registers['current_locale'] ?? 'unknown'
            ]);
        }
        
        return $key;
    }
    
    /**
     * Processa recursivamente strings de tradução em um array
     *
     * @param mixed $data Dados a serem processados
     * @param Context $context Contexto Liquid
     * @return mixed Dados com traduções processadas
     */
    public function processTranslations($data, Context $context)
    {
        if (is_string($data)) {
            // Verificar se é uma string de tradução
            if (strpos($data, 't:') === 0) {
                return $this->translate($data, [], $context);
            }
            return $data;
        } elseif (is_array($data)) {
            // Processar recursivamente arrays
            $result = [];
            foreach ($data as $key => $value) {
                $result[$key] = $this->processTranslations($value, $context);
            }
            return $result;
        }
        
        // Retornar outros tipos de dados sem alteração
        return $data;
    }
    
    /**
     * Aplicar parâmetros de substituição a uma string traduzida
     *
     * @param string $string String traduzida
     * @param array $params Parâmetros de substituição
     * @return string String com parâmetros aplicados
     */
    private function applyParams(string $string, array $params): string
    {
        if (empty($params)) {
            return $string;
        }
        
        // Substituir parâmetros no formato {{ param }}
        foreach ($params as $key => $value) {
            $string = str_replace("{{ {$key} }}", $value, $string);
        }
        
        return $string;
    }
    
    /**
     * Verificar se uma tradução existe
     *
     * @param string $key Chave de tradução
     * @param Context $context Contexto Liquid
     * @return bool True se a tradução existir
     */
    public function hasTranslation(string $key, Context $context): bool
    {
        // Remover prefixo 't:' se existir
        if (strpos($key, 't:') === 0) {
            $key = substr($key, 2);
        }
        
        // Se não há contexto, não podemos verificar
        if (!$context || !isset($context->registers['translations'])) {
            return false;
        }
        
        $translations = $context->registers['translations'];
        $parts = explode('.', $key);
        
        // Navegar pela estrutura para verificar
        $value = $translations;
        foreach ($parts as $part) {
            if (!isset($value[$part])) {
                return false;
            }
            $value = $value[$part];
        }
        
        return is_string($value);
    }
    
    /**
     * Limpar o cache de traduções
     *
     * @param string $themePath Caminho do tema (opcional)
     * @param string $locale Código do idioma (opcional)
     * @return void
     */
    public function clearCache(string $themePath = null, string $locale = null): void
    {
        if ($themePath && $locale) {
            // Limpar cache específico
            $cacheKey = "theme_translations_{$themePath}_{$locale}";
            Cache::forget($cacheKey);
        } elseif ($themePath) {
            // Limpar todos os locales de um tema
            $keys = Cache::get('translation_cache_keys', []);
            foreach ($keys as $key) {
                if (strpos($key, "theme_translations_{$themePath}_") === 0) {
                    Cache::forget($key);
                }
            }
        } else {
            // Limpar todas as traduções
            $keys = Cache::get('translation_cache_keys', []);
            foreach ($keys as $key) {
                if (strpos($key, 'theme_translations_') === 0) {
                    Cache::forget($key);
                }
            }
        }
    }
    
    /**
     * Obtém o idioma mais adequado com base nas preferências do usuário
     *
     * @param array $availableLocales Locales disponíveis
     * @param string $defaultLocale Locale padrão
     * @return string Locale selecionado
     */
    public function getBestLocale(array $availableLocales, string $defaultLocale = self::DEFAULT_LOCALE): string
    {
        // Verificar query string
        $requestLocale = request()->query('locale');
        if ($requestLocale && in_array($requestLocale, $availableLocales)) {
            return $requestLocale;
        }
        
        // Lista de possíveis locales pelo cabeçalho HTTP Accept-Language
        $acceptLanguage = request()->header('Accept-Language');
        if (!$acceptLanguage) {
            return $defaultLocale;
        }
        
        // Extrair e normalizar locales aceitos
        $acceptedLocales = [];
        $parts = explode(',', $acceptLanguage);
        
        foreach ($parts as $part) {
            $subParts = explode(';', $part);
            $locale = trim($subParts[0]);
            $quality = isset($subParts[1]) ? (float) str_replace('q=', '', $subParts[1]) : 1.0;
            
            $acceptedLocales[$locale] = $quality;
        }
        
        // Ordenar por qualidade (prioridade)
        arsort($acceptedLocales);
        
        // Encontrar primeiro match
        foreach (array_keys($acceptedLocales) as $locale) {
            // Verificar correspondência exata
            if (in_array($locale, $availableLocales)) {
                return $locale;
            }
            
            // Verificar só o idioma principal (ex: 'pt' para 'pt-BR')
            $language = explode('-', $locale)[0];
            foreach ($availableLocales as $availableLocale) {
                if (strpos($availableLocale, $language) === 0) {
                    return $availableLocale;
                }
            }
        }
        
        return $defaultLocale;
    }
    
    /**
     * Lista todos os idiomas disponíveis para um tema
     *
     * @param string $themePath Caminho para o diretório do tema
     * @return array Lista de códigos de idioma disponíveis
     */
    public function getAvailableLocales(string $themePath): array
    {
        $locales = [];
        
        try {
            // Verificar arquivos .json e .schema.json
            $localesPath = "{$themePath}/locales";
            
            if (Storage::disk('themes')->exists($localesPath)) {
                $files = Storage::disk('themes')->files($localesPath);
                
                foreach ($files as $file) {
                    $filename = basename($file);
                    
                    // Ignorar arquivos que não são .json
                    if (!str_ends_with($filename, '.json')) {
                        continue;
                    }
                    
                    // Extrair o código do idioma removendo a extensão
                    $locale = pathinfo($filename, PATHINFO_FILENAME);
                    
                    // Remover .schema do final se existir
                    $locale = str_replace('.schema', '', $locale);
                    
                    // Adicionar à lista se ainda não estiver lá
                    if (!in_array($locale, $locales)) {
                        $locales[] = $locale;
                    }
                }
            } else {
                Log::warning("Diretório de locales não encontrado", [
                    'path' => $localesPath
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Erro ao listar locales disponíveis", [
                'error' => $e->getMessage()
            ]);
        }
        
        // Se não encontrou nenhum, usar o padrão
        if (empty($locales)) {
            $locales[] = self::DEFAULT_LOCALE;
        }
        
        return $locales;
    }
    
    /**
     * Cria um arquivo de tradução para uso na aplicação
     * 
     * @param array $translations Dados de tradução
     * @param string $filePath Caminho do arquivo (opcional)
     * @return bool Sucesso ou falha
     */
    public function createTranslationFile(array $translations, string $filePath = null): bool
    {
        try {
            $filePath = $filePath ?? public_path('translations-data.json');
            $result = file_put_contents($filePath, json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            
            if ($result !== false) {
                Log::info("Arquivo de traduções criado com sucesso", [
                    'path' => $filePath,
                    'size' => $result
                ]);
                return true;
            } else {
                Log::error("Falha ao escrever arquivo de traduções", [
                    'path' => $filePath
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error("Erro ao criar arquivo de traduções", [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Cria arquivo de tradução a partir do JSON de schema (arquivo-2.txt)
     * 
     * @param string $schemaJson JSON do schema
     * @param string $outputPath Caminho de saída
     * @return bool Sucesso ou falha
     */
    public function createTranslationFromSchema(string $schemaJson, string $outputPath = null): bool
    {
        try {
            $data = json_decode($schemaJson, true, 512, JSON_THROW_ON_ERROR);
            return $this->createTranslationFile($data, $outputPath);
        } catch (\JsonException $e) {
            Log::error("Erro ao decodificar schema JSON", [
                'error' => $e->getMessage()
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error("Erro ao processar schema", [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}