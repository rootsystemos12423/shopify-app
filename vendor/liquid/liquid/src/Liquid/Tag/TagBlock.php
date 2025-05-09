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

use Liquid\AbstractBlock;
use Liquid\Exception\ParseException;
use Liquid\FileSystem;
use Liquid\Regexp;

/**
 * Marks a section of a template as being reusable.
 *
 * Example:
 *
 *     {% block foo %} bar {% endblock %}
 */
class TagBlock extends AbstractBlock
{
	/**
	 * The variable to assign to
	 *
	 * @var string
	 */
	private $block;

	/**
	 * Constructor
	 *
	 * @param string $markup
	 * @param array $tokens
	 * @param FileSystem $fileSystem
	 *
	 * @throws \Liquid\Exception\ParseException
	 * @return \Liquid\Tag\TagBlock
	 */
	public function __construct($markup, array &$tokens, FileSystem $fs = null)
	{
		parent::__construct($markup, $tokens, $fs); // sem verificar $markup
	}

}
