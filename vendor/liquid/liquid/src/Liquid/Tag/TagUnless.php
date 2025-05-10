<?php

/*
 * This file is part of the Liquid package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package Liquid
 */

namespace Liquid\Tag;

use Liquid\LiquidException;
use Illuminate\Support\Facades\Log;
use Liquid\AbstractBlock;
use Liquid\Decision;

/**
 * An unless statement
 *
 * Example:
 *
 *     {% unless true %} YES {% else %} NO {% endunless %}
 *
 *     will return:
 *     NO
 */
class TagUnless extends Decision
{
    /**
     * Stack of opening and closing tags for tracking
     * Used for debugging unclosed unless tags
     * 
     * @var array
     */
    protected static $tagStack = [];
    
    /**
     * Debug mode flag
     * 
     * @var bool
     */
    protected static $debugMode = true;
    
    /**
     * Block delimiter
     * 
     * @var string
     */
    protected $blockDelimiter = 'unless';
    
    /**
     * Override constructor to add tag tracking
     * 
     * @param string $markup
     * @param array $tokens
     * @param object $fileSystem
     * @throws LiquidException
     */
    public function __construct($markup, &$tokens, $fileSystem = null)
    {
        // Add opening tag to stack with file info
        if (self::$debugMode) {
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
            $file = isset($trace[1]['file']) ? $trace[1]['file'] : 'unknown';
            $line = isset($trace[1]['line']) ? $trace[1]['line'] : 0;
            
            self::$tagStack[] = [
                'type' => 'unless',
                'markup' => $markup,
                'file' => $file,
                'line' => $line,
                'status' => 'open'
            ];
            
        }
        
        // Call parent constructor
        parent::__construct($markup, $tokens, $fileSystem);
    }
    
    /**
     * Negates the result of the condition
     * 
     * @param bool $display
     * @return bool
     */
    protected function negateIfUnless($display)
    {
        return !$display;
    }

    /**
     * Override the block end regex to make it more flexible for different endunless formats
     *
     * @return string
     */
    protected function blockEndRegexp()
    {
        return '/^\s*end' . $this->blockDelimiter . '\s*$/';
    }

    /**
     * Custom implementation to find block ending - this is the key override
     * 
     * @param array $tokens Array of tokens
     * @return bool True if the end tag is found
     */
    protected function isBlockDelimiter($token)
    {
        if (!is_string($token)) {
            return false;
        }

        // Strip hyphens and whitespace to check for the core tag
        $cleanToken = preg_replace('/\{%[-]?\s*|\s*[-]?%\}/', '', $token);
        return preg_match('/^end' . $this->blockDelimiter . '$/i', trim($cleanToken));
    }

    /**
     * Override the parent parse method to handle our custom block delimiter check
     *
     * @param array $tokens
     * @return array
     * @throws LiquidException
     */
    public function parse(&$tokens)
    {
        try {
            // Call parent parse method for simplicity, now that we've defined blockDelimiter
            $result = parent::parse($tokens);
            
            // Mark tag as closed in stack
            if (self::$debugMode && !empty(self::$tagStack)) {
                $tagInfo = array_pop(self::$tagStack);
                $tagInfo['status'] = 'closed';
                
            }
            
            return $result;
        } catch (LiquidException $e) {
            // If we catch an exception, log the state of the tag stack
            if (self::$debugMode) {
                $openTags = array_filter(self::$tagStack, function($tag) {
                    return $tag['status'] === 'open';
                });
                
                if (!empty($openTags)) {
                    Log::error("TagUnless: Unclosed tags detected", [
                        'open_tags' => $openTags,
                        'error' => $e->getMessage()
                    ]);
                    
                    // Provide more helpful error message
                    if (strpos($e->getMessage(), 'was never closed') !== false) {
                        $lastTag = end($openTags);
                        throw new LiquidException(
                            "Unclosed unless tag: {$lastTag['markup']} in file {$lastTag['file']} on line {$lastTag['line']}",
                            0,
                            $e
                        );
                    }
                }
            }
            
            // Re-throw the exception
            throw $e;
        }
    }
    
    /**
     * Enable or disable debug mode
     * 
     * @param bool $enabled
     */
    public static function setDebugMode($enabled)
    {
        self::$debugMode = $enabled;
    }
    
    /**
     * Check for unclosed tags
     * 
     * @return array Unclosed tags info
     */
    public static function getUnclosedTags()
    {
        return array_filter(self::$tagStack, function($tag) {
            return $tag['status'] === 'open';
        });
    }
    
    /**
     * Clear the tag stack
     */
    public static function clearTagStack()
    {
        self::$tagStack = [];
    }
    
    /**
     * Log the current state of the tag stack
     */
    public static function logTagStackState()
    {
        Log::debug("TagUnless: Current tag stack state", [
            'stack' => self::$tagStack,
            'stack_size' => count(self::$tagStack),
            'unclosed_count' => count(self::getUnclosedTags())
        ]);
    }
}