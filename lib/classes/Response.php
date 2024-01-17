<?php

namespace Aeros\Lib\Classes;

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
        // Validates if mimetypes exist
        // if (! cache()->exists('mime.types')) {
        //     queue()->push([\Aeros\Queues\Jobs\GetMimeTypesJob::class]);
        // }
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
        // On terminal
        if (strpos(PHP_SAPI, 'cli') !== false) {
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
            // return json_encode($data);
        }

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
