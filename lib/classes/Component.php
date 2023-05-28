<?php

namespace Classes;

final class Component
{
    /**
     * Renders a component or returns the component body.
     *
     * @param string $component
     * @param array $data
     * @param boolean $return
     * @return void
     */
    public function render(
        string $component, 
        array $data, 
        bool $return = false)
    {
        if ($return) {

            ob_start();

            app()->view->make($component, $data, 'components');

            return ob_get_clean();

        }

        return app()->view->make($component, $data, 'components');
    }
}