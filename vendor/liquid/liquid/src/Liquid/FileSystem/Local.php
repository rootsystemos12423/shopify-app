<?php

/*
 * This file is part of the Liquid package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package Liquid
 */

namespace Liquid\FileSystem;

use Liquid\Exception\NotFoundException;
use Liquid\Exception\ParseException;
use Liquid\FileSystem;
use Liquid\Regexp;
use Liquid\Liquid;

/**
 * This implements an abstract file system which retrieves template files named in a manner similar to Rails partials,
 * ie. with the template name prefixed with an underscore. The extension ".liquid" is also added.
 *
 * For security reasons, template paths are only allowed to contain letters, numbers, and underscore.
 */
class Local implements FileSystem
{
	/**
	 * The root path
	 *
	 * @var string
	 */
	private $root;

	/**
	 * Constructor
	 *
	 * @param string $root The root path for templates
	 * @throws \Liquid\Exception\NotFoundException
	 */
	public function __construct($root)
	{
		// since root path can only be set from constructor, we check it once right here
		if (!empty($root)) {
			$realRoot = realpath($root);
			if ($realRoot === false) {
				throw new NotFoundException("Root path could not be found: '$root'");
			}
			$root = $realRoot;
		}

		$this->root = $root;
	}

	/**
	 * Retrieve a template file
	 *
	 * @param string $templatePath
	 *
	 * @return string template content
	 */
	public function readTemplateFile($templatePath)
	{
		return file_get_contents($this->fullPath($templatePath));
	}

	/**
	 * Resolves a given path to a full template file path, making sure it's valid
	 *
	 * @param string $templatePath
	 *
	 * @throws \Liquid\Exception\ParseException
	 * @throws \Liquid\Exception\NotFoundException
	 * @return string
	 */
	public function fullPath($templatePath)
	{
	// Configurações do Liquid
	Liquid::set('INCLUDE_ALLOW_EXT', true);  // Permite extensão explícita
	Liquid::set('INCLUDE_PREFIX', '');       // Remove o prefixo '_'
	Liquid::set('INCLUDE_SUFFIX', 'liquid'); // Adiciona .liquid automaticamente

	// Remove ./ ou .\ do início do caminho
	$templatePath = ltrim($templatePath, '.\\/');

	// Adiciona .liquid se não tiver extensão
	if (pathinfo($templatePath, PATHINFO_EXTENSION) !== 'liquid') {
		$templatePath .= '.liquid';
	}

	// Validação do nome (permite letras, números, _, -, / e .)
	$nameRegex = new Regexp('/^[a-zA-Z0-9_\-\.\/]+$/');
	if (!$nameRegex->match($templatePath)) {
		throw new ParseException("Invalid template name: {$templatePath}");
	}

	// Constrói o caminho completo
	$fullPath = realpath($this->root . DIRECTORY_SEPARATOR . $templatePath);

	if ($fullPath === false) {
		throw new NotFoundException("File not found: {$this->root}/{$templatePath}");
	}

	return $fullPath;
	}
}
