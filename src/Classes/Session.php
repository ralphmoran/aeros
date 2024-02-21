<?php

namespace Aeros\Src\Classes;

/**
 * Class Session
 * 
 * @package Aeros\Src\Classes
 */
class Session
{
    /**
     * @var     bool    The state of the session.
     */
    private $state = false;

    /**
     * Starts the session.
     *
     * @return  bool    Returns true on success or false on failure.
     */
    public function start()
    {
        if (! $this->state) {

            $cookie = config('session.cookie');

            session_set_cookie_params(
                $cookie['lifetime'],
                $cookie['path'],
                $cookie['domain'],
                $cookie['secure'],
                $cookie['httponly']
            );

            if (! empty($samesite = $cookie['samesite'])) {
                ini_set('session.cookie_samesite', $samesite);
            }

            session_name(strtolower($cookie['cookie_name']));

            $this->state = session_start(config('session.options'));
        }

        return $this->state;
    }

    /**
     * Renovates all data registered to a session.
     *
     * @return  bool    Returns true on success or false on failure.
     */
    public function renovate()
    {
        if ($this->state) {
            session_unset();
            $this->state = ! session_destroy();
            unset($_SESSION);

            $this->start();

            session_regenerate_id(true);

            return ! $this->state;
        }

        return false;
    }

    /**
     * Magic method to set session variables.
     *
     * @param   string  $name   The session variable name.
     * @param   mixed   $value  The session variable value.
     */
    public function __set($name, $value)
    {
        $_SESSION[$name] = $value;
    }

    /**
     * Magic method to get session variables.
     *
     * @param   string  $name   The session variable name.
     * @return  mixed|null  The session variable value if set, otherwise null.
     */
    public function __get($name)
    {
        if (isset($_SESSION[$name])) {
            return $_SESSION[$name];
        }

        return null;
    }

    /**
     * Magic method to check if a session variable is set.
     *
     * @param   string  $name   The session variable name.
     * @return  bool    Returns true if the session variable is set, otherwise false.
     */
    public function __isset($name)
    {
        return isset($_SESSION[$name]);
    }

    /**
     * Magic method to unset a session variable.
     *
     * @param   string  $name   The session variable name.
     * @return  bool    Returns true on success.
     */
    public function __unset($name)
    {
        unset($_SESSION[$name]);

        return true;
    }

    /**
     * Closes the session and no more data is stored.
     *
     * @access  private
     */
    public function close()
    {
        session_write_close();
    }
}
