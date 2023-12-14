<?php

namespace Classes;

class Cookie
{
    /**
	 * Sets/mames properly a cookie with value and params.
	 *
	 * @param string $key
	 * @param mixed $value
	 * @param int|array $params
	 * @return boolean
	 */
    public function set(string $key, 
        mixed $value, 
        int|array $params, 
        string $path = '/', 
        string $domain = '', 
        bool $secure = false, 
        bool $httponly = false): bool
    {
        return setCookieWith($key, $value, $params, $path, $domain, $secure, $httponly);
    }

    /**
     * Returns value of cookie by $key.
     *
     * @param string $key
     * @return mixed
     */
    public function get(string $key): mixed
    {
        if (array_key_exists($key, $_COOKIE)) {
            return $_COOKIE[$key];
        }

        return null;
    }

    /**
     * Regenerates the whole session cookie.
     *
     * @return void
     */
    public function clear(string $cookie = null)
    {
        if (is_null($cookie)) {
            session_unset();
            session_destroy();
            session_write_close();
            setcookie(session_name(), '', 0, '/');
            session_regenerate_id(true);
        }
    }
}
