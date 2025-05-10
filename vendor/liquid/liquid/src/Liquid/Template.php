<?php

namespace Liquid;

use Liquid\Exception\CacheException;
use Liquid\Exception\MissingFilesystemException;
use Illuminate\Support\Facades\Log;

/**
 * The Template class with memory debugging.
 */
class Template
{
    const CLASS_PREFIX = '\Liquid\Cache\\';
    
    // Memory thresholds for debugging
    const MEMORY_WARNING_THRESHOLD = 0.7; // 70% of available memory
    const MEMORY_CRITICAL_THRESHOLD = 0.9; // 90% of available memory

    /**
     * @var Document The root of the node tree
     */
    private $root;

    /**
     * @var FileSystem The file system to use for includes
     */
    private $fileSystem;

    /**
     * @var array Globally included filters
     */
    private $filters = array();

    /**
     * @var callable|null Called "sometimes" while rendering
     */
    private $tickFunction = null;

    /**
     * @var array Custom tags
     */
    private static $tags = array();

    /**
     * @var Cache
     */
    private static $cache;
    
    /**
     * @var array Keep track of template paths being processed to detect circular includes
     */
    private static $processingTemplates = [];
    
    /**
     * @var int Memory usage at initialization time
     */
    private $initialMemory;
    
    /**
     * @var array Memory debug info
     */
    private $memoryInfo = [];

    /**
     * Constructor.
     *
     * @param string $path
     * @param array|Cache $cache
     *
     * @return Template
     */
    public function __construct($path = null, $cache = null)
    {
        // Record initial memory usage
        $this->initialMemory = memory_get_usage(true);
        $this->memoryDebug('Constructor initialized');
        
        $this->fileSystem = $path !== null
            ? new LocalFileSystem($path)
            : null;

        $this->setCache($cache);
    }
    
    /**
     * Log memory usage debugging info
     */
    private function memoryDebug($action, $extraInfo = [])
    {
        if (!class_exists('Illuminate\Support\Facades\Log')) {
            return;
        }
        
        $currentMemory = memory_get_usage(true);
        $memoryLimit = $this->getMemoryLimitBytes();
        $percentage = $memoryLimit > 0 ? ($currentMemory / $memoryLimit) * 100 : 0;
        
        $info = [
            'action' => $action,
            'memory_used' => $this->formatBytes($currentMemory),
            'memory_limit' => $this->formatBytes($memoryLimit),
            'percentage' => number_format($percentage, 2) . '%',
            'peak_memory' => $this->formatBytes(memory_get_peak_usage(true)),
        ];
        
        // Add any extra info
        $info = array_merge($info, $extraInfo);
        
        // Log at appropriate level based on memory usage
        if ($percentage > self::MEMORY_CRITICAL_THRESHOLD * 100) {
            Log::error('CRITICAL MEMORY USAGE', $info);
        } elseif ($percentage > self::MEMORY_WARNING_THRESHOLD * 100) {
            Log::warning('HIGH MEMORY USAGE', $info);
        }
        
        // Store in memory info array
        $this->memoryInfo[] = $info;
        
        // Force garbage collection at high memory usage
        if ($percentage > self::MEMORY_WARNING_THRESHOLD * 100) {
            gc_collect_cycles();
        }
    }
    
    /**
     * Format bytes to human-readable format
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
    
    /**
     * Get PHP memory limit in bytes
     */
    private function getMemoryLimitBytes()
    {
        $memoryLimit = ini_get('memory_limit');
        
        // If no limit is set, return -1
        if ($memoryLimit === '-1') {
            return -1;
        }
        
        // Convert memory limit to bytes
        $unit = strtoupper(substr($memoryLimit, -1));
        $value = (int) substr($memoryLimit, 0, -1);
        
        switch ($unit) {
            case 'G':
                return $value * 1024 * 1024 * 1024;
            case 'M':
                return $value * 1024 * 1024;
            case 'K':
                return $value * 1024;
            default:
                return (int) $memoryLimit;
        }
    }

    /**
     * @param FileSystem $fileSystem
     */
    public function setFileSystem(FileSystem $fileSystem)
    {
        $this->fileSystem = $fileSystem;
    }

    /**
     * @param array|Cache $cache
     *
     * @throws \Liquid\Exception\CacheException
     */
    public static function setCache($cache)
    {
        if (is_array($cache)) {
            if (isset($cache['cache']) && class_exists($classname = self::CLASS_PREFIX . ucwords($cache['cache']))) {
                self::$cache = new $classname($cache);
            } else {
                throw new CacheException('Invalid cache options!');
            }
        }

        if ($cache instanceof Cache) {
            self::$cache = $cache;
        }

        if (is_null($cache)) {
            self::$cache = null;
        }
    }

    /**
     * @return Cache
     */
    public static function getCache()
    {
        return self::$cache;
    }

    /**
     * @return Document
     */
    public function getRoot()
    {
        return $this->root;
    }

    /**
     * Register custom Tags
     *
     * @param string $name
     * @param string $class
     */
    public static function registerTag($name, $class)
    {
        self::$tags[$name] = $class;
    }

    /**
     * @return array
     */
    public static function getTags()
    {
        return self::$tags;
    }

    /**
     * Register the filter
     *
     * @param string $filter
     */
    public function registerFilter($filter, callable $callback = null)
    {
        // Store callback for later use
        if ($callback) {
            $this->filters[] = [$filter, $callback];
        } else {
            $this->filters[] = $filter;
        }
    }

    public function setTickFunction(callable $tickFunction)
    {
        $this->tickFunction = $tickFunction;
    }

    /**
     * Tokenizes the given source string
     *
     * @param string $source
     *
     * @return array
     */
    public static function tokenize($source)
    {
        if (empty($source)) {
            return array();
        }
        
        return preg_split(Liquid::get('TOKENIZATION_REGEXP'), $source, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
    }

    /**
     * Parses the given source string with memory debugging
     *
     * @param string $source
     *
     * @return Template
     */
    public function parse($source)
    {
        $this->memoryDebug('Starting template parsing', [
            'source_length' => strlen($source),
            'source_preview' => substr($source, 0, 100) . '...'
        ]);
        
        // Check if the template is too large
        if (strlen($source) > 1024 * 1024) { // 1MB
            $this->memoryDebug('WARNING: Large template', [
                'size' => $this->formatBytes(strlen($source))
            ]);
        }
        
        if (!self::$cache) {
            // Proceed with normal parsing
            try {
                $result = $this->parseAlways($source);
                $this->memoryDebug('Parsing completed without cache');
                return $result;
            } catch (\Exception $e) {
                $this->memoryDebug('ERROR during parsing', [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);
                throw $e;
            }
        }

        $hash = md5($source);
        $this->memoryDebug('Looking for cached template', [
            'hash' => $hash
        ]);
        
        $this->root = self::$cache->read($hash);

        // if no cached version exists, or if it checks for includes
        if ($this->root == false || $this->root->hasIncludes() == true) {
            $this->memoryDebug('Cache miss or template has includes');
            $this->parseAlways($source);
            self::$cache->write($hash, $this->root);
        } else {
            $this->memoryDebug('Using cached template');
        }

        return $this;
    }

    /**
     * Parses the given source string regardless of caching
     * This is where the critical operations happen
     *
     * @param string $source
     *
     * @return Template
     */
    private function parseAlways($source)
    {
        // Start memory tracking for this critical section
        $startMemory = memory_get_usage(true);
        $this->memoryDebug('Starting tokenization');
        
        // DEBUG: Line 175 is likely here
        $tokens = Template::tokenize($source);
        
        $this->memoryDebug('Tokenization completed', [
            'token_count' => count($tokens)
        ]);
        
        // Check for enormous token arrays
        if (count($tokens) > 10000) {
            $this->memoryDebug('WARNING: Extremely large token array', [
                'token_count' => count($tokens),
                'first_tokens' => array_slice($tokens, 0, 5)
            ]);
        }
        
        // Memory-efficient Document creation
        $this->memoryDebug('Creating Document object');
        
        // This is another critical point
        try {
            $this->root = new Document($tokens, $this->fileSystem);
            $this->memoryDebug('Document creation completed');
        } catch (\Exception $e) {
            $this->memoryDebug('ERROR in Document creation', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            throw $e;
        }
        
        // Check memory usage after parsing
        $endMemory = memory_get_usage(true);
        $memoryUsed = $endMemory - $startMemory;
        
        $this->memoryDebug('Parse operation completed', [
            'memory_used_for_parse' => $this->formatBytes($memoryUsed)
        ]);

        return $this;
    }

    /**
     * Parses the given template file with cycle detection
     *
     * @param string $templatePath
     * @throws \Liquid\Exception\MissingFilesystemException
     * @return Template
     */
    public function parseFile($templatePath)
    {
        if (!$this->fileSystem) {
            throw new MissingFilesystemException("Could not load a template without an initialized file system");
        }
        
        // Check for circular includes
        if (in_array($templatePath, self::$processingTemplates)) {
            $this->memoryDebug('WARNING: Circular template inclusion detected', [
                'template_path' => $templatePath,
                'include_chain' => implode(' -> ', self::$processingTemplates)
            ]);
            
            return $this->parse("<!-- Circular include detected for: {$templatePath} -->");
        }
        
        // Track this template
        self::$processingTemplates[] = $templatePath;
        
        $this->memoryDebug('Reading template file', [
            'template_path' => $templatePath
        ]);
        
        try {
            $content = $this->fileSystem->readTemplateFile($templatePath);
            
            $this->memoryDebug('Template file read successfully', [
                'template_path' => $templatePath,
                'content_length' => strlen($content)
            ]);
            
            $result = $this->parse($content);
            
            // Remove from processing stack
            array_pop(self::$processingTemplates);
            
            return $result;
        } catch (\Exception $e) {
            // Clean up processing stack even on error
            array_pop(self::$processingTemplates);
            
            $this->memoryDebug('ERROR reading template file', [
                'template_path' => $templatePath,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * Renders the current template with memory tracking
     *
     * @param array $assigns an array of values for the template
     * @param array $filters additional filters for the template
     * @param array $registers additional registers for the template
     *
     * @return string
     */
    public function render(array $assigns = array(), $filters = null, array $registers = array())
    {
        $this->memoryDebug('Starting template rendering');
        
        // Sanity check
        if (!$this->root) {
            $this->memoryDebug('ERROR: No document root, template not parsed');
            return '<!-- Error: Template not parsed correctly -->';
        }
        
        $context = new Context($assigns, $registers);
    
        if ($this->tickFunction) {
            $context->setTickFunction($this->tickFunction);
        }
    
        if (!is_null($filters)) {
            if (is_array($filters)) {
                $this->filters = array_merge($this->filters, $filters);
            } else {
                $this->filters[] = $filters;
            }
        }
    
        foreach ($this->filters as $filter) {
            if (is_array($filter)) {
                // Unpack a callback saved as second argument
                $context->addFilters(...$filter);
            } else {
                $context->addFilters($filter);
            }
        }
        
        $this->memoryDebug('Filters registered, starting render');
        
        try {
            $result = $this->root->render($context);
            $this->memoryDebug('Rendering completed successfully', [
                'result_length' => strlen($result)
            ]);
            
            // Force garbage collection
            gc_collect_cycles();
            
            return $result;
        } catch (\Exception $e) {
            // Capturar informações detalhadas sobre o erro
            $errorDetails = [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'type' => get_class($e),
                'code' => $e->getCode()
            ];
            
            // Capturar stack trace
            $trace = $e->getTrace();
            $traceInfo = [];
            
            foreach (array_slice($trace, 0, 5) as $i => $frame) {
                $traceInfo[] = sprintf(
                    "#%d %s:%d - %s%s%s()",
                    $i,
                    $frame['file'] ?? 'unknown',
                    $frame['line'] ?? 0,
                    $frame['class'] ?? '',
                    $frame['type'] ?? '',
                    $frame['function'] ?? ''
                );
            }
            
            $errorDetails['trace'] = $traceInfo;
            
            // Verificar se é erro de conversão de array para string
            if (strpos($e->getMessage(), 'Array to string conversion') !== false) {
                $errorDetails['suggestion'] = 'Possível valor array sendo usado como string. Verifique se está usando filtros como |to_string ou |json.';
                
                // Tentar identificar variáveis problemáticas
                $pattern = '/in\s+(.+?):(\d+)/i';
                if (preg_match($pattern, $e->getMessage(), $matches)) {
                    $errorDetails['problem_file'] = $matches[1];
                    $errorDetails['problem_line'] = $matches[2];
                }
            }
            
            // Adicionar informações do contexto quando apropriado
            if (config('app.debug') === true) {
                // Em desenvolvimento, adicionar algumas variáveis do contexto para ajudar na depuração
                $contextKeys = array_keys($context->getAll());
                $errorDetails['context_keys'] = array_slice($contextKeys, 0, 10); // Primeiras 10 chaves
            }
            
            $this->memoryDebug('ERROR during rendering', $errorDetails);
            
            // Force garbage collection
            gc_collect_cycles();
            
            // Gerar mensagem de erro detalhada com HTML comentado
            $errorOutput = "<!-- Template Rendering Error\n";
            $errorOutput .= "Type: " . get_class($e) . "\n";
            $errorOutput .= "Message: " . $e->getMessage() . "\n";
            $errorOutput .= "File: " . $e->getFile() . " (line " . $e->getLine() . ")\n";
            
            if (isset($errorDetails['suggestion'])) {
                $errorOutput .= "Suggestion: " . $errorDetails['suggestion'] . "\n";
            }
            
            if (!empty($traceInfo)) {
                $errorOutput .= "Stack Trace (partial):\n" . implode("\n", $traceInfo) . "\n";
            }
            
            $errorOutput .= "-->";
            
            // Em produção, exibe mensagem mais simples
            if (config('app.env') === 'production') {
                return "<!-- Error rendering template: " . htmlspecialchars($e->getMessage()) . " -->";
            }
            
            return $errorOutput;
        }
    }
    
    /**
     * Get memory usage statistics
     * 
     * @return array
     */
    public function getMemoryInfo()
    {
        return $this->memoryInfo;
    }
    
    /**
     * Clean up references to help GC
     */
    public function __destruct()
    {
        $this->root = null;
        $this->filters = [];
        $this->tickFunction = null;
        $this->fileSystem = null;
        
        // Force garbage collection
        gc_collect_cycles();
    }
}