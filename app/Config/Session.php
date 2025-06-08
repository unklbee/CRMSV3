<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Session\Handlers\BaseHandler;
use CodeIgniter\Session\Handlers\RedisHandler;
use CodeIgniter\Session\Handlers\DatabaseHandler;
use CodeIgniter\Session\Handlers\FileHandler;

/**
 * Session Configuration yang dioptimasi dan diperbaiki
 */
class Session extends BaseConfig
{
    /**
     * Session driver
     *
     * @var class-string<BaseHandler>
     */
    public string $driver = FileHandler::class;

    /**
     * Session cookie name - akan di-set di constructor
     */
    public string $cookieName = 'ci_session';

    /**
     * Session expiration yang lebih aman (2 jam)
     */
    public int $expiration = 7200;

    /**
     * Save path - akan di-set di constructor
     */
    public string $savePath = '';

    /**
     * Match IP untuk keamanan tambahan
     */
    public bool $matchIP = false;

    /**
     * Regenerate session ID lebih sering (5 menit)
     */
    public int $timeToUpdate = 300;

    /**
     * Destroy old session data
     */
    public bool $regenerateDestroy = true;

    /**
     * Database group untuk session (jika menggunakan DatabaseHandler)
     */
    public ?string $DBGroup = 'default';

    /**
     * Redis configuration untuk session
     */
    public int $lockRetryInterval = 100_000;
    public int $lockMaxRetries = 300;

    /**
     * Constructor untuk set dynamic values
     */
    public function __construct()
    {
        parent::__construct();

        // Set driver berdasarkan environment
        if (ENVIRONMENT === 'production') {
            // Gunakan Redis di production jika tersedia
            if (extension_loaded('redis')) {
                $this->driver = RedisHandler::class;
                $this->savePath = 'tcp://localhost:6379';
            } else {
                $this->driver = DatabaseHandler::class;
                $this->savePath = 'ci_sessions';
            }
        } else {
            // Gunakan file handler di development
            $this->driver = FileHandler::class;
            $this->savePath = WRITEPATH . 'session';
        }

        // Set unique cookie name
        $appHash = substr(md5(APPPATH), 0, 8);
        $this->cookieName = 'ci_session_' . $appHash;

        // Set match IP berdasarkan environment
        $this->matchIP = (ENVIRONMENT === 'production');
    }

    /**
     * Get session configuration array
     */
    public function getSessionConfig(): array
    {
        return [
            'cookie_lifetime' => $this->expiration,
            'cookie_path' => '/',
            'cookie_domain' => '',
            'cookie_secure' => (ENVIRONMENT === 'production'),
            'cookie_httponly' => true,
            'cookie_samesite' => 'Lax',
            'use_strict_mode' => true,
            'use_cookies' => true,
            'use_only_cookies' => true,
            'cache_limiter' => 'nocache',
            'cache_expire' => 180,
            'lazy_write' => true,
            'name' => $this->cookieName,
        ];
    }

    /**
     * Get Redis configuration
     */
    public function getRedisConfig(): array
    {
        return [
            'host' => '127.0.0.1',
            'port' => 6379,
            'password' => null,
            'database' => 0,
            'timeout' => 0,
        ];
    }
}