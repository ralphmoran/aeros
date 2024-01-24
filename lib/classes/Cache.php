<?php

namespace Aeros\Lib\Classes;

use Aeros\Lib\Traits\ProxyableTrait;

class Cache
{
    use ProxyableTrait;

    /**
     * Sets/Picks cache driver.
     *
     * @param string $driver
     * @return void
     */
    public function setDriver(string $driver = null)
    {
        if (! $this->index) {
            $this->index = $driver ?: config('cache.default')[0];
        }

        // Singleton
        if (isset($this->objects[$this->index])) {
            return $this->objects[$this->index];
        }

        switch ($this->index) {
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
            case 'mysql':
                $this->objects[$this->index] = db('mysql');

                # TODO: Implement mysql env for cache: queues table, jobs table, etc

                return $this->objects[$this->index];

                break;
            case 'mssql':
                $this->objects[$this->index] = db('mssql');

                # TODO: Implement mssql env for cache: queues table, jobs table, etc

                return $this->objects[$this->index];

                break;
            case 'postgres':
                $this->objects[$this->index] = db('postgres');

                # TODO: Implement postgres env for cache: queues table, jobs table, etc

                return $this->objects[$this->index];

                break;
            case 'sqlite':
                $this->objects[$this->index] = db('sqlite');

                # TODO: Implement sqlite env for cache: queues table, jobs table, etc

                return $this->objects[$this->index];

                break;
            default:
                throw new \Exception('ERROR[ObjectException] Driver "%s" is not supported.');
        }
    }
}
