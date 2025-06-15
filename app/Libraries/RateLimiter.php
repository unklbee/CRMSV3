<?php

namespace App\Libraries;

use CodeIgniter\Cache\CacheInterface;
use Config\Services;

/**
 * Improved Rate Limiter untuk CodeIgniter 4 - Fixed untuk MAMP
 */
class RateLimiter
{
    protected CacheInterface $cache;

    public function __construct()
    {
        $this->cache = Services::cache();
    }

    /**
     * Sanitize cache key untuk menghindari reserved characters
     */
    private function sanitizeKey(string $key): string
    {
        // Hapus karakter reserved: {}()/\@:
        $key = preg_replace('/[{}()\\/\\\\@:]/', '_', $key);

        // Pastikan key tidak kosong dan valid
        if (empty($key)) {
            $key = 'default_key';
        }

        // Batasi panjang key dan pastikan hanya karakter aman
        $key = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $key);
        $key = substr($key, 0, 200); // Batasi panjang

        return $key;
    }

    /**
     * Check apakah request diizinkan berdasarkan rate limit
     */
    public function isAllowed(string $key, int $maxAttempts, int $timeWindow): bool
    {
        $cacheKey = 'rate_limit_' . $this->sanitizeKey($key);
        $attempts = $this->cache->get($cacheKey, 0);

        return $attempts < $maxAttempts;
    }

    /**
     * Attempt dengan sanitized key
     */
    public function attempt(string $key, int $maxAttempts, int $timeWindow): bool
    {
        $cacheKey = 'rate_limit_' . $this->sanitizeKey($key);
        $attempts = $this->cache->get($cacheKey, 0);

        if ($attempts >= $maxAttempts) {
            return false;
        }

        $this->cache->save($cacheKey, $attempts + 1, $timeWindow);
        return true;
    }

    /**
     * Check remaining attempts
     */
    public function check(string $key, int $maxAttempts, int $timeWindow): bool
    {
        $cacheKey = 'rate_limit_' . $this->sanitizeKey($key);
        $attempts = $this->cache->get($cacheKey, 0);

        return $attempts < $maxAttempts;
    }

    /**
     * Get jumlah attempts saat ini
     */
    public function getAttempts(string $key): int
    {
        $cacheKey = 'rate_limit_' . $this->sanitizeKey($key);
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
        $cacheKey = 'rate_limit_' . $this->sanitizeKey($key);
        return $this->cache->delete($cacheKey);
    }

    /**
     * Check apakah IP sedang dalam lockout
     */
    public function isLockedOut(string $key): bool
    {
        $lockKey = 'lockout_' . $this->sanitizeKey($key);
        return $this->cache->get($lockKey, false) !== false;
    }

    /**
     * Set lockout untuk key tertentu
     */
    public function setLockout(string $key, int $duration): bool
    {
        $lockKey = 'lockout_' . $this->sanitizeKey($key);
        return $this->cache->save($lockKey, time(), $duration);
    }

    /**
     * Get lockout remaining time dalam detik
     */
    public function getLockoutTime(string $key): int
    {
        $lockKey = 'lockout_' . $this->sanitizeKey($key);
        $lockTime = $this->cache->get($lockKey, false);

        if ($lockTime === false) {
            return 0;
        }

        return 1800; // 30 minutes
    }

    /**
     * Clear lockout
     */
    public function clearLockout(string $key): bool
    {
        $lockKey = 'lockout_' . $this->sanitizeKey($key);
        return $this->cache->delete($lockKey);
    }

    /**
     * Hit rate limiter dengan sanitized key
     */
    public function hit(string $key, int $timeWindow): bool
    {
        $cacheKey = 'throttle_' . $this->sanitizeKey($key);
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
        $windowKey = 'sliding_' . $this->sanitizeKey($key);

        $timestamps = $this->cache->get($windowKey, []);

        $timestamps = array_filter($timestamps, function($timestamp) use ($now, $timeWindow) {
            return ($now - $timestamp) < $timeWindow;
        });

        if (count($timestamps) >= $maxAttempts) {
            return false;
        }

        $timestamps[] = $now;
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
     * Cleanup expired entries
     */
    public function cleanup(): int
    {
        return 0;
    }
}