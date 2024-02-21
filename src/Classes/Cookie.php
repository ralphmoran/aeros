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

            $cookieHeader = "$cookie_name=$value; ";

            $cookieHeader .= (! empty($expiration)) ? 'expires=' . gmdate('D, d M Y H:i:s', $expiration) . ' ' . date('T') . '; ' : '';
            $cookieHeader .= (! empty($path)) ? 'path=' . $path . '; ' : '';
            $cookieHeader .= (! empty($domain)) ? 'domain=' . $domain . '; ' : '';
            $cookieHeader .= (! empty($secure)) ? 'Secure; ' : '';
            $cookieHeader .= (! empty($httponly)) ? 'HttpOnly' : '';

            response()->addHeaders(['Set-Cookie' => $cookieHeader]);
		}

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

            setcookie($cookie_name, null, time() - (60 * 60 * 24));

            response()->addHeaders([
                'Set-Cookie' => $cookie_name . '=deleted; expires=Thu, 01 Jan 1970 00:00:00 GMT;'
            ]);

            return true;
        }

        return false;
    }

    /**
     * Deletes all cookies
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
    }
}
