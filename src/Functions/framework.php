<?php

if (! function_exists('app')) {
	
	/**
	 * Bootstraps the application.
	 *
	 * @return \Aeros\Src\Classes\ServiceContainer
	 * 
	 * @throws \Exception
	 */
	function app(): \Aeros\Src\Classes\ServiceContainer
	{
		return \Aeros\Src\Classes\ServiceContainer::getInstance()
			->setBaseDir(env('APP_ROOT_DIR') ?? dirname(dirname(__DIR__)));
	}
}

if (! function_exists('scan')) {

	/**
	 * Scans a directory for files with specific extensions.
	 *
	 * @param 	string 	$path 		The path of the directory to scan.
	 * @param 	array 	$filenames 	(optional) The array of filenames to filter by.
	 * @param 	mixed 	$extensions (optional) The array of file extensions to 
	 * 								filter by. Defaults to ['php'].
	 * @return 	array 				An array of matching file names.
	 *
	 * @throws \Exception If the provided path is empty or the directory does not exist.
	 */
	function scan(string $path, array $filenames = [], $extensions = ['php']): array
	{
		if (empty($path)) {
			throw new \Exception("ERROR[dir] 'path' should not be empty.");
		}

		if (! is_dir($path)) {
            throw new \Exception("ERROR[dir] Directory '{$path}' does not exist.");
        }

		return array_filter(scandir($path), function ($file) use ($extensions, $filenames) {
			$parts = pathinfo($file);

			if (in_array($parts['extension'], $extensions)) {

				// Return all files that match the extensions
				if (empty($filenames)) {
					return true;
				}

				// Filenames were provided
				if (in_array($parts['filename'], $filenames)) {
					return true;
				}
			}
		});
	}
}

if (! function_exists('load')) {

	/**
	 * Loads PHP files from a specified directory.
	 *
	 * This function scans the specified directory for PHP files and includes 
	 * them using the built-in `require` function. It is a convenient way to load 
	 * multiple PHP files at once.
	 *
	 * @param 	string 	$path 		The path to the directory containing the 
	 * 								PHP files.
	 * @param 	array 	$filenames 	An optional array of specific filenames to 
	 * 								load. If provided, only files with matching 
	 * 								filenames will be included.
	 *
	 * @throws 	\Exception 	If no files are found in the specified directory.
	 *
	 * @return 	bool 		Returns true if the files are loaded successfully.
	 */
	function load(string $path, array $filenames = []) {

		$files = scan($path, $filenames);

		if (empty($files)) {
			throw new \Exception("ERROR[load] No files were found in '$path'.");
		}

		foreach ($files as $file) {
			require $path . '/' . $file;
		}

		return true;
	}
}

if (! function_exists('view')) {

	/**
	 * View function is a wrapper for the View class which renders/makes and returns
	 * HTML content to the browser.
	 *
	 * @param string $view
	 * @param array $values
	 * @param string $subfolder
	 */
	function view(string $view, array $values = [], string $subfolder = '')
	{
		return app()->view->make($view, $values, $subfolder);
	}
}

if (! function_exists('response')) {

	/**
	 * Response function is a wrapper for Response class and formats and returns 
	 * content as JSON, XML or any other content type.
	 *
	 * @param mixed $data
	 * @param ?int $code
	 * @param string $type
	 * @return mixed
	 */
	function response($data = null, int $code = null, string $type = \Aeros\Src\Classes\Response::HTML)
	{
		if (is_null($data)) {
			return app()->response;
		}

		return app()->response->type($data, $code, $type);
	}
}

if (! function_exists('abort')) {

	/**
	 * Abort function is a wrapper for Response class and ends the script. 
	 *
	 * @param mixed $data
	 * @param ?int $code
	 * @param string $type
	 * @return mixed
	 */
	function abort($data = null, int $code = 403, string $type = \Aeros\Src\Classes\Response::HTML) 
	{
		printf('%s', response($data, $code));

		exit;
	}
}

if (! function_exists('request')) {

	/**
	 * Makes HTML requests with cURL PHP built-in function, also, grabs values, params, from
	 * a specific HTTP method.
	 *
	 * @param mixed $opts
	 * @param array $keys
	 */
	function request(mixed $opts = '', array $keys = [])
	{
		if (empty($opts)) {
			return app()->request;
		}

		return app()->request->setOptions($opts, $keys);
	}
}

if (! function_exists('redirect')) {

	/**
	 * Redirect function is a wrapper for Redirect class and performs a redirect
	 * with arguments.
	 *
	 * @param string $redirect
	 * @param array $arguments
	 * @return void
	 */
	function redirect(string $redirect, array $arguments = [], string $request_method = 'GET')
	{
		return app()->redirect->goto($redirect, $arguments, $request_method);
	}
}

if (! function_exists('dd')) {

	/**
	 * Dump and die for testing purposes.
	 *
	 * @param string|mixed $args
	 * @return void
	 */
	function dd(...$args) 
	{
		// On terminal
		if (strpos(php_sapi_name(), 'cli') !== false) {
			$position = [debug_backtrace()[0]['file'] . ':' . debug_backtrace()[0]['line']];
			die(response(array_merge($position, $args)));
		}

		print_r(
			response(
				array_values(
					array_filter(
						array_map(
							function ($point) {
								if (! isset($point['file'])) {
									return null;
								}

								$key = $point['function'] . ' => ' . ($point['file'] ?? '') . '#L:' . ($point['line'] ?? '');

								unset($point['function'], $point['file'], $point['line']);

								if (isset($point['args'])) {
									foreach ($point['args'] as &$value) {
										$value = is_object($value) ? $value : $value;
									}
								}

								$index = [$key => $point];

								return $index;
							},
							debug_backtrace()
						)
					)
				),
				200,
				Aeros\Src\Classes\Response::JSON
			)
		);

		exit;
	}
}

if (! function_exists('cache')) {

	/**
	 * It returns a cache object.
	 *
	 * @param ?string $connection
	 */
	function cache($connection = null)
	{
		return app()->cache->setConnection($connection);
	}
}

if (! function_exists('component')) {

	/**
	 * Component function is a wrapper for the Component class which renders and returns
	 * HTML content.
	 *
	 * @param string $component
	 * @param array $data
	 * @param bool $dump If true, the component body will be returned instead of being dumped
	 * @return mixed
	 */
	function component(string $component, array $data = [], bool $dump = true)
	{
		return app()->component->render($component, $data, $dump);
	}
}

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
		if (empty($key)) {
			return null;
		}

		if (! is_null($default)) {
			return $_ENV[$key] = $default;
		}

		if (array_key_exists($key, $_ENV)) {
			return $_ENV[$key];
		}

		return null;
	}
}

if (! function_exists('sanitizeWith')) {

	/**
	 * Sanitizes a value by reference.
	 *
	 * @param mixed $vector
	 * @param array $categories
	 * @param string $replacement
	 * @return mixed
	 */
	function sanitizeWith($vector, array $categories, string $replacement = ''): mixed
	{
		$policies = config('security');

		foreach ($categories as $category) {
			if (isset($policies[$category])) {
				$vector = str_replace($policies[$category], $replacement, $vector);
			}
		}

		return $vector;
	}
}

if (! function_exists('db')) {

	/**
	 * Wrapper for DB conection and all its handlers.
	 * 
	 * @param ?string $connection - `sqlite-server-01`. This should be listed in config('db.connections').
	 * @return \Aeros\Src\Classes\Db
	 */
	function db(string $connection = null): \Aeros\Src\Classes\Db
	{
		return app()->db->connect($connection);
	}
}

if (! function_exists('csrf')) {

	/**
	 * Embeds a CSRF token into a hidden input.
	 *
	 * @return void
	 */
	function csrf()
	{
		return app()->security->csrf();
	}
}

if (! function_exists('worker')) {

	/**
	 * Returns the main app worker.
	 *
	 * @return \Aeros\Src\Classes\Worker
	 */
	function worker(): \Aeros\Src\Classes\Worker {
		return app()->worker;
	}
}

if (! function_exists('encryptor')) {

	/**
	 * Returns the global encryptor.
	 *
	 * @return \Aeros\Src\Classes\Encryptor
	 */
	function encryptor(): \Aeros\Src\Classes\Encryptor {
		return app()->encryptor;
	}
}

if (! function_exists('session')) {

	/**
	 * Returns the global session object.
	 *
	 * @return \Aeros\Src\Classes\Session
	 */
	function session(): \Aeros\Src\Classes\Session {
		return app()->session;
	}
}

if (! function_exists('config')) {

	/**
	 * Returns values or objects from config files.
	 *
	 * @return mixed
	 */
	function config(string $from, mixed $default = null): mixed {
		return app()->config->getFrom($from, $default);
	}
}

if (! function_exists('logger')) {

	/**
	 * Appends a message into a log file.
	 *
	 * @param mixed $message
	 * @param string $logFile Path and filename. If empty, error.log is set.
	 * @param bool $createFile Flag to create the log file if it does not exist.
	 * @return boolean
	 */
	function logger(mixed $message, string $logFile = '', bool $createFile = false): bool {
		return app()->logger->log($message, $logFile, $createFile);
	}
}

if (! function_exists('queue')) {

	/**
	 * Returns the queue instance.
	 *
	 * @return \Aeros\Src\Classes\Queue
	 */
	function queue(): \Aeros\Src\Classes\Queue {
		return app()->queue;
	}
}

if (! function_exists('cron')) {

	/**
	 * Returns an instance of Cron. 
	 * This is a wrapper for Scheduler class.
	 *
	 * @return \Aeros\Src\Classes\Cron
	 */
	function cron(): \Aeros\Src\Classes\Cron {
		return app()->cron;
	}
}

if (! function_exists('updateEnv')) {

	/**
	 * Updates environment variables in the .env file.
	 *
	 * This function takes an associative array of new environment variable values
	 * and updates the corresponding values in the .env file. It uses regular
	 * expressions to find and replace the existing values for the specified keys.
	 *
	 * @param 	array 	$newEnvValues 	An associative array where keys represent the
	 *                            		environment variable names, and values are the
	 *                            		new values to be set.
	 *
	 * ```php
	 * 	updateEnvVariable(['APP_KEY' => 'new app key']);
	 * ```
	 * @return 	bool|int 	Returns the number of bytes written to the .env file on success,
	 *                  	or false on failure. In case of failure, an error message can
	 *                  	be retrieved with error_get_last().
	 */
	function updateEnvVariable(array $newEnvValues): bool|int {

		$envFile = app()->basedir . '/../.env';
		$envBody = file_get_contents($envFile);

		foreach ($newEnvValues as $key => $value) {
			if (! ($envBody = preg_replace("/($key=)(.*)/", $key . '=' . $value, $envBody))) {
				return false;
			}
		}

		return file_put_contents($envFile, $envBody);
	}
}

if (!function_exists('updateJsonNode')) {

	/**
	 * Update values of nested nodes in a JSON file.
	 *
	 * This function updates the values of nested nodes in a JSON file based on 
	 * the provided key-value pairs.
	 *
	 * @param 	array  	$keyValues 	An associative array where keys represent 
	 * 								the nested structure within the JSON file 
	 * 								and values are the new values to be updated.
	 * @param 	string 	$jsonFile  	The path to the JSON file to be updated.
	 * 
	 * ```php
	 * 	updateJsonNode(
	 * 		['environments.staging.name' => 'newdb'], 
	 * 		app()->basedir . '/../phinx.json'
	 * 	);
	 * ```
	 *
	 * @return bool|int Returns true if the JSON file was successfully updated, 
	 * 					false if a key is not found in the JSON structure, or 
	 * 					the number of bytes written to the file if successful.
	 */
	function updateJsonNode(array $keyValues, string $jsonFile): bool|int {

        $jsonConfig = json_decode(file_get_contents($jsonFile), true);

        foreach ($keyValues as $key => $value) {

            $keys = explode('.', $key);
            $tempConfig = &$jsonConfig;

            foreach ($keys as $nestedKey) {

                if (! isset($tempConfig[$nestedKey])) {
                    return false;
                }

                $tempConfig = &$tempConfig[$nestedKey];
            }

            $tempConfig = $value;
        }

        return file_put_contents(
			$jsonFile, 
			str_replace('\/', '/', json_encode($jsonConfig, JSON_PRETTY_PRINT))
		);
	}
}

if (! function_exists('cookie')) {

	/**
	 * Manages cookies.
	 *
	 * This function allows setting, getting, or retrieving the CookieJar instance.
	 *
	 * @param 	string 	$cookie_name 		The name of the cookie.
	 * @param 	mixed 	$cookie_value 		(Optional) The value of the cookie. 
	 * 													Default is null.
	 * @param 	int 	$cookie_expiration 	(Optional) The expiration time of the 
	 * 													cookie. Default is 0.
	 * @param 	string 	$path 				(Optional) The path on the server in 
	 * 													which the cookie will be 
	 * 													available. Default is "/".
	 * @param 	string 	$cookie_domain 		(Optional) The domain that the cookie 
	 * 													is available to. Default 
	 * 													is an empty string.
	 * @param 	bool 	$secure 			(Optional) Indicates whether the 
	 * 													cookie should only be 
	 * 													transmitted over a secure 
	 * 													HTTPS connection. 
	 * 													Default is false.
	 * @param 	bool 	$httponly 			(Optional) When true, the cookie will 
	 * 													be made accessible only 
	 * 													through the HTTP protocol. 
	 * 													Default is true.
	 *
	 * @return 	mixed 	Returns the value of the specified cookie when only the 
	 * 					cookie name is provided, sets the cookie when both name 
	 * 					and value are provided, or returns the CookieJar instance 
	 * 					when no parameters are provided.
	 */
	function cookie(
		string $cookie_name = null, 
		mixed $cookie_value = null, 
		int $cookie_expiration = 0, 
		string $path = "/", 
		string $cookie_domain = '', 
		bool $secure = false, 
		bool $httponly = true) : mixed {

		// Return cookie value by name only
		if (! is_null($cookie_name) && is_null($cookie_value)) {
			return app()->cookie->get($cookie_name);
		}

		// Set or create a new cookie
		if (! is_null($cookie_name) && ! is_null($cookie_value)) {
			return app()->cookie->set(
				$cookie_name, 
				$cookie_value, 
				$cookie_expiration, 
				$path, 
				$cookie_domain, 
				$secure, 
				$httponly
			);
		}

		// Return cookie instance to use 'clear' and 'delete' methods
		if (is_null($cookie_name) && is_null($cookie_value)) {
			return app()->cookie;
		}
	}
}
