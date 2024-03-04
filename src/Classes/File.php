<?php

namespace Aeros\Src\Classes;

class File
{
    /**
     * Creates a file with the given content. This method validates if the file 
     * exists, otherwise it's created. When it exists, the content is appended.
     *
     * @param string $filename
     * @param mixed $content
     * @param int $append
     * @return integer|false
     */
    public static function create(string $filename, mixed $content = '', int $append = FILE_APPEND): int|false
    {
        $flags = FILE_USE_INCLUDE_PATH | $append;

        return file_put_contents(
            $filename, 
            $content, 
            $flags
        );
    }

    /**
     * Gets the content of the file or a URL.
     *
     * @param string $filename
     * @return string|false
     */
    public static function getContent(string $filename): string|false
    {
        return file_get_contents(
            $filename,
            true
        );
    }

    /**
     * Creates a new file from a template with the given tokens.
     *
     * @param string $filename
     * @param string $template
     * @param array $tokens
     * @return integer|false
     */
    public static function createFromTemplate(string $filename, string $template, array $tokens): int|false
    {
        if (! file_exists($template)) {
            throw new \Exception("{$template} does not exist");
        }

        $templateContent = static::getContent($template);

        foreach ($tokens as $tag => $value) {
            $templateContent = str_replace('{{' . $tag . '}}', $value, $templateContent);
        }

        return static::create($filename, $templateContent, 0);
    }
}
