<?php

/*
|----------------------------------------------
| All functions here are decoupled from the 
| standard library and other packages, they act
| as stand-alone functions.
*/

if (! function_exists('env')) {

	/**
	 * Gets an ENV variable if exists, otherwise, if $default is not null, sets
	 * the new value.
	 *
	 * @param string $key
	 * @param string $default
	 * @return mixed
	 */
	function env(string $key, $default = NULL) 
	{
        if (! array_key_exists($key, $_ENV) && ! is_null($default)) {
			return $default;
		}

		if (array_key_exists($key, $_ENV)) {
			return $_ENV[$key];
		}
	}
}
