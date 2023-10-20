<?php

namespace Providers;

use Classes\ServiceProvider;

class MimeTypesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register the MIME types on Cache
        if (! cache()->exists('mime.types')) {

            $mime_types = [];

            $content = @file_get_contents(env('APACHE_MIME_TYPES_URL'));

            if ($content !== false) {

                foreach(explode("\n", $content) as $line) {
                    if (strpos($line = trim($line), '#') === 0) {
                        continue;
                    }

                    $parts = preg_split('/\s+/', $line);

                    $value = array_shift($parts);
                    $key = array_shift($parts);

                    $mime_types[$key] = $value;
                }
            }

            cache()->set('mime.types', json_encode(array_filter($mime_types)));
        }
    }

    public function boot(): void
    {
        
    }
}
