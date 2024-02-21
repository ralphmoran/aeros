<?php

namespace Aeros\Src\Classes;

class Cookie
{
    /**
     * Sets a cookie.
     *
     * @param   string  $cookie_name            The name of the cookie.
     * @param   mixed   $value                  The value to be stored in the 
     *                                          cookie.
     * @param   int     $expiration             The expiration time of the cookie 
     *                                          in Unix timestamp.
     * @param   string  $path       [optional]  The path on the server in which 
     *                                          the cookie will be available. 
     *                                          Defaults to '/'.
     * @param   string  $domain     [optional]  The domain that the cookie is 
     *                                          available to. Defaults to an 
     *                                          empty string.
     * @param   bool    $secure     [optional]  Indicates whether the cookie 
     *                                          should only be transmitted over 
     *                                          a secure HTTPS connection. 
     *                                          Defaults to false.
     * @param   bool    $httponly   [optional]  When set to true, the cookie will 
     *                                          be accessible only through the 
     *                                          HTTP protocol. Defaults to false.
     *
     * @return bool Returns true on success or false on failure.
     */
    public function set(
        string $cookie_name, 
        mixed $value, 
        int $expiration, 
        string $path = '/', 
        string $domain = '', 
        bool $secure = false, 
        bool $httponly = false): bool
    {
        $status = setcookie($cookie_name, $value, $expiration, $path, $domain, $secure, $httponly);

        if ($status) {
			$_COOKIE[$cookie_name] = $value;
            $_REQUEST[$cookie_name] = $value;
		}

        // dd($status);

		return $status;
    }

    /**
     * Returns value of cookie by $cookie_name.
     *
     * @param   string  $cookie_name
     * @return  mixed
     */
    public function get(string $cookie_name): mixed
    {
        return isset($_COOKIE[$cookie_name]) ? $_COOKIE[$cookie_name] : null;
    }

    /**
     * Deletes a cookie
     *
     * @param   string  $cookie_name
     * @return  boolean
     */
    public function delete(string $cookie_name): bool
    {
        if (isset($_COOKIE[$cookie_name])) {
            unset($_COOKIE[$cookie_name]);
            unset($_REQUEST[$cookie_name]);

            setcookie($cookie_name, 0, time() - 1);

            return true;
        }

        return false;
    }

    /**
     * Regenerates the whole session cookie.
     *
     * @return  void
     */
    public function clear()
    {
        foreach ($_COOKIE as $key => $value) {
            $this->delete($key);
        }

        foreach ($_REQUEST as $key => $value) {
            $this->delete($key);
        }

        session_regenerate_id(true);
        session_unset();
        session_destroy();
        session_write_close();
        setcookie(session_name(), '', 0, '/');
    }
}
