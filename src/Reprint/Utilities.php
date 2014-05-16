<?php
namespace Reprint;

abstract class Utilities {
	protected static $defaultRemovedWords	= array('and', 'or');

	public static function stringToSlug($string, array $removedWords = null){
		if(!isset($removedWords)){
			$removedWords	= static::$defaultRemovedWords;
		}

		$string	= strtolower($string);

		// Strip specific apostrophes
		$string	= preg_replace('~(\\w)\'(s|t)([^\\w])~', '$1$2$3', $string);

		// Remove unnecessary words
		$removedWords	= implode('|', array_map('preg_quote', $removedWords));
		$string	= preg_replace('/( (?:'.$removedWords.') )+/', '-', $string);

		// Remove remaining non-alphanumeric characters
		$string	= preg_replace('/[^a-z0-9]+/', '-', $string);

		$string	= trim($string, '-');
		return $string;
	}

	public static function escape($string){
		return htmlspecialchars($string, \ENT_QUOTES, 'UTF-8');
	}

	/**
	 * Replaces basic template tags (e.g. `{{tag}}`) with the provided values
	 *
	 * @param string $content	The templated content
	 * @param array $values		An associative array of values to replace in the template content
	 * @return string			The rendered template
	 */
	public static function renderTemplate($content, array $values = null){
		if($values){
			// Replace tags
			$content = preg_replace_callback('~\{\{([\w\d_]+)\}\}~', function($matches) use($values){
				$token	= $matches[1];
				if(!isset($values[$token])){
					// Unknown - do not replace
					return $matches[0];
				}

				return $values[$token];

			}, $content);
		}

		return $content;
	}
}
