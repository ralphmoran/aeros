<?php

namespace Classes;

class Redirect
{
    /**
     * Redirects a request to anothe URL.
     *
     * @param string $redirect
     * @param array $arguments
     * @param string $request_method
     * @return void
     */
    public function goto(string $redirect, array $arguments = [], string $request_method = 'GET')
    {
        // Add flash variables to session
        if (! empty($arguments)) {
            $_SESSION['flash_vars'] = $arguments;
        }

        // By default, all redirects are GET requests
        $_SERVER['REQUEST_METHOD'] = $request_method;

        if (array_key_exists("forward", $_GET) && ! empty($_GET["forward"])) {
            $redirect = str_replace(["$", "!", "https://"], ["&", "?", env('HTTP_PROTOCOL')], $_GET["forward"]);
        }

        # TODO: Use htmlspecialchars() to protect from XSS
        header("location: {$redirect}");

        // Redirects are happening fast, so we need to prevent the next event
        die;
    }
}