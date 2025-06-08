<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Services;

class DebugCache extends BaseCommand
{
    protected $group       = 'Debug';
    protected $name        = 'debug:cache';
    protected $description = 'Debug cache keys for rate limiting';
    protected $usage       = 'debug:cache [ip_address]';

    public function run(array $params)
    {
        $cache = Services::cache();
        $ipAddress = $params[0] ?? '127.0.0.1';

        CLI::write("Debugging cache for IP: {$ipAddress}", 'yellow');
        CLI::newLine();

        // Possible cache keys based on the code
        $possibleKeys = [
            'rate_limit_login_' . $ipAddress,
            'lockout_login_' . $ipAddress,
            'throttle_login_' . $ipAddress,
            'rate_limit_register_' . $ipAddress,
            'lockout_register_' . $ipAddress,
            'throttle_register_' . $ipAddress,
        ];

        foreach ($possibleKeys as $key) {
            $value = $cache->get($key);
            if ($value !== null) {
                CLI::write("Found: {$key} = " . var_export($value, true), 'red');
            } else {
                CLI::write("Not found: {$key}", 'green');
            }
        }

        CLI::newLine();
        CLI::write('Cache debug completed.', 'blue');
    }
}