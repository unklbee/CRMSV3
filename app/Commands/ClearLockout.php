<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Services;

class ClearLockout extends BaseCommand
{
    protected $group       = 'Auth';
    protected $name        = 'auth:clear-lockout';
    protected $description = 'Clear rate limiting lockout for specific IP';
    protected $usage       = 'auth:clear-lockout [ip_address]';
    protected $arguments   = [
        'ip_address' => 'IP address to clear lockout (optional, clears all if not specified)'
    ];

    public function run(array $params)
    {
        $cache = Services::cache();
        $ipAddress = $params[0] ?? '127.0.0.1';

        CLI::write("Clearing lockout for IP: {$ipAddress}", 'yellow');

        // All possible cache keys based on RateLimiter and AuthController
        $allPossibleKeys = [
            // Rate limiting keys
            'rate_limit_login_' . $ipAddress,
            'lockout_login_' . $ipAddress,
            'throttle_login_' . $ipAddress,
            'rate_limit_register_' . $ipAddress,
            'lockout_register_' . $ipAddress,
            'throttle_register_' . $ipAddress,

            // Additional possible variations
            'login_' . $ipAddress,
            'register_' . $ipAddress,
        ];

        $cleared = 0;
        foreach ($allPossibleKeys as $key) {
            if ($cache->delete($key)) {
                CLI::write("Cleared: {$key}", 'green');
                $cleared++;
            }
        }

        if ($cleared === 0) {
            CLI::write('No cache keys found to clear.', 'yellow');

            // Force clear entire cache as last resort
            CLI::write('Clearing entire cache...', 'red');
            $cache->clean();
            CLI::write('Entire cache cleared!', 'green');
        } else {
            CLI::write("Successfully cleared {$cleared} cache keys", 'green');
        }

        // Also try to clear file cache manually if using file handler
        $this->clearFileCache();
    }

    private function clearFileCache()
    {
        $cachePath = WRITEPATH . 'cache/';

        if (is_dir($cachePath)) {
            CLI::write('Clearing file cache...', 'yellow');

            $files = glob($cachePath . '*');
            $deletedFiles = 0;

            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                    $deletedFiles++;
                }
            }

            CLI::write("Deleted {$deletedFiles} cache files", 'green');
        }
    }
}