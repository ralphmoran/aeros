<?php

namespace Aeros\Lib\Classes;

/**
 * Core class Component renders and resuses small portions of HTML that
 * could be embedded or returned for after rendering.
 */
final class Component
{
    /**
     * Renders and embeds a component or returns the component body for after rendering.
     *
     * @param string $component
     * @param array $data
     * @param boolean $return
     * @return string
     */
    public function render(string $component, array $data, bool $return = false): string
    {
        // Makes the view and returns the content to be embeded
        if ($return) {
            ob_start();
            app()->view->make($component, $data, 'components');

            return ob_get_clean();
        }

        return app()->view->make($component, $data, 'components');
    }
}
