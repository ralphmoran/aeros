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
        // Resolve view: absolute path and filename
        $resolved_view = $this->resolveView($view, $subfolder);

        // Check if the view template exists
        if (! file_exists($resolved_view)) {
            http_response_code(400);

            return view('common.errors.codes', [
                'code'    => '404 - Not found',
                'message' => 'File does not exist: ' . $resolved_view
            ]);
        }

        // $flash_vars array comes from any redirect action if $arguments were passed
        if (! empty($_SESSION['flash_vars'])) {
            extract($_SESSION['flash_vars']);

            $_SESSION['flash_vars'] = [];
        }

        // Embeded variables
        if (! empty($vars)) {
            extract($vars);
        }

        include $resolved_view;
    }

    /**
     * Resolves the final path and filename for the requested view.
     *
     * @param string $view
     * @param string $subfolder
     * @param string $extension
     * @return string
     */
    private function resolveView(string $view, string $subfolder = '', string $extension = 'php'): string
    {
        return config('app.views.basepath') 
            . (! empty($subfolder) ? '/' . $subfolder : '') // Subfolder: components
            . '/' . implode('/', explode('.', $view)) . '.' . $extension; // Form base path if there are sub-sections
    }
}
