<?php

if (! function_exists('app')) {
	
	/**
	 * Bootstraps the application.
	 *
	 * @return \Aeros\Lib\Classes\ServiceContainer
	 * 
	 * @throws \Exception
	 */
	function app(): \Aeros\Lib\Classes\ServiceContainer
	{
		return \Aeros\Lib\Classes\ServiceContainer::getInstance()
			->setBaseDir(env('APP_ROOT_DIR') ?? dirname(dirname(__DIR__)));
	}
}

if (! function_exists('scan')) {

	/**
	 * Scans a dir for files with specific extensions.
	 *
	 * @param string $path
	 * @param mixed $extensions:default ('php')
	 * @return array
	 * 
	 * @throws \Exception
	 */
	function scan(string $path, $extensions = ['php']): array
	{
		if (empty($path)) {
			throw new \Exception("ERROR[dir] 'path' should not be empty.");
		}

		if (! is_dir($path)) {
            throw new \Exception("ERROR[dir] Directory '{$path}' does not exist.");
        }

		return array_filter(scandir($path), function ($file) use ($extensions) {
			$file_ext = pathinfo($file, PATHINFO_EXTENSION);

			if (in_array($file_ext, $extensions)) {
				return $file_ext;
			}
		});
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
	 * @param int $code
	 * @param string $type
	 * @return mixed
	 */
	function response($data = '', int $code = 200, string $type = \Aeros\Lib\Classes\Response::JSON)
	{
		return app()->response->type($data, $code, $type);
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
		if (strpos(PHP_SAPI, 'cli') !== false) {
			$position = [debug_backtrace()[0]['file'] . ':' . debug_backtrace()[0]['line']];
			die(response(array_merge($position, $args)));
		}

		die(
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
				)
			)
		);
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
	 * HTML content to the browser.
	 *
	 * @param string $component
	 * @param array $data
	 * @param bool $return If true, the component body will be returned instead of being dumped
	 * @return mixed
	 */
	function component(string $component, array $data = [], bool $return = false)
	{
		return app()->component->render($component, $data, $return);
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

if (! function_exists('setCookieWith')) {

	/**
	 * Creates a cookie with mixed values (callables are not supported).
	 *
	 * @param string $key
	 * @param mixed $value Callable type is not supported
	 * @param integer|array $params
	 * @param string $path
	 * @param string $domain
	 * @param boolean $secure
	 * @param boolean $httponly
	 * @return boolean
	 */
	function setCookieWith(
		string $key, 
		mixed $value, 
		int|array $params, 
		string $path = '/', 
		string $domain = '', 
		bool $secure = false, 
		bool $httponly = false): bool
	{
		// Cast $value
		if (! is_string($value)) {
			$value = serialize($value);
		}

		$status = setcookie($key, $value, $params, $path, $domain, $secure, $httponly);

		if ($status) {
			$_COOKIE[$key] = $value;
			$_REQUEST[$key] = $value;
		}

		return $status;
	}
}

if (! function_exists('deleteCookie')) {

	/**
	 * Deletes a cookie by $key.
	 *
	 * @param string $key
	 * @return boolean
	 */
	function deleteCookie(string $key, bool $clear = false): bool
	{
		$status = setCookieWith($key, '', time() - 60);

		if ($status) {
			if (isset($_COOKIE[$key])) {
				unset($_COOKIE[$key]);
			}

			if (isset($_REQUEST[$key])) {
				unset($_REQUEST[$key]);
			}

			if ($clear) {
				cookie()->clear();
			}
		}

		return $status;
	}
}

if (! function_exists('cookie')) {

	/**
	 * Returns the main cookie or a specific one.
	 *
	 * @param string $key
	 * @return mixed
	 */
	function cookie(string $key = null): mixed
	{
		if (! is_null($key)) {
			return app()->cookie->get($key);
		}

		return app()->cookie;
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
	 * @return \Aeros\Lib\Classes\Db
	 */
	function db(string $connection = null): \Aeros\Lib\Classes\Db
	{
		return app()->db->connect($connection);
	}
}

if (! function_exists('csrf')) {

	/**
	 * Embeds a CSRF token into a hidden input.
	 *
	 * @return string
	 */
	function csrf(): string
	{
		return app()->security->csrf();
	}
}

if (! function_exists('worker')) {

	/**
	 * Returns the main app worker.
	 *
	 * @return \Aeros\Lib\Classes\Worker
	 */
	function worker(): \Aeros\Lib\Classes\Worker {
		return app()->worker;
	}
}

if (! function_exists('encryptor')) {

	/**
	 * Returns the global encryptor.
	 *
	 * @return \Aeros\Lib\Classes\Encryptor
	 */
	function encryptor(): \Aeros\Lib\Classes\Encryptor {
		return app()->encryptor;
	}
}

if (! function_exists('session')) {

	/**
	 * Returns the global session object.
	 *
	 * @return \Aeros\Lib\Classes\Session
	 */
	function session(): \Aeros\Lib\Classes\Session {
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
	 * @param string $logFile Path and filename.
	 * @param bool $createFile Flag to create the log file if it does not exist.
	 * @return boolean
	 */
	function logger(mixed $message, string $logFile, bool $createFile = false): bool {
		return app()->logger->log($message, $logFile, $createFile);
	}
}

if (! function_exists('queue')) {

	/**
	 * Returns the queue instance.
	 *
	 * @return \Aeros\Lib\Classes\Queue
	 */
	function queue(): \Aeros\Lib\Classes\Queue {
		return app()->queue;
	}
}

if (! function_exists('cron')) {

	/**
	 * Returns an instance of Cron. 
	 * This is a wrapper for Scheduler class.
	 *
	 * @return \Aeros\Lib\Classes\Cron
	 */
	function cron(): \Aeros\Lib\Classes\Cron {
		return app()->cron;
	}
}
