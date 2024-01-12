<?php

namespace Aeros\Lib\Classes;

/**
 * This is a wrapper class for Predis package.
 * 
 * @link https://github.com/predis/predis
 */
class Cache extends \Predis\Client
{
    /**
     * Connects to Redis server.
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
