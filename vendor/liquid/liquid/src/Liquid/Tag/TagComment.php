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

use Liquid\AbstractTag;
use Liquid\Context;

/**
 * Creates a comment; everything inside will be completely ignored
 * This implementation prevents recursive processing of its contents
 *
 * Example:
 *
 *     {% comment %} This will be ignored {% endcomment %}
 */
class CommentBlock extends AbstractTag
{
    /**
     * @var string Raw content of the comment block
     */
    private $rawContent;
    
    /**
     * Constructor
     *
     * @param string $markup
     * @param array &$tokens
     * @param \Liquid\FileSystem $fileSystem
     */
    public function __construct($markup, array &$tokens, \Liquid\FileSystem $fileSystem = null)
    {
        parent::__construct($markup, $tokens, $fileSystem);
        
        // Extract everything up to the end tag, without processing
        $this->rawContent = '';
        $level = 1;
        
        while (count($tokens) > 0) {
            $token = array_shift($tokens);
            
            // Look for nested comments
            if (preg_match('/\{% comment %}/', $token)) {
                $level++;
            }
            
            // Look for closing tags
            if (preg_match('/\{% endcomment %}/', $token)) {
                $level--;
                if ($level === 0) {
                    break; // Exit when we find the matching endcomment
                }
            }
            
            // Store raw content (only for debugging, we don't actually use it)
            $this->rawContent .= $token;
        }
    }
    
    /**
     * Renders nothing - comments are discarded
     *
     * @param Context $context
     * @return string Empty string
     */
    public function render(Context $context)
    {
        return '';
    }
}

/**
 * Alias for CommentBlock to maintain backward compatibility
 */
class TagComment extends CommentBlock
{
    // This class intentionally left empty
}