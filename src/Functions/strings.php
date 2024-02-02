<?php

if (! function_exists('str_find')) {

	/**
	 * Searches for a string within another string based on a list and returns
	 * true if one of the items from the list was found.
	 *
	 * @param string $haystack
	 * @param array $needles
	 * @return boolean
	 */
	function str_find(string $haystack, array $needles) : bool 
	{
		if (empty($needles)) {
			return false;
		}

		foreach ($needles as $needle) {
			if (strpos($haystack, $needle) === 0) {
				return true;
			} 
		}

		return false;
	}
}

if (! function_exists('pluralize')) {

	/**
	 * Pluralizes a word.
	 *
	 * @param string $word
	 * @return string
	 */
	function pluralize(string $word): string
	{
		return implode('', (new \Symfony\Component\String\Inflector\EnglishInflector())->pluralize($word));
	}
}

if (! function_exists('singularize')) {

	/**
	 * Singularizes a word.
	 *
	 * @param string $word
	 * @return string
	 */
	function singularize(string $word): string
	{
		return implode('', (new \Symfony\Component\String\Inflector\EnglishInflector())->singularize($word));
	}
}