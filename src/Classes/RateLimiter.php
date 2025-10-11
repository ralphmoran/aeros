<?php

namespace Aeros\Src\Classes;

/**
 * Rate limiter to prevent abuse and DoS attacks.
 */
class RateLimiter
{
    /** @var string Cache key prefix */
    private const PREFIX = 'rate_limit:';

    /**
     * Check if request should be rate limited.
     *
     * @param   string  $key Unique identifier (IP, user ID, API key, etc.)
     * @param   int     $maxAttempts Maximum attempts allowed
     * @param   int     $decayMinutes Time window in minutes
     * @return  bool    True if rate limit exceeded
     */
    public function tooManyAttempts(string $key, int $maxAttempts, int $decayMinutes): bool
    {
        $attempts = cache()->get(self::PREFIX . $key) ?? 0;

        return $attempts >= $maxAttempts;
    }

    /**
     * Increment the counter for a given key.
     *
     * @param   string  $key Unique identifier
     * @param   int     $decayMinutes Time window in minutes
     * @return  int     Current attempt count
     */
    public function hit(string $key, int $decayMinutes = 1): int
    {
        $cacheKey = self::PREFIX . $key;
        $attempts = cache()->get($cacheKey) ?? 0;
        $attempts++;

        // Set with expiration
        cache()->set($cacheKey, $attempts, $decayMinutes * 60);

        return $attempts;
    }

    /**
     * Get remaining attempts.
     *
     * @param   string  $key Unique identifier
     * @param   int     $maxAttempts Maximum attempts allowed
     * @return  int     Remaining attempts
     */
    public function remaining(string $key, int $maxAttempts): int
    {
        $attempts = cache()->get(self::PREFIX . $key) ?? 0;

        return max(0, $maxAttempts - $attempts);
    }

    /**
     * Get number of attempts.
     *
     * @param   string  $key Unique identifier
     * @return  int     Current attempts
     */
    public function attempts(string $key): int
    {
        return cache()->get(self::PREFIX . $key) ?? 0;
    }

    /**
     * Reset the counter for a given key.
     *
     * @param   string  $key Unique identifier
     * @return  bool
     */
    public function clear(string $key): bool
    {
        return cache()->delete(self::PREFIX . $key);
    }

    /**
     * Get seconds until rate limit resets.
     *
     * @param   string  $key Unique identifier
     * @return  int     Seconds until reset (0 if not limited)
     */
    public function availableIn(string $key): int
    {
        return max(
            0,
            cache()->ttl(self::PREFIX . $key) // TTL
        );
    }

    /**
     * Throttle a request - throws exception if rate limited.
     *
     * @param   string  $key Unique identifier
     * @param   int     $maxAttempts Maximum attempts
     * @param   int     $decayMinutes Time window
     * @param   callable|null $callback Optional callback when limit exceeded
     * @throws  \Exception When rate limit exceeded
     */
    public function throttle(string $key, int $maxAttempts, int $decayMinutes, ?callable $callback = null): void
    {
        if ($this->tooManyAttempts($key, $maxAttempts, $decayMinutes)) {

            if ($callback) {
                $callback($this);
            }

            $retryAfter = $this->availableIn($key);

            throw new \Exception(
                "Too many attempts. Please try again in {$retryAfter} seconds.",
                429
            );
        }

        $this->hit($key, $decayMinutes);
    }

    /**
     * Get rate limit key based on IP address.
     *
     * @param   string  $prefix Optional prefix
     * @return  string
     */
    public static function keyByIP(string $prefix = ''): string
    {
        return $prefix . $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }

    /**
     * Get rate limit key based on user ID.
     *
     * @param   int|string  $userId
     * @param   string      $prefix Optional prefix
     * @return  string
     */
    public static function keyByUser(int|string $userId, string $prefix = ''): string
    {
        return $prefix . 'user:' . $userId;
    }

    /**
     * Get rate limit key based on route.
     *
     * @param   string  $route
     * @param   string  $identifier IP or user ID
     * @return  string
     */
    public static function keyByRoute(string $route, string $identifier): string
    {
        return $route . ':' . $identifier;
    }
}
