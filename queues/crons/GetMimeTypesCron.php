<?php

namespace Crons;

use Classes\Cron;

class GetMimeTypesCron extends Cron
{
    /**
     * This method is called when main scheduler cron is invoked.
     *
     * @return void
     */
    public function run()
    {
        app()
            ->scheduler
            ->call(function() {

                if (! cache()->exists('mime.types')) {

                    $mime_types = [];

                    $content = @file_get_contents(
                        "http://svn.apache.org/repos/asf/httpd/httpd/trunk/docs/conf/mime.types",
                        false,
                        stream_context_create([
                            'http' => ['method' => 'head']
                        ])
                    );

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
                    }

                    cache()->set('mime.types', json_encode(array_filter($mime_types)));

                    printf('Mime types updated.');
                }
            })
            ->sunday()
            ->then(function ($output) {
                logger($output, app()->basedir . '/logs/cron.log');
            });
    }
}
