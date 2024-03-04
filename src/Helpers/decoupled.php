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

if (! function_exists('isMode')) {

	/**
	 * Checks if the current Server Application Programming Interface (SAPI) mode 
	 * matches the specified mode.
	 *
	 * @param 	string 	$mode 	The SAPI mode to compare against. 
	 * 
	 * 	Possible values include:
	 * 
	 *  - 'cli': Indicates that PHP is running from the 
	 * 	command line interface (CLI).
	 *  It typically means that PHP is being executed 
	 * 	from a terminal or command prompt.
	 *  
	 *  - 'cli-server': Indicates that PHP is running as a 
	 * 	built-in web server for development purposes.
	 *  When you execute `php -S` from the command line 
	 * 	to start a built-in web server,
	 *  PHP operates in this mode.
	 * 
	 *  - 'cgi-fcgi': Indicates that PHP is being executed as 
	 * 	a CGI binary, either directly or through a FastCGI interface.
	 *  FastCGI is a variation of CGI that provides 
	 * 	improved performance and scalability by maintaining 
	 * 	persistent connections between the web server 
	 * 	and PHP processes.
	 * 
	 *  - 'embed': Indicates that PHP is being used as an 
	 * 	embedded scripting language within another application.
	 *  It allows developers to integrate PHP into applications 
	 * 	written in languages such as C or C++.
	 * 
	 *  - 'fpm-fcgi': Indicates that PHP is being executed 
	 * 	under the control of the FPM (FastCGI Process Manager)
	 *  process manager, which manages pools of PHP worker 
	 * 	processes to handle incoming requests efficiently.
	 * 
	 *  - 'litespeed': Indicates that PHP is being used 
	 * 	with the LiteSpeed Web Server, which is a 
	 * 	high-performance, secure web server designed for 
	 * 	use with PHP and other dynamic content.
	 * 
	 *  - 'phpdbg': Indicates that PHP is being executed 
	 * 	within the PHP debugger (phpdbg), which allows 
	 * 	developers to step through their PHP code, set 
	 * 	breakpoints, inspect variables, and debug their 
	 * 	scripts interactively.
	 *
	 * @return 	bool 	Returns true if the current SAPI mode matches the 
	 * 					specified mode; otherwise, false.
	 */
	function isMode(string $mode) {
		return php_sapi_name() === $mode;
	}
}

if (! function_exists('isEnv')) {

	/**
	 * Deterines if the environment is the requested label. 
	 * 
	 * The comparison is based on the env('APP_ENV').
	 *
	 * @param string|array $env
	 * @return boolean
	 */
	function isEnv(string|array $env) {

		$env = is_string($env) ? [$env] : $env;

		return in_array(env('APP_ENV'), $env);
	}
}
