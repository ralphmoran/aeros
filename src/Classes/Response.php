<?php

namespace Aeros\Src\Classes;

use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

final class Response
{
    /** @var string css */
    const CSS  = 'css';

    /** @var string GIF */
    const GIF  = 'gif';

    /** @var string PNG */
    const PNG  = 'png';

    /** @var string JPG */
    const JPG  = 'jpg';

    /** @var string SVG */
    const SVG  = 'svg';

    /** @var string ICO */
    const ICO  = 'ico';

    /** @var string JS */
    const JS = 'js';

    /** @var string CSV */
    const CSV  = 'csv';

    /** @var string XML */
    const XML  = 'xml';

    /** @var string JSON */
    const JSON = 'json';

    /** @var string TXT */
    const TXT  = 'txt';

    /** @var string HTML */
    const HTML  = 'html';

    /** @var string PDF */
    const PDF  = 'pdf';

    /** @var array */
    private $headers = [];

    /** @var int */
    private $code = 200;

    /**
     * Outputs a special data type and sets the required headers.
     *
     * @param mixed $data
     * @param string $type
     * @param int $code
     * @return mixed
     */
    public function type(mixed $data, int $code = null, string $type = Response::HTML)
    {
        $this->withResponseCode(! is_null($code) ? $code : $this->code);

        // On terminal
        if (strpos(php_sapi_name(), 'cli') !== false) {
            $output = new ConsoleOutput();

            $output->getFormatter()->setStyle(
                'success', 
                new OutputFormatterStyle('green', 'black', ['bold'])
            );

            // Dump values to the console with formatting
            foreach ($data as $key => $value) {
                $output->writeln(sprintf('<success>%s: </success>%s', $key, var_export($value, true)));
            }

            return;
        }

        // Hack for special files
        $type = match ($type) {
            'map'   => 'json',
            default => $type,
        };

        // Setting up response headers
        $this->sendHeaders();

        // JSON format
        if ($type == 'json') {

            $this
                ->removeHeaders(['Content-type'])
                ->addHeaders(['Content-type' => cache('memcached')->get('mime.types')[$type]])
                ->sendHeaders();

            if (! is_array($data)) {

                // It is string|float|int|boolean|etc
                if (! is_object(json_decode($data))) {
                    return json_encode([$data]);
                }

                // It is already a JSON
                return $data;

            }

            if (is_array($data)) {
                return json_encode($data);
            }
        }

        return $data;
    }

    /**
     * Sends the headers for the response.
     *
     * @return void
     */
    public function sendHeaders()
    {
        header_remove();
        $this->assignHeadersToResponse();
        http_response_code($this->code);
    }

    /**
     * Assigns headers to the response.
     * 
     * Iterates over the headers obtained from the `getHeaders` method
     * and sets each one using the PHP `header` function. This method does not
     * directly accept any headers to assign; it uses the headers already set in the object.
     *
     * @param   array   $headers An optional array of headers to be assigned to the response.
     *                          This parameter is not used in the method as described but is included
     *                          in the method signature for example purposes. Depending on your implementation,
     *                          you might want to remove it or update the documentation accordingly.
     * @return self
     */
    public function assignHeadersToResponse(array $headers = []): self
    {
        foreach ($this->getHeaders() as $key => $value) {

            if (is_int($key)) {
                header($value);
                continue;
            }

            header($key . ': ' . $value);
        }

        return $this;
    }

    /**
     * Retrieves all headers that should be sent with the response.
     * 
     * This method combines headers explicitly set on the object with default headers
     * configured in the application's session configuration. It's useful for getting a
     * complete view of all headers that will be sent back in the response.
     *
     * @return  array   An associative array of headers where each key is a header 
     *                  name and each value is the header value.
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Adds headers to be sent with the response.
     * 
     * This method allows adding or overwriting headers in the object. Headers passed to this method
     * are merged with existing headers, with the new headers either adding to or replacing the existing set.
     *
     * @param   array $headers An associative array of headers to add, where each 
     *                          key is a header name and each value is the header value.
     * @return  self
     */
    public function addHeaders(array $headers = []): self 
    {
        foreach ($headers as $key => $value) {

            if (is_int($key)) {
                $this->headers[] = $value;
                continue;
            }

            $this->headers[$key] = $value;
        }

        return $this;
    }

    /**
     * It removes afterward header addition.
     * 
     * Note: This method will not remove the default headers. See config('session.headers.default')
     *
     * @param   array   $headers
     * @return  self
     */
    public function removeHeaders(array $headers = []): self 
    {
        foreach ($headers as $header) {
            if (isset($this->headers[$header])) {
                unset($this->headers[$header]);
            }
        }

        return $this;
    }

    /**
     * Sets the response code.
     *
     * @param   integer     $code
     * @return  void
     */
    public function withResponseCode(int $code)
    {
        $this->code = $code;
    }
}
