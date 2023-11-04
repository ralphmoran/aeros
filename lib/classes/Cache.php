<?php

namespace Classes;

class Cache extends \Predis\Client
{
    /**
     * Setting up the client.
     */
    public function __construct() {
        parent::__construct(
            env('REDIS_PROTOCOL') . '://' . env('REDIS_HOST') . ':' . env('REDIS_PORT'), 
            [
                'parameters' => [
                    'password' => env('REDIS_PASSWORD'),
                ]
            ]
        );
    }
}
