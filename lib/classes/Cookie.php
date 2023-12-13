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
    public function set(string $key, $value, int|array $params): bool
    {
        return setCookieWith($key, $value, $params);
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
}
