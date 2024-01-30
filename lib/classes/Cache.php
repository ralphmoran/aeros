<?php

namespace Aeros\Lib\Classes;

use Aeros\Lib\Traits\ProxyableTrait;

class Cache
{
    use ProxyableTrait;

    /**
     * Sets/Picks cache connection.
     *
     * @param string $connection
     * @return void
     */
    public function setConnection(string $connection = null)
    {
        if (isset($this->objects[$this->index])) {
            return $this->objects[$this->index];
        }

        if (! is_null($connection) && ! in_array($connection, array_keys(config('cache.connections')))) {
            throw new \PDOException(
                sprintf(
                    'ERROR[DB connection] Cache connection "%s" not found.', 
                    $connection
                )
            );
        }

        $cacheConfig = config('cache');

        $this->index = $connection ?? implode(config('cache.default'));

        switch ($cacheConfig['connections'][$this->index]['driver']) {
            case 'memcached':
                $this->objects[$this->index] = new \Memcached();
                $this->objects[$this->index]->setOption(\Memcached::OPT_COMPRESSION, true);
                $this->objects[$this->index]->addServer(env('MEMCACHED_HOST'), env('MEMCACHED_PORT'));

                return $this->objects[$this->index];

                break;
            case 'redis':
                $this->objects[$this->index] = new \Predis\Client(
                    env('REDIS_PROTOCOL') . '://' . env('REDIS_HOST') . ':' . env('REDIS_PORT'), 
                    [
                        'parameters' => [
                            'password' => env('REDIS_PASSWORD'),
                        ]
                    ]
                );

                return $this->objects[$this->index];

                break;
            default:
                throw new \Exception('ERROR[ObjectException] Driver "%s" is not supported.');
        }
    }
}
