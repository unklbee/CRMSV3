<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Security Configuration yang diperbaiki
 */
class Security extends BaseConfig
{
    /**
     * CSRF Protection method
     */
    public string $csrfProtection = 'cookie';

    /**
     * Randomize CSRF token untuk security tambahan
     */
    public bool $tokenRandomize = true;

    /**
     * CSRF token name - akan di-set di constructor
     */
    public string $tokenName = 'csrf_test_name';

    /**
     * CSRF header name
     */
    public string $headerName = 'X-CSRF-TOKEN';

    /**
     * CSRF cookie name - akan di-set di constructor
     */
    public string $cookieName = 'csrf_cookie_name';

    /**
     * CSRF expires time (2 jam)
     */
    public int $expires = 7200;

    /**
     * Regenerate token pada setiap submit untuk security maksimal
     */
    public bool $regenerate = true;

    /**
     * Redirect on failure hanya di production
     */
    public bool $redirect = false; // Will be set in constructor

    /**
     * Constructor untuk set dynamic values
     */
    public function __construct()
    {
        parent::__construct();

        // Set unique names berdasarkan application path
        $appHash = substr(md5(APPPATH), 0, 8);
        $this->tokenName = 'csrf_token_' . $appHash;
        $this->cookieName = 'csrf_cookie_' . $appHash;

        // Set redirect based on environment
        $this->redirect = (ENVIRONMENT === 'production');
    }

    /**
     * Get security headers configuration
     */
    public function getSecurityHeaders(): array
    {
        return [
            'X-Frame-Options' => 'DENY',
            'X-Content-Type-Options' => 'nosniff',
            'X-XSS-Protection' => '1; mode=block',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()',
            'X-Permitted-Cross-Domain-Policies' => 'none'
        ];
    }

    /**
     * Get Content Security Policy
     */
    public function getContentSecurityPolicy(): string
    {
        return "default-src 'self'; " .
            "script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; " .
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; " .
            "img-src 'self' data: https:; " .
            "font-src 'self' https://fonts.gstatic.com; " .
            "connect-src 'self'; " .
            "frame-ancestors 'none';";
    }

    /**
     * Get rate limiting configuration
     */
    public function getRateLimiting(): array
    {
        return [
            'login' => [
                'max_attempts' => 10,
                'time_window' => 900, // 15 menit
                'lockout_time' => 1800 // 30 menit
            ],
            'register' => [
                'max_attempts' => 3,
                'time_window' => 600, // 10 menit
                'lockout_time' => 1800 // 30 menit
            ],
            'password_reset' => [
                'max_attempts' => 3,
                'time_window' => 3600, // 1 jam
                'lockout_time' => 3600 // 1 jam
            ],
            'global' => [
                'max_requests' => 100,
                'time_window' => 60, // per menit
                'lockout_time' => 300 // 5 menit
            ]
        ];
    }

    /**
     * Get password policy configuration
     */
    public function getPasswordPolicy(): array
    {
        return [
            'min_length' => 8,
            'max_length' => 255,
            'require_uppercase' => true,
            'require_lowercase' => true,
            'require_numbers' => true,
            'require_symbols' => true,
            'prevent_common' => true,
            'prevent_personal_info' => true
        ];
    }

    /**
     * Get session security configuration
     */
    public function getSessionSecurity(): array
    {
        return [
            'timeout' => 1800, // 30 menit inactivity
            'check_ip' => true,
            'check_user_agent' => true,
            'regenerate_id' => true,
            'secure_cookie' => (ENVIRONMENT === 'production'),
            'httponly_cookie' => true,
            'samesite_cookie' => 'Lax'
        ];
    }
}