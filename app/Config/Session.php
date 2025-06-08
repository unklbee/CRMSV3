<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Session\Handlers\BaseHandler;
use CodeIgniter\Session\Handlers\FileHandler;

/**
 * Session Configuration Fixed untuk Production
 */
class Session extends BaseConfig
{
    /**
     * Session driver
     */
    public string $driver = FileHandler::class;

    /**
     * Session cookie name
     */
    public string $cookieName = 'ci_session';

    /**
     * Session expiration - 2 jam
     */
    public int $expiration = 7200;

    /**
     * Save path
     */
    public string $savePath = '';

    /**
     * Match IP - disabled untuk compatibility
     */
    public bool $matchIP = false;

    /**
     * Regenerate session ID setiap 5 menit
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
     * Constructor dengan error handling yang kuat
     */
    public function __construct()
    {
        parent::__construct();

        // Always use FileHandler untuk stability
        $this->driver = FileHandler::class;

        // Set save path dengan multiple fallbacks
        $this->setSavePath();

        // Set unique cookie name
        $appHash = substr(md5(APPPATH), 0, 8);
        $this->cookieName = 'ci_session_' . $appHash;

        // Production settings
        if (ENVIRONMENT === 'production') {
            $this->matchIP = false; // Disable untuk avoid issues
            $this->regenerateDestroy = true;
        }
    }

    /**
     * Set save path dengan multiple fallbacks
     */
    private function setSavePath(): void
    {
        $primaryPath = WRITEPATH . 'session';
        $backupPath = sys_get_temp_dir() . '/ci_session';

        try {
            // Try primary path first
            if ($this->ensureDirectoryExists($primaryPath)) {
                $this->savePath = $primaryPath;
                return;
            }

            // Fallback to system temp
            if ($this->ensureDirectoryExists($backupPath)) {
                $this->savePath = $backupPath;
                log_message('warning', 'Using fallback session path: ' . $backupPath);
                return;
            }

            // Last resort - use system temp directly
            $this->savePath = sys_get_temp_dir();
            log_message('warning', 'Using system temp for sessions: ' . $this->savePath);

        } catch (\Exception $e) {
            log_message('error', 'Session path setup failed: ' . $e->getMessage());
            $this->savePath = sys_get_temp_dir();
        }
    }

    /**
     * Ensure directory exists and is writable
     */
    private function ensureDirectoryExists(string $path): bool
    {
        try {
            // Create directory if not exists
            if (!is_dir($path)) {
                if (!mkdir($path, 0755, true)) {
                    return false;
                }
            }

            // Check if writable
            if (!is_writable($path)) {
                // Try to fix permissions
                @chmod($path, 0755);

                if (!is_writable($path)) {
                    return false;
                }
            }

            return true;

        } catch (\Exception $e) {
            log_message('error', 'Directory creation failed for: ' . $path . ' - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get session configuration for ini_set
     */
    public function getSessionConfig(): array
    {
        return [
            'session.cookie_lifetime' => $this->expiration,
            'session.cookie_path' => '/',
            'session.cookie_domain' => '',
            'session.cookie_secure' => (ENVIRONMENT === 'production' && isset($_SERVER['HTTPS'])),
            'session.cookie_httponly' => true,
            'session.cookie_samesite' => 'Lax',
            'session.use_strict_mode' => 1,
            'session.use_cookies' => 1,
            'session.use_only_cookies' => 1,
            'session.cache_limiter' => 'nocache',
            'session.cache_expire' => 180,
            'session.lazy_write' => 1,
            'session.name' => $this->cookieName,
            'session.save_path' => $this->savePath,
            'session.save_handler' => 'files'
        ];
    }
}