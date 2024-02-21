<?php

namespace Aeros\Src\Classes;

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
     * @param boolean $dump
     * @return string
     */
    public function render(string $component, array $data, bool $dump = true)
    {
        // Makes the view and returns the content to be embeded
        if (! $dump) {
            ob_start();

            app()->view->make($component, $data, 'components');

            $content = ob_get_contents();

            ob_end_clean();

            return $content;
        }

        return app()->view->make($component, $data, 'components');
    }
}
