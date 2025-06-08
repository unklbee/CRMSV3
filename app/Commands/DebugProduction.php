<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class DebugProduction extends BaseCommand
{
    protected $group       = 'Debug';
    protected $name        = 'debug:production';
    protected $description = 'Debug production errors by checking logs';
    protected $usage       = 'debug:production';

    public function run(array $params)
    {
        CLI::write('=== DEBUGGING PRODUCTION ERRORS ===', 'yellow');
        CLI::newLine();

        // Check current environment
        CLI::write('Current Environment: ' . ENVIRONMENT, 'blue');
        CLI::newLine();

        // Check log files
        $logPath = WRITEPATH . 'logs/';

        if (!is_dir($logPath)) {
            CLI::write('Log directory not found: ' . $logPath, 'red');
            return;
        }

        $logFiles = glob($logPath . '*.log');

        if (empty($logFiles)) {
            CLI::write('No log files found in: ' . $logPath, 'yellow');
            return;
        }

        CLI::write('Found ' . count($logFiles) . ' log files:', 'green');

        foreach ($logFiles as $logFile) {
            $fileName = basename($logFile);
            $fileSize = filesize($logFile);
            CLI::write("- {$fileName} ({$fileSize} bytes)", 'white');
        }

        CLI::newLine();

        // Show recent errors from the latest log
        $latestLog = end($logFiles);
        CLI::write('Reading latest log: ' . basename($latestLog), 'blue');
        CLI::newLine();

        $content = file_get_contents($latestLog);
        $lines = explode("\n", $content);

        // Get last 20 lines
        $recentLines = array_slice($lines, -20);

        foreach ($recentLines as $line) {
            if (!empty(trim($line))) {
                if (strpos($line, 'ERROR') !== false || strpos($line, 'CRITICAL') !== false) {
                    CLI::write($line, 'red');
                } elseif (strpos($line, 'WARNING') !== false) {
                    CLI::write($line, 'yellow');
                } else {
                    CLI::write($line, 'white');
                }
            }
        }
    }
}