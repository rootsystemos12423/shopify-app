<?php

namespace App\Liquid\CustomTags;

use Liquid\AbstractTag;
use Liquid\Context;
use Liquid\Template;
use Liquid\FileSystem;
use Liquid\Exception\ParseException;
use Illuminate\Support\Facades\Log;

class LiquidTag extends AbstractTag
{
   public static function tagName(): string
    {
        return 'liquid';
    }

    /**
     * Arrays para armazenar subtemplates para processamento
     */
    private $ifBlocks = [];
    private $elseBlocks = [];
    private $elsifBlocks = [];
    private $currentBlock = null;
    private $subTemplate = null;
    protected $fileSystem = null;  // Sistema de arquivos para acessar snippets
    
    // Variáveis para rastrear blocos condicionais
    private $currentIfBlock = null;    // Rastreia o bloco if atual
    private $ifConditionStack = [];    // Pilha de condições if/elsif ativas
    private $ifConditionMet = false;   // Flag que indica se alguma condição já foi atendida

    /**
     * Constructor
     */
    public function __construct($markup, array &$tokens, FileSystem $fileSystem = null)
    {
        parent::__construct($markup, $tokens, $fileSystem);
        $this->fileSystem = $fileSystem;  // Salva o sistema de arquivos para uso posterior
        
        // Inicialização das variáveis de controle de fluxo
        $this->currentIfBlock = null;
        $this->ifConditionStack = [];
        $this->ifConditionMet = false;
        
        $this->parseBlocks($markup);
    }

    /**
     * Parse the markup into logical blocks
     */
    private function parseBlocks($markup)
    {
        // Dividir o markup em linhas
        $lines = preg_split('/\r\n|\n|\r/', $markup);
        $processedLines = [];
        $currentIndent = 0;
        $blockStack = [];

        $currentBlock = [
            'type' => 'main',
            'content' => '',
            'condition' => null,
            'indent' => 0,
            'parent' => null,
            'children' => []
        ];

        foreach ($lines as $line) {
            $line = rtrim($line);
            if (empty($line)) continue;

            // Processar linha por linha
            $trimmedLine = trim($line);

            // Verificar se é o início de um bloco if
            if (preg_match('/^if\s+(.+)$/', $trimmedLine, $matches)) {
                $newBlock = [
                    'type' => 'if',
                    'content' => $trimmedLine . "\n",
                    'condition' => $matches[1],
                    'indent' => $currentIndent,
                    'parent' => $currentBlock,
                    'children' => []
                ];
                
                $currentBlock['children'][] = $newBlock;
                $blockStack[] = $currentBlock;
                $currentBlock = $newBlock;
                $currentIndent++;
                continue;
            }

            // Verificar se é um elsif
            if (preg_match('/^elsif\s+(.+)$/', $trimmedLine, $matches)) {
                if ($currentBlock['type'] !== 'if' && $currentBlock['type'] !== 'elsif') {
                    throw new ParseException("'elsif' sem 'if' correspondente");
                }

                $newBlock = [
                    'type' => 'elsif',
                    'content' => $trimmedLine . "\n",
                    'condition' => $matches[1],
                    'indent' => $currentIndent - 1,
                    'parent' => $currentBlock['parent'],
                    'children' => []
                ];
                
                $currentBlock['parent']['children'][] = $newBlock;
                $currentBlock = $newBlock;
                continue;
            }

            // Verificar se é um else
            if ($trimmedLine === 'else') {
                if ($currentBlock['type'] !== 'if' && $currentBlock['type'] !== 'elsif') {
                    throw new ParseException("'else' sem 'if' correspondente");
                }

                $newBlock = [
                    'type' => 'else',
                    'content' => $trimmedLine . "\n",
                    'condition' => null,
                    'indent' => $currentIndent - 1,
                    'parent' => $currentBlock['parent'],
                    'children' => []
                ];
                
                $currentBlock['parent']['children'][] = $newBlock;
                $currentBlock = $newBlock;
                continue;
            }

            // Verificar se é o fim de um bloco if
            if ($trimmedLine === 'endif') {
                if ($currentBlock['type'] !== 'if' && $currentBlock['type'] !== 'elsif' && $currentBlock['type'] !== 'else') {
                    throw new ParseException("'endif' sem 'if' correspondente");
                }

                $currentBlock = array_pop($blockStack);
                $currentIndent--;
                continue;
            }

            // Adicionar linha ao bloco atual
            $currentBlock['content'] .= $trimmedLine . "\n";
        }

        // Armazenar o bloco principal
        $this->currentBlock = $currentBlock;
    }

    /**
     * Renderiza o conteúdo do liquid tag
     */
     public function render(Context $context): string
    {
        try {
            // Resetar o estado para cada nova renderização
            $this->currentIfBlock = null;
            $this->ifConditionStack = [];
            $this->ifConditionMet = false;
            
            \Log::debug('LiquidTag processing variables', [
                'context_keys' => array_keys($context->getAll()),
                'settings_keys' => $context->hasKey('settings') ? array_keys($context->get('settings')) : 'no settings'
            ]);

            // Processar o bloco principal - isso configura as variáveis no contexto
            // Primeiro, divide o conteúdo em instruções individuais
            $instructions = $this->splitIntoInstructions($this->markup);
            
            foreach ($instructions as $instruction) {
                try {
                    // Processa cada instrução usando métodos já existentes
                    $this->processInstruction($instruction, $context);
                } catch (\Exception $e) {
                    \Log::warning('Error processing liquid instruction: ' . $instruction, [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }
            
            // Liquid tag não produz saída direta
            return '';
            
        } catch (\Throwable $e) {
            \Log::error('Error in LiquidTag: ' . $e->getMessage(), [
                'markup' => $this->markup,
                'trace' => $e->getTraceAsString()
            ]);
            
            return "<!-- Error processing Liquid tag: " . htmlspecialchars($e->getMessage()) . " -->";
        }
    }

    /**
     * Divide o conteúdo em instruções individuais mesmo quando não estão em linhas separadas
     */
    private function splitIntoInstructions(string $markup): array
    {
        // Primeiro dividimos por linhas normalmente
        $lines = preg_split('/\r\n|\n|\r/', $markup);
        $instructions = [];
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            // Agora precisamos verificar se há múltiplas instruções na mesma linha
            $currentInstructions = $this->splitLineIntoInstructions($line);
            foreach ($currentInstructions as $instruction) {
                $instructions[] = trim($instruction);
            }
        }
        
        return $instructions;
    }

    /**
     * Divide uma linha em instruções individuais baseadas em palavras-chave do Liquid
     */
    private function splitLineIntoInstructions(string $line): array
    {
        // Palavras-chave que iniciam novas instruções
        $keywords = ['assign', 'capture', 'if', 'unless', 'else', 'elsif', 'endif', 'endunless', 'endcapture', 'for', 'endfor', 'case', 'endcase', 'when', 'cycle', 'increment', 'decrement', 'render'];
        
        $instructions = [];
        $buffer = '';
        $tokens = preg_split('/\s+/', $line);
        
        foreach ($tokens as $i => $token) {
            // Se for a primeira palavra, sempre começa uma instrução
            if ($i === 0) {
                $buffer = $token;
                continue;
            }
            
            // Se for uma palavra-chave, finaliza a instrução anterior e começa uma nova
            if (in_array($token, $keywords)) {
                if (!empty($buffer)) {
                    $instructions[] = $buffer;
                }
                $buffer = $token;
            } else {
                $buffer .= ' ' . $token;
            }
        }
        
        // Adiciona a última instrução no buffer
        if (!empty($buffer)) {
            $instructions[] = $buffer;
        }
        
        return $instructions;
    }

    /**
     * Processa uma instrução individual
     */
       private function processInstruction(string $instruction, Context $context): void
    {
        // Processar atribuições
        if (preg_match('/^assign\s+(\w+)\s*=\s*(.+)$/', $instruction, $matches)) {
            // Se estamos em um bloco condicional e nenhuma condição foi atendida, pular
            if ($this->currentIfBlock !== null && !$this->ifConditionMet) {
                return;
            }
            
            $varName = $matches[1];
            $expression = trim($matches[2]);
            
            // Processar filtros na expressão
            if (strpos($expression, '|') !== false) {
                $parts = explode('|', $expression, 2);
                $value = $this->evaluateExpression(trim($parts[0]), $context);
                $value = $this->applyFilters($value, trim($parts[1]), $context);
            } else {
                $value = $this->evaluateExpression($expression, $context);
            }
            
            $context->set($varName, $value);
            return;
        }
        
        // Processar blocos if/elsif/else/endif
        if (preg_match('/^if\s+(.+)$/', $instruction, $matches)) {
            // Iniciar um novo bloco if
            $condition = $matches[1];
            $result = $this->evaluateCondition($condition, $context);
            
            // Salvar o estado do bloco if atual
            $this->ifConditionStack[] = $this->currentIfBlock;
            $this->currentIfBlock = [
                'condition_met' => $result,
                'in_else' => false
            ];
            
            // Definir se uma condição já foi atendida
            $this->ifConditionMet = $result;
            
            return;
        }
        
        // Processar elsif
        if (preg_match('/^elsif\s+(.+)$/', $instruction, $matches)) {
            // Verificar se estamos dentro de um bloco if
            if ($this->currentIfBlock === null) {
                \Log::warning("'elsif' sem 'if' correspondente");
                return;
            }
            
            // Se outra condição já foi atendida, ignore este elsif
            if ($this->ifConditionMet) {
                return;
            }
            
            // Avaliar a condição do elsif
            $condition = $matches[1];
            $result = $this->evaluateCondition($condition, $context);
            
            // Se esta condição for atendida, definir a flag
            if ($result) {
                $this->ifConditionMet = true;
                $this->currentIfBlock['condition_met'] = true;
            }
            
            return;
        }
        
        // Processar else
        if ($instruction === 'else') {
            // Verificar se estamos dentro de um bloco if
            if ($this->currentIfBlock === null) {
                \Log::warning("'else' sem 'if' correspondente");
                return;
            }
            
            // Se nenhuma condição anterior foi atendida, executar o bloco else
            if (!$this->ifConditionMet) {
                $this->ifConditionMet = true;
                $this->currentIfBlock['condition_met'] = true;
            } else {
                // Caso contrário, pular o bloco else
                $this->currentIfBlock['in_else'] = true;
            }
            
            return;
        }
        
        // Processar endif
        if ($instruction === 'endif') {
            // Verificar se estamos dentro de um bloco if
            if ($this->currentIfBlock === null) {
                \Log::warning("'endif' sem 'if' correspondente");
                return;
            }
            
            // Restaurar o bloco if anterior
            $this->currentIfBlock = array_pop($this->ifConditionStack);
            // Restaurar a flag da condição
            $this->ifConditionMet = $this->currentIfBlock !== null ? $this->currentIfBlock['condition_met'] : false;
            
            return;
        }
        
        // Se estamos em um bloco condicional e nenhuma condição foi atendida, pular a instrução
        if ($this->currentIfBlock !== null && !$this->ifConditionMet) {
            return;
        }
        
        // Se estamos em um bloco else que não deve ser executado, pular a instrução
        if ($this->currentIfBlock !== null && $this->currentIfBlock['in_else'] && !$this->ifConditionMet) {
            return;
        }
        
        // Processar unless/endunless
        if (preg_match('/^unless\s+(.+)$/', $instruction, $matches)) {
            $condition = $matches[1];
            $result = !$this->evaluateCondition($condition, $context);
            $context->push(['unless_condition' => $result]);
            return;
        }
        
        if ($instruction === 'endunless') {
            $context->pop();
            return;
        }
        
        // Processar case/when/endcase
        if (preg_match('/^case\s+(.+)$/', $instruction, $matches)) {
            $caseValue = $this->evaluateExpression($matches[1], $context);
            $context->push([
                'case_value' => $caseValue,
                'case_satisfied' => false
            ]);
            return;
        }
        
        if (preg_match('/^when\s+(.+)$/', $instruction, $matches)) {
            // Verifica se estamos dentro de um bloco case
            if (!isset($context->environments[0]['case_value'])) {
                \Log::warning("'when' tag encontrado fora de um bloco 'case'");
                return;
            }
            
            // Se o case já foi satisfeito, pular este when
            if ($context->environments[0]['case_satisfied']) {
                return;
            }
            
            // Avaliar o valor do when
            $whenValue = $this->evaluateExpression($matches[1], $context);
            $caseValue = $context->environments[0]['case_value'];
            
            // Comparar valores
            if ($whenValue == $caseValue) {
                $context->environments[0]['case_satisfied'] = true;
            }
            
            return;
        }
        
        if ($instruction === 'endcase') {
            $context->pop();
            return;
        }
        
        // Processar for/endfor loops
        if (preg_match('/^for\s+(\w+)\s+in\s+(.+)$/', $instruction, $matches)) {
            $loopVar = $matches[1];
            $collectionExpr = $matches[2];
            
            // Avaliar a coleção
            $collection = $this->evaluateExpression($collectionExpr, $context);
            
            // Se não for uma coleção válida, criar uma vazia
            if (!is_array($collection)) {
                $collection = [];
            }
            
            // Adicionar ao contexto
            $context->push([
                'for_loop' => [
                    'variable' => $loopVar,
                    'collection' => $collection,
                    'index' => 0,
                    'length' => count($collection)
                ]
            ]);
            
            // Processar o primeiro item se existir
            if (!empty($collection)) {
                $context->set($loopVar, reset($collection));
                $context->set('forloop', [
                    'first' => true,
                    'last' => count($collection) === 1,
                    'index' => 1,
                    'index0' => 0,
                    'rindex' => count($collection),
                    'rindex0' => count($collection) - 1,
                    'length' => count($collection)
                ]);
            }
            
            return;
        }
        
        if ($instruction === 'endfor') {
            // Verifica se estamos dentro de um loop for
            if (!isset($context->environments[0]['for_loop'])) {
                \Log::warning("'endfor' tag encontrado fora de um bloco 'for'");
                return;
            }
            
            $context->pop();
            return;
        }
        
        // Processar capture/endcapture blocos
        if (preg_match('/^capture\s+(\w+)$/', $instruction, $matches)) {
            $captureVar = $matches[1];
            $context->push([
                'capture' => [
                    'variable' => $captureVar,
                    'content' => ''
                ]
            ]);
            return;
        }
        
        if ($instruction === 'endcapture') {
            // Verifica se estamos dentro de um bloco capture
            if (!isset($context->environments[0]['capture'])) {
                \Log::warning("'endcapture' tag encontrado fora de um bloco 'capture'");
                return;
            }
            
            $captureVar = $context->environments[0]['capture']['variable'];
            $captureContent = $context->environments[0]['capture']['content'];
            $context->pop();
            
            // Define a variável com o conteúdo capturado
            $context->set($captureVar, $captureContent);
            return;
        }
        
        // NOVA FUNCIONALIDADE: Processar a tag render
        if (preg_match('/^render\s+[\'"]([^\'"]*)[\'"](.*)$/', $instruction, $matches)) {
            $snippetName = $matches[1];
            $paramsString = isset($matches[2]) ? trim($matches[2]) : '';
            
            \Log::debug("Processando render do snippet '{$snippetName}' com parâmetros: {$paramsString}");
            
            try {
                // Vamos tentar encontrar o sistema de arquivos para acessar o snippet
                $fileSystem = null;
                
                // Tentar obter do contexto, da instância atual, ou do serviço
                if (isset($context->registers['file_system'])) {
                    $fileSystem = $context->registers['file_system'];
                } elseif ($this->fileSystem) {
                    $fileSystem = $this->fileSystem;
                } elseif (isset($context->registers['theme_content_service'])) {
                    // Se tivermos um serviço de conteúdo de tema, usamos ele
                    $themeContentService = $context->registers['theme_content_service'];
                    $theme = $context->registers['theme'] ?? null;
                    
                    if ($theme && method_exists($themeContentService, 'getThemeFile')) {
                        // Extrair parâmetros da string de parâmetros
                        $params = $this->extractRenderParams($paramsString, $context);
                        
                        // Carregar o conteúdo do snippet
                        $snippetPath = "snippets/{$snippetName}.liquid";
                        $snippetContent = $themeContentService->getThemeFile($theme, $snippetPath);
                        
                        if ($snippetContent) {
                            // Criar um novo contexto com os parâmetros para o snippet
                            $renderContext = clone $context;
                            foreach ($params as $key => $value) {
                                $renderContext->set($key, $value);
                            }
                            
                            // Renderizar o snippet usando um novo template
                            $template = new \Liquid\Template();
                            
                            // Registrar os mesmos filtros do contexto original
                            if (isset($context->registers['filters'])) {
                                foreach ($context->registers['filters'] as $name => $filter) {
                                    $template->registerFilter($filter);
                                }
                            }
                            
                            // Analisar e renderizar o snippet
                            $template->parse($snippetContent);
                            $template->render($renderContext->getAll());
                            return;
                        }
                    }
                }
                
                // Se não conseguiu acesso por serviço, tentar pelo sistema de arquivos padrão
                if (!$fileSystem) {
                    \Log::warning("Falha ao processar render '{$snippetName}': Sistema de arquivos não disponível", [
                        'snippet' => $snippetName,
                        'params' => $paramsString
                    ]);
                    return;
                }
                
                // Extrair parâmetros da string de parâmetros
                $params = $this->extractRenderParams($paramsString, $context);
                
                // Tentar carregar o snippet usando o sistema de arquivos
                try {
                    $snippetPath = "snippets/{$snippetName}.liquid";
                    $snippetContent = $fileSystem->readTemplateFile($snippetPath);
                    
                    // Criar um novo contexto com os parâmetros para o snippet
                    $renderContext = clone $context;
                    foreach ($params as $key => $value) {
                        $renderContext->set($key, $value);
                    }
                    
                    // Renderizar o snippet usando um novo template
                    $template = new \Liquid\Template();
                    $template->setFileSystem($fileSystem);
                    
                    // Registrar os mesmos filtros do contexto original
                    if (isset($context->registers['filters'])) {
                        foreach ($context->registers['filters'] as $name => $filter) {
                            $template->registerFilter($filter);
                        }
                    }
                    
                    // Analisar e renderizar o snippet
                    $template->parse($snippetContent);
                    $template->render($renderContext->getAll());
                    
                } catch (\Exception $e) {
                    \Log::warning("Erro ao renderizar snippet '{$snippetName}': " . $e->getMessage(), [
                        'path' => $snippetPath ?? 'unknown',
                        'error' => $e->getMessage()
                    ]);
                }
            } catch (\Exception $e) {
                \Log::warning("Erro ao processar tag render '{$snippetName}': " . $e->getMessage(), [
                    'instruction' => $instruction,
                    'error' => $e->getMessage()
                ]);
            }
            
            return;
        }
        
        // Caso não seja reconhecido, tente processar como uma instrução genérica
        try {
            $wrappedInstruction = '{% ' . $instruction . ' %}';
            $tempTemplate = new \Liquid\Template();
            $tempTemplate->parse($wrappedInstruction)->render($context->getAll());
        } catch (\Exception $e) {
            \Log::warning('Could not process unknown instruction: ' . $instruction, [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Extrai os parâmetros de uma tag render
     * 
     * A tag render pode ter parâmetros como:
     * render 'snippet-name', param1: value1, param2: 'string value', param3: variable
     */
    private function extractRenderParams(string $paramsString, Context $context): array
    {
        $params = [];
        
        if (empty($paramsString)) {
            return $params;
        }
        
        // Primeiro verifica se há uma vírgula após o nome do snippet
        if (substr($paramsString, 0, 1) === ',') {
            $paramsString = substr($paramsString, 1);
        }
        
        // Divide parâmetros por vírgula, mas preserva vírgulas dentro de strings com aspas
        $paramPairs = preg_split('/,(?=(?:[^"\']*["|\'][^"\']*["|\'])*[^"\']*$)/', $paramsString);
        
        foreach ($paramPairs as $pair) {
            $pair = trim($pair);
            
            // Se o par é vazio, pular
            if (empty($pair)) {
                continue;
            }
            
            // Encontra nome e valor do parâmetro (formato: nome: valor)
            if (preg_match('/(\w+):\s*(.+)$/', $pair, $paramMatch)) {
                $paramName = trim($paramMatch[1]);
                $paramValue = trim($paramMatch[2]);
                
                // Se o valor tem aspas, remova-as
                if (preg_match('/^[\'"](.*)[\'"]\s*$/', $paramValue, $matches)) {
                    $params[$paramName] = $matches[1];
                } else {
                    // Senão, tenta avaliar como expressão no contexto atual
                    $params[$paramName] = $this->evaluateExpression($paramValue, $context);
                }
            }
        }
        
        return $params;
    }

    /**
     * Processa manualmente as tags de output {{ variável }}
     */
    private function processOutputTags(string $content, Context $context): string
    {
        // Processar tags de output {{ variable }}
        preg_match_all('/\{\{\s*([^}]+)\s*\}\}/', $content, $matches, PREG_SET_ORDER);
        
        $processedContent = $content;
        foreach ($matches as $match) {
            $expression = trim($match[1]);
            
            // Processar filtros na expressão de output
            if (strpos($expression, '|') !== false) {
                $parts = explode('|', $expression, 2);
                $value = $this->evaluateExpression(trim($parts[0]), $context);
                $value = $this->applyFilters($value, trim($parts[1]), $context);
            } else {
                $value = $this->evaluateExpression($expression, $context);
            }
            
            // Substituir a tag pelo valor
            $processedContent = str_replace($match[0], (string)$value, $processedContent);
        }
        
        return $processedContent;
    }

    /**
     * Processa um bloco lógico e seus filhos
     */
    private function processBlock($block, Context $context)
    {
        // Se for um bloco condicional, avaliar a condição
        if ($block['type'] === 'if' || $block['type'] === 'elsif') {
            $conditionResult = $this->evaluateCondition($block['condition'], $context);
            
            if ($conditionResult) {
                // Se a condição for verdadeira, processar este bloco
                $this->processBlockContent($block['content'], $context);
            } else {
                // Se a condição for falsa, tentar processar filhos (elsif/else)
                foreach ($block['children'] as $childBlock) {
                    if ($childBlock['type'] === 'elsif') {
                        $childResult = $this->evaluateCondition($childBlock['condition'], $context);
                        if ($childResult) {
                            $this->processBlockContent($childBlock['content'], $context);
                            break;
                        }
                    } elseif ($childBlock['type'] === 'else') {
                        $this->processBlockContent($childBlock['content'], $context);
                        break;
                    }
                }
            }
        } 
        elseif ($block['type'] === 'else') {
            // Blocos else são processados diretamente
            $this->processBlockContent($block['content'], $context);
        }
        else {
            // Blocos principais são processados diretamente
            $this->processBlockContent($block['content'], $context);
        }
    }

    /**
     * Processa o conteúdo de um bloco, analisando atribuições e operações
     */
    private function processBlockContent($content, Context $context)
    {
        // Dividir o conteúdo em linhas
        $lines = preg_split('/\r\n|\n|\r/', $content);
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            try {
                // Processar atribuições
                if (preg_match('/^assign\s+(\w+)\s*=\s*(.+)$/', $line, $matches)) {
                    $varName = $matches[1];
                    $expression = trim($matches[2]);
                    
                    // Processar filtros na expressão
                    if (strpos($expression, '|') !== false) {
                        $parts = explode('|', $expression, 2);
                        $value = $this->evaluateExpression(trim($parts[0]), $context);
                        $value = $this->applyFilters($value, trim($parts[1]), $context);
                    } else {
                        $value = $this->evaluateExpression($expression, $context);
                    }
                    
                    $context->set($varName, $value);
                    continue;
                }
                
                // Adicione outros tipos de instruções aqui conforme necessário
                // Por exemplo, capture, increment, decrement, etc.
                
            } catch (\Exception $e) {
                // Log de erro para esta linha específica
                Log::warning('Error processing line in liquid tag: ' . $e->getMessage(), [
                    'line' => $line,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Aplica filtros a um valor
     */
    private function applyFilters($value, $filterString, Context $context)
    {
        // Dividir a string de filtros em filtros individuais
        $filters = preg_split('/\s*\|\s*(?=[\w]+:?|t:?)/', $filterString);
        
        foreach ($filters as $filter) {
            // Se o filtro estiver vazio, pular
            if (empty(trim($filter))) continue;
            
            // Verificar se o filtro tem argumentos
            if (preg_match('/^(\w+)(?:\s*:\s*(.+))?$/', trim($filter), $matches)) {
                $filterName = $matches[1];
                $filterArgs = isset($matches[2]) ? $matches[2] : null;
                
                // Buscar e executar o filtro registrado
                $filterMethod = $this->getFilterMethod($filterName, $context);
                if ($filterMethod) {
                    if ($filterArgs !== null) {
                        // Processar os argumentos e converter para o formato adequado
                        $args = $this->parseFilterArgs($filterArgs, $context);
                        $value = call_user_func($filterMethod, $value, $args);
                    } else {
                        $value = call_user_func($filterMethod, $value);
                    }
                }
            }
        }
        
        return $value;
    }

    /**
     * Processa argumentos de filtro
     */
    private function parseFilterArgs($args, Context $context)
    {
        // Se o argumento parece ser uma variável
        if (preg_match('/^\w+$/', $args)) {
            return $context->get($args);
        }
        
        // Se for string com aspas
        if (preg_match('/^[\'"](.*)[\'"]\s*$/', $args, $matches)) {
            return $matches[1];
        }
        
        // Se for numérico
        if (is_numeric($args)) {
            return floatval($args);
        }
        
        // Outros casos, retornar como está
        return $args;
    }

    /**
     * Obtém o método de filtro registrado
     */
    private function getFilterMethod($filterName, Context $context)
    {
        // Verificar nos registros do contexto
        if (isset($context->registers['filters'][$filterName])) {
            return $context->registers['filters'][$filterName];
        }
        
        // Verificar em CustomFilters
        $customFilters = new \App\Liquid\CustomFilters($context);
        if (method_exists($customFilters, $filterName)) {
            return [$customFilters, $filterName];
        }
        
        return null;
    }

    /**
     * Encontra uma tradução nas traduções disponíveis
     */
    private function findTranslation($translations, $key)
    {
        $parts = explode('.', $key);
        $current = $translations;
        
        foreach ($parts as $part) {
            if (!isset($current[$part])) {
                return null;
            }
            $current = $current[$part];
        }
        
        return $current;
    }

    /**
     * Avalia uma expressão simples no contexto atual
     */
    private function evaluateExpression(string $expression, Context $context): mixed
    {
        // Valores literais
        if ($expression === 'true') return true;
        if ($expression === 'false') return false;
        if ($expression === 'nil' || $expression === 'null') return null;
        
        // Números
        if (is_numeric($expression)) {
            return strpos($expression, '.') !== false ? 
                (float)$expression : (int)$expression;
        }
        
        // Strings literais
        if (preg_match('/^[\'"](.*)[\'"]\s*$/', $expression, $matches)) {
            return $matches[1];
        }
        
        // Verificar filtros
        if (strpos($expression, '|') !== false) {
            $parts = explode('|', $expression, 2);
            $value = $this->evaluateExpression(trim($parts[0]), $context);
            return $this->applyFilters($value, trim($parts[1]), $context);
        }
        
        // Variáveis com acesso a propriedades (obj.prop)
        if (preg_match('/^([\w]+)(\.[\w\.]+)?$/', $expression, $matches)) {
            $varName = $matches[1];
            $value = $context->get($varName);
            
            // Se houver acesso a propriedades
            if (isset($matches[2]) && $matches[2]) {
                $props = explode('.', substr($matches[2], 1));
                foreach ($props as $prop) {
                    if (is_array($value) && isset($value[$prop])) {
                        $value = $value[$prop];
                    } elseif (is_object($value) && isset($value->{$prop})) {
                        $value = $value->{$prop};
                    } else {
                        return null;
                    }
                }
            }
            
            return $value;
        }
        
        // Para expressões mais complexas, fazer uma "miniavaliação"
        return $this->evaluateComplexExpression($expression, $context);
    }
    
    /**
     * Avalia expressões complexas de forma simplificada
     */
    private function evaluateComplexExpression(string $expression, Context $context): mixed
    {
        // Expressões que usam operadores de comparação
        if (strpos($expression, ' > ') !== false) {
            list($left, $right) = explode(' > ', $expression, 2);
            $leftValue = $this->evaluateExpression(trim($left), $context);
            $rightValue = $this->evaluateExpression(trim($right), $context);
            return $leftValue > $rightValue;
        }
        
        if (strpos($expression, ' < ') !== false) {
            list($left, $right) = explode(' < ', $expression, 2);
            $leftValue = $this->evaluateExpression(trim($left), $context);
            $rightValue = $this->evaluateExpression(trim($right), $context);
            return $leftValue < $rightValue;
        }
        
        if (strpos($expression, ' == ') !== false) {
            list($left, $right) = explode(' == ', $expression, 2);
            $leftValue = $this->evaluateExpression(trim($left), $context);
            $rightValue = $this->evaluateExpression(trim($right), $context);
            return $leftValue == $rightValue;
        }
        
        if (strpos($expression, ' != ') !== false) {
            list($left, $right) = explode(' != ', $expression, 2);
            $leftValue = $this->evaluateExpression(trim($left), $context);
            $rightValue = $this->evaluateExpression(trim($right), $context);
            return $leftValue != $rightValue;
        }
        
        // Tentar resolver chamadas de funções/filtros simples como size
        if (preg_match('/^(.+)\.size\s*$/', $expression, $matches)) {
            $value = $this->evaluateExpression(trim($matches[1]), $context);
            if (is_array($value)) {
                return count($value);
            }
            if (is_string($value)) {
                return strlen($value);
            }
            return 0;
        }
        
        // Verificar property existe
        if (preg_match('/^(.+)\.(\w+)\?$/', $expression, $matches)) {
            $obj = $this->evaluateExpression(trim($matches[1]), $context);
            $prop = $matches[2];
            if (is_array($obj)) {
                return isset($obj[$prop]);
            }
            if (is_object($obj)) {
                return isset($obj->{$prop});
            }
            return false;
        }
        
        // Para outros casos, retornar o próprio contexto
        return $context->get($expression);
    }
    
    /**
     * Avalia uma condição composta
     */
    private function evaluateCondition(string $condition, Context $context): bool
    {
        // Operador OR
        if (strpos($condition, ' or ') !== false) {
            $parts = explode(' or ', $condition);
            foreach ($parts as $part) {
                if ($this->evaluateCondition(trim($part), $context)) {
                    return true;
                }
            }
            return false;
        }
        
        // Operador AND
        if (strpos($condition, ' and ') !== false) {
            $parts = explode(' and ', $condition);
            foreach ($parts as $part) {
                if (!$this->evaluateCondition(trim($part), $context)) {
                    return false;
                }
            }
            return true;
        }
        
        // Condição simples
        return (bool)$this->evaluateExpression($condition, $context);
    }
}