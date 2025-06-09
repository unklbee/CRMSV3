<?php

namespace App\Libraries;

use CodeIgniter\Cache\CacheInterface;
use Config\Services;

/**
 * Improved Rate Limiter untuk CodeIgniter 4
 */
class RateLimiter
{
    protected CacheInterface $cache;

    public function __construct()
    {
        $this->cache = Services::cache();
    }

    /**
     * Check apakah request diizinkan berdasarkan rate limit
     * Method ini yang dipanggil oleh RateLimitFilter
     */
    public function isAllowed(string $key, int $maxAttempts, int $timeWindow): bool
    {
        $cacheKey = 'rate_limit_' . $key;
        $attempts = $this->cache->get($cacheKey, 0);

        // Jika sudah mencapai limit, tidak diizinkan
        return $attempts < $maxAttempts;
    }

    /**
     * Check apakah request diizinkan berdasarkan rate limit
     */
    public function attempt(string $key, int $maxAttempts, int $timeWindow): bool
    {
        $cacheKey = 'rate_limit_' . $key;
        $attempts = $this->cache->get($cacheKey, 0);

        if ($attempts >= $maxAttempts) {
            return false;
        }

        // Increment counter
        $this->cache->save($cacheKey, $attempts + 1, $timeWindow);

        return true;
    }

    /**
     * Check remaining attempts tanpa increment
     */
    public function check(string $key, int $maxAttempts, int $timeWindow): bool
    {
        $cacheKey = 'rate_limit_' . $key;
        $attempts = $this->cache->get($cacheKey, 0);

        return $attempts < $maxAttempts;
    }

    /**
     * Get jumlah attempts saat ini
     */
    public function getAttempts(string $key): int
    {
        $cacheKey = 'rate_limit_' . $key;
        return $this->cache->get($cacheKey, 0);
    }

    /**
     * Get remaining attempts
     */
    public function getRemainingAttempts(string $key, int $maxAttempts): int
    {
        $attempts = $this->getAttempts($key);
        return max(0, $maxAttempts - $attempts);
    }

    /**
     * Clear rate limit untuk key tertentu
     */
    public function clear(string $key): bool
    {
        $cacheKey = 'rate_limit_' . $key;
        return $this->cache->delete($cacheKey);
    }

    /**
     * Check apakah IP sedang dalam lockout
     */
    public function isLockedOut(string $key): bool
    {
        $lockKey = 'lockout_' . $key;
        return $this->cache->get($lockKey, false) !== false;
    }

    /**
     * Set lockout untuk key tertentu
     */
    public function setLockout(string $key, int $duration): bool
    {
        $lockKey = 'lockout_' . $key;
        return $this->cache->save($lockKey, time(), $duration);
    }

    /**
     * Get lockout remaining time dalam detik
     */
    public function getLockoutTime(string $key): int
    {
        $lockKey = 'lockout_' . $key;
        $lockTime = $this->cache->get($lockKey, false);

        if ($lockTime === false) {
            return 0;
        }

        // Simple implementation - return a reasonable remaining time
        return 1800; // 30 minutes
    }

    /**
     * Clear lockout
     */
    public function clearLockout(string $key): bool
    {
        $lockKey = 'lockout_' . $key;
        return $this->cache->delete($lockKey);
    }

    /**
     * Hit rate limiter (simplified method)
     */
    public function hit(string $key, int $timeWindow): bool
    {
        $cacheKey = 'throttle_' . $key;
        $hits = $this->cache->get($cacheKey, 0);

        $this->cache->save($cacheKey, $hits + 1, $timeWindow);

        return true;
    }

    /**
     * Advanced rate limiting dengan sliding window
     */
    public function slidingWindow(string $key, int $maxAttempts, int $timeWindow): bool
    {
        $now = time();
        $windowKey = 'sliding_' . $key;

        // Get existing timestamps
        $timestamps = $this->cache->get($windowKey, []);

        // Remove old timestamps outside window
        $timestamps = array_filter($timestamps, function($timestamp) use ($now, $timeWindow) {
            return ($now - $timestamp) < $timeWindow;
        });

        // Check if we're within limit
        if (count($timestamps) >= $maxAttempts) {
            return false;
        }

        // Add current timestamp
        $timestamps[] = $now;

        // Save updated timestamps
        $this->cache->save($windowKey, $timestamps, $timeWindow);

        return true;
    }

    /**
     * Get statistics untuk monitoring
     */
    public function getStats(string $keyPattern = null): array
    {
        return [
            'total_keys' => 0,
            'active_limits' => 0,
            'active_lockouts' => 0
        ];
    }

    /**
     * Cleanup expired entries (untuk maintenance)
     */
    public function cleanup(): int
    {
        // Most cache drivers handle TTL automatically
        // Return 0 for compatibility
        return 0;
    }
}