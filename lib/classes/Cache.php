<?php

namespace Classes;

use Predis\Client as PredisClient;

class Cache
{
    /** @var PredisClient */
    private $client = null;

    /**
     * Setting up the client.
     */
    public function __construct() {
        $this->client = new PredisClient(
            'tcp://' . env('REDIS_HOST') . ':' . env('REDIS_PORT'), 
            [
                'parameters' => [
                    'password' => env('REDIS_PASSWORD'),
                ]
            ]
        );
    }

    /**
     * Returns Predis client object.
     *
     * @return PredisClient
     */
    public function get_client()
    {
        return $this->client;
    }
}
