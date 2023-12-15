<?php

namespace Controllers;

use Classes\ControllerBase;

class AssetController extends ControllerBase
{
    /**
     * Resolves any requested resource.
     *
     * @param string $path Folder where the resource is in
     * @param string $resource Filename with extension
     * @return string
     */
    public function index(string $path, string $resource): string
    {
        $info = $this->getContent($path . '/' . $resource);

        return response($info['content'], 200, $info['type']);
    }

    /**
     * Validates and returns a file content, if it exists, otherwise it throws an exception.
     *
     * @param string $file
     * @throws \Exception
     * @return void
     */
    private function getContent(string $file)
    {
        $file = env('APP_ROOT_DIR') . '/public/assets/' . $file;

        $content = file_get_contents($file, false, stream_context_create([
            'http' => [
                'method' => 'HEAD',
            ],
        ]));

        if ($content) {
            $parts = pathinfo($file);

            // Adjustments and exceptions
            $type = match ($parts['extension']) {
                'css' => 'text/css',
                default => mime_content_type($file)
            };
            
            return [
                'content' => $content,
                'type' => array_search($type, json_decode(cache()->get('mime.types'), true))
            ];
        }

        throw new \Exception(
            sprintf('ERROR[Controller] File "%s" was not found.', $file)
        );
    }
}
