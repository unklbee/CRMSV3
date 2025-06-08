<?php

namespace Config;

use CodeIgniter\Cache\CacheInterface;
use CodeIgniter\Cache\Handlers\DummyHandler;
use CodeIgniter\Cache\Handlers\FileHandler;
use CodeIgniter\Cache\Handlers\MemcachedHandler;
use CodeIgniter\Cache\Handlers\PredisHandler;
use CodeIgniter\Cache\Handlers\RedisHandler;
use CodeIgniter\Cache\Handlers\WincacheHandler;
use CodeIgniter\Config\BaseConfig;

/**
 * Cache Configuration yang dioptimasi dan diperbaiki
 */
class Cache extends BaseConfig
{
    /**
     * Primary handler - akan di-set di constructor
     */
    public string $handler = 'file';

    /**
     * Backup handler
     */
    public string $backupHandler = 'dummy';

    /**
     * Key prefix untuk menghindari collision
     */
    public string $prefix = '';

    /**
     * Default TTL yang dioptimasi (1 jam)
     */
    public int $ttl = 3600;

    /**
     * Reserved characters
     */
    public string $reservedCharacters = '{}()/\@:';

    /**
     * File cache configuration
     */
    public array $file = [
        'storePath' => '',
        'mode'      => 0640,
    ];

    /**
     * Memcached configuration
     */
    public array $memcached = [
        'host'   => '127.0.0.1',
        'port'   => 11211,
        'weight' => 1,
        'raw'    => false,
    ];

    /**
     * Redis configuration
     */
    public array $redis = [
        'host'     => '127.0.0.1',
        'password' => null,
        'port'     => 6379,
        'timeout'  => 0,
        'database' => 0,
    ];

    /**
     * Available cache handlers
     */
    public array $validHandlers = [
        'dummy'     => DummyHandler::class,
        'file'      => FileHandler::class,
        'memcached' => MemcachedHandler::class,
        'predis'    => PredisHandler::class,
        'redis'     => RedisHandler::class,
        'wincache'  => WincacheHandler::class,
    ];

    /**
     * Web page caching configuration
     */
    public $cacheQueryString = false;

    /**
     * Constructor untuk set dynamic values
     */
    public function __construct()
    {
        parent::__construct();

        // Set handler berdasarkan environment dan extension yang tersedia
        if (ENVIRONMENT === 'production') {
            if (extension_loaded('redis')) {
                $this->handler = 'redis';
                $this->redis['database'] = 1; // Database terpisah dari session
            } elseif (extension_loaded('memcached')) {
                $this->handler = 'memcached';
            } else {
                $this->handler = 'file';
            }
        } else {
            $this->handler = 'file';
        }

        // Set backup handler
        $this->backupHandler = 'file';

        // Set prefix yang unik
        $this->prefix = 'app_cache_' . substr(md5(APPPATH), 0, 8) . '_';

        // Set file storage path
        $this->file['storePath'] = WRITEPATH . 'cache/';
    }

    /**
     * Get cache group configurations
     */
    public function getCacheGroups(): array
    {
        return [
            'user_data' => [
                'handler' => $this->handler,
                'ttl' => 1800, // 30 menit untuk user data
                'prefix' => $this->prefix . 'user_'
            ],
            'session_data' => [
                'handler' => $this->handler,
                'ttl' => 7200, // 2 jam untuk session
                'prefix' => $this->prefix . 'sess_'
            ],
            'login_attempts' => [
                'handler' => $this->handler,
                'ttl' => 900, // 15 menit untuk rate limiting
                'prefix' => $this->prefix . 'login_'
            ],
            'view_cache' => [
                'handler' => 'file',
                'ttl' => 86400, // 24 jam untuk view cache
                'prefix' => $this->prefix . 'view_'
            ],
            'api_cache' => [
                'handler' => $this->handler,
                'ttl' => 300, // 5 menit untuk API cache
                'prefix' => $this->prefix . 'api_'
            ]
        ];
    }

    /**
     * Get optimized Redis configuration
     */
    public function getOptimizedRedisConfig(): array
    {
        return array_merge($this->redis, [
            'serializer' => 'php', // Better performance than default
            'compression' => 'lz4', // Enable compression if available
            'persistent' => true,   // Persistent connections
            'retry_interval' => 100,
            'read_timeout' => 2,
        ]);
    }
}