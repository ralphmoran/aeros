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
	 * @return array
	 */
	function singularize(string $word): array
	{
		return (new \Symfony\Component\String\Inflector\EnglishInflector())->singularize($word);
	}
}

if (! function_exists('class_basename')) {

	/**
	 * Returns the class name from format 'Aeros\Classes\User'.
	 *
	 * @param 	string 	$string
	 * @param 	string 	$separator
	 * @return 	string
	 */
	function class_basename(string $string, string $separator = '\\'): string
	{
		$parts = explode($separator, $string);

		return end($parts);
	}
}

if (! function_exists('truncate')) {

    /**
     * Truncate a string to a specified length and add a substitute string.
     * The substitute is included in the total length.
     *
     * @param   string  $string The string to truncate
     * @param   int     $length Maximum length of the resulting string
     * @param   string  $substitute The string to append (default: '...')
     * @return  string  The truncated string
     */
    function truncate(string $string, int $length = 30, string $substitute = '...'): string
    {
        if (mb_strlen($string, 'UTF-8') <= $length) {
            return $string;
        }

        $substituteLength = mb_strlen($substitute, 'UTF-8');

        if ($substituteLength >= $length) {
            return mb_substr($string, 0, $length, 'UTF-8');
        }

        return mb_substr(
            $string,
            0,
            $length - $substituteLength,
            'UTF-8'
        ) . $substitute;
    }
}

if (! function_exists('truncateAtWord')) {

    /**
     * Truncate a string at the last complete word within the specified length.
     * The substitute is included in the total length.
     *
     * @param   string  $string The string to truncate
     * @param   int     $length Maximum length of the resulting string
     * @param   string  $substitute The string to append (default: '...')
     * @return  string  The truncated string
     */
    function truncateAtWord(string $string, int $length = 30, string $substitute = '...'): string
    {
        if (mb_strlen($string, 'UTF-8') <= $length) {
            return $string;
        }

        $substituteLength = mb_strlen($substitute, 'UTF-8');

        if ($substituteLength >= $length) {
            return mb_substr($string, 0, $length, 'UTF-8');
        }

        $maxStringLength = $length - $substituteLength;

        $truncated = mb_substr(
            $string,
            0,
            $maxStringLength,
            'UTF-8'
        );

        $lastSpace = mb_strrpos($truncated, ' ', 0, 'UTF-8');

        if ($lastSpace === false || $lastSpace === 0) {
            return $truncated . $substitute;
        }

        return mb_substr(
            $truncated,
            0,
            $lastSpace,
            'UTF-8'
        ) . $substitute;
    }
}
