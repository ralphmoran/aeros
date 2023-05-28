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
        header("Content-type: " . get_myme_types()[$type]);
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
