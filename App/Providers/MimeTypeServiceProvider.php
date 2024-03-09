<?php

namespace App\Providers;

use Aeros\Src\Classes\ServiceProvider;

class MimeTypeServiceProvider extends ServiceProvider
{
    public function register(): void
    {

    }

    /**
     * Gets and sets the MIME types for the application.
     *
     * @return void
     */
    public function boot(): void
    {
        $mime_types = [];

        $content = file_get_contents("http://svn.apache.org/repos/asf/httpd/httpd/trunk/docs/conf/mime.types");

        if ($content !== false) {

            foreach (explode("\n", $content) as $line) {
                if (strpos($line = trim($line), '#') === 0) {
                    continue;
                }

                $parts = preg_split('/\s+/', $line);

                $value = array_shift($parts);
                $key = array_shift($parts);

                $mime_types[$key] = $value;
            }

            cache('memcached')->set('mime.types', array_filter($mime_types));
        }
    }
}
