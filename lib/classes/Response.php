<?php

namespace Classes;

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

    /** @var string PDF */
    const PDF  = 'pdf';

    /** @var int $status */
    private $status = 200;

    /** @var array $headers */
    public $headers = [
        "Content-Type:application/json"
    ];

    private $cookies;
    private $data;

    /**
     * Initializes Response
     */
    public function __construct()
    {
        
    }

    /**
     * Outputs a special data type and sets the required headers.
     *
     * @param mixed $data
     * @param string $type
     * @param int $code
     * @return mixed
     */
    public function type(mixed $data, int $code = 200, string $type = Response::JSON)
    {
        // Hack for special files
        $type = match ($type) {
            'map'   => 'json',
            default => $type,
        };

        header_remove();
        header("Content-type: " . json_decode(cache()->get('mime.types'), true)[$type]);
        http_response_code($code);

        if ($type == 'json') {

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
}
