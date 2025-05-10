<?php

namespace Liquid\Tag;

use Liquid\AbstractTag;
use Liquid\Document;
use Liquid\Context;
use Liquid\Exception\MissingFilesystemException;
use Liquid\Exception\ParseException;
use Liquid\Liquid;
use Liquid\LiquidException;
use Liquid\FileSystem;
use Liquid\Regexp;
use Liquid\Template;
use Illuminate\Support\Facades\Log;

/**
 * Includes another, partial, template
 *
 * Example:
 *
 *     {% include 'foo' %}
 *
 *     Will include the template called 'foo'
 *
 *     {% include 'foo' with 'bar' %}
 *
 *     Will include the template called 'foo', with a variable called foo that will have the value of 'bar'
 *
 *     {% include 'foo' for 'bar' %}
 *
 *     Will loop over all the values of bar, including the template foo, passing a variable called foo
 *     with each value of bar
 */
class TagInclude extends AbstractTag
{
    /**
     * @var string The name of the template
     */
    private $templateName;

    /**
     * @var bool True if the variable is a collection
     */
    private $collection;

    /**
     * @var mixed The value to pass to the child template as the template name
     */
    private $variable;

    /**
     * @var Document The Document that represents the included template
     */
    private $document;

    /**
     * @var string The Source Hash
     */
    protected $hash;

    /**
     * Constructor
     *
     * @param string $markup
     * @param array $tokens
     * @param FileSystem $fileSystem
     *
     * @throws \Liquid\Exception\ParseException
     */
    public function __construct($markup, array &$tokens, FileSystem $fileSystem = null)
    {
        $regex = new Regexp('/("[^"]+"|\'[^\']+\'|[^\'"\s]+)(\s+(with|for)\s+(' . Liquid::get('QUOTED_FRAGMENT') . '+))?/');

        if (!$regex->match($markup)) {
            throw new ParseException("Error in tag 'include' - Valid syntax: include '[template]' (with|for) [object|collection]");
        }

        $unquoted = (strpos($regex->matches[1], '"') === false && strpos($regex->matches[1], "'") === false);

        $start = 1;
        $len = strlen($regex->matches[1]) - 2;

        if ($unquoted) {
            $start = 0;
            $len = strlen($regex->matches[1]);
        }

        $this->templateName = substr($regex->matches[1], $start, $len);

        if (isset($regex->matches[1])) {
            $this->collection = (isset($regex->matches[3])) ? ($regex->matches[3] == "for") : null;
            $this->variable = (isset($regex->matches[4])) ? $regex->matches[4] : null;
        }

        $this->extractAttributes($markup);

        parent::__construct($markup, $tokens, $fileSystem);
    }

    /**
     * Parses the tokens
     *
     * @param array $tokens
     *
     * @throws \Liquid\Exception\MissingFilesystemException
     */
    public function parse(array &$tokens)
    {
        if ($this->fileSystem === null) {
            throw new MissingFilesystemException("No file system");
        }

        // Try to read the source, first from snippets, then from sections
        $source = $this->readTemplateFromMultiplePaths($this->templateName);

        $cache = Template::getCache();

        if (!$cache) {
            // tokens in this new document
            $templateTokens = Template::tokenize($source);
            $this->document = new Document($templateTokens, $this->fileSystem);
            return;
        }

        $this->hash = md5($source);
        $this->document = $cache->read($this->hash);

        if ($this->document == false || $this->document->hasIncludes() == true) {
            $templateTokens = Template::tokenize($source);
            $this->document = new Document($templateTokens, $this->fileSystem);
            $cache->write($this->hash, $this->document);
        }
    }

    /**
     * Try to read template from multiple locations - first snippets, then sections
     *
     * @param string $templateName
     * @return string
     * @throws \Liquid\LiquidException
     */
    private function readTemplateFromMultiplePaths($templateName)
    {
        // Check if it already has a path or extension
        $hasExtension = (strpos($templateName, '.') !== false);
        $fileName = $hasExtension ? $templateName : $templateName . '.liquid';
        
        // Search paths to try in order
        $searchPaths = [
            $fileName,
            'snippets/' . $fileName,
            'sections/' . $fileName
        ];
        
        foreach ($searchPaths as $path) {
            try {
                return $this->fileSystem->readTemplateFile($path);
            } catch (\Exception $e) {
                // Continue to next path
                continue;
            }
        }
        
        // If we get here, we couldn't find the file anywhere
        $searchPathsStr = implode(', ', $searchPaths);
        Log::warning("Failed to find include template '{$fileName}' in any of these locations: {$searchPathsStr}");
        throw new LiquidException("File not found: {$fileName} (searched in snippets and sections)");
    }

    /**
     * Check for cached includes; if there are - do not use cache
     *
     * @see Document::hasIncludes()
     * @return boolean
     */
    public function hasIncludes()
    {
        if ($this->document->hasIncludes() == true) {
            return true;
        }

        $source = $this->readTemplateFromMultiplePaths($this->templateName);

        if (Template::getCache()->exists(md5($source)) && $this->hash === md5($source)) {
            return false;
        }

        return true;
    }

    /**
     * Renders the node
     *
     * @param Context $context
     *
     * @return string
     */
    public function render(Context $context)
    {
        $result = '';
        $variable = $context->get($this->variable);

        $context->push();

        foreach ($this->attributes as $key => $value) {
            $context->set($key, $context->get($value));
        }

        if ($this->collection) {
            foreach ($variable as $item) {
                $context->set($this->templateName, $item);
                $result .= $this->document->render($context);
            }
        } else {
            if (!is_null($this->variable)) {
                $context->set($this->templateName, $variable);
            }

            $result .= $this->document->render($context);
        }

        $context->pop();

        return $result;
    }
}