<?php

namespace Classes;

class Cache extends \Predis\Client
{
    /** @var PredisClient */
    private $client = null;

    /**
     * Setting up the client.
     */
    public function __construct() {
        parent::__construct(
            'tcp://' . env('REDIS_HOST') . ':' . env('REDIS_PORT'), 
            [
                'parameters' => [
                    'password' => env('REDIS_PASSWORD'),
                ]
            ]
        );
    }
}
