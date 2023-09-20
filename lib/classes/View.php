<?php

namespace Classes;

final class View
{
    /**
     * Makes/Renderes a view.
     *
     * @param string $view
     * @param array $vars
     * @param string $subfolder (optional) Commonly used for components
     */
    public function make(
        string $view, 
        array $vars = [], 
        string $subfolder = ''
    ) {
        $base_path        = __DIR__ . '/../../views';
        $parsed_view_path = '/' . implode('/', explode('.', $view)) . '.php';
        $view_path        = $base_path . (! empty($subfolder) ? '/' . $subfolder : '') . $parsed_view_path;

        // Check if the view template exists
        if (! file_exists($view_path)) {

            http_response_code(400);

            return view('common.errors.codes', [
                                                'code'    => '404 - Not found',
                                                'message' => 'File does not exist: ' . $view_path
                                            ]);
        }

        clearstatcache();

        // $flash_vars array comes from any redirect action if $arguments were passed
        if (! empty($_SESSION['flash_vars'])) {
            extract($_SESSION['flash_vars']);

            $_SESSION['flash_vars'] = [];
        }

        // Embeded variables
        if (! empty($vars)) {
            extract($vars);
        }

        include $view_path;
    }
}
