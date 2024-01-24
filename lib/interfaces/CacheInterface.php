<?php

namespace Aeros\Lib\Interfaces;

interface CacheInterface
{
    public function setDriver(string $driver): CacheInterface;
    public function get(string $key);
    public function set(string $key, mixed $value, int $expiration = 0);
    public function del(string $key);
    public function delete(string $key);
    public function rpush();
    public function lpush();
    public function lpop();
    public function rpop();
    public function keys();
    public function exists();
    public function flush();
}
