<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class RateLimit extends BaseConfig
{
    /**
     * Login Rate Limiting
     */
    public array $login = [
        'max_attempts' => 5,      // Maximum attempts
        'time_window' => 900,     // 15 minutes in seconds
    ];

    /**
     * Registration Rate Limiting
     */
    public array $register = [
        'max_attempts' => 3,      // Maximum attempts
        'time_window' => 3600,    // 1 hour in seconds
    ];

    /**
     * API Rate Limiting
     */
    public array $api = [
        'max_requests' => 100,    // Requests per minute
        'time_window' => 60,      // 1 minute in seconds
    ];

    /**
     * General Rate Limiting
     */
    public array $general = [
        'max_requests' => 200,    // Requests per minute
        'time_window' => 60,      // 1 minute in seconds
    ];

    /**
     * Password Reset Rate Limiting
     */
    public array $password_reset = [
        'max_attempts' => 3,      // Maximum attempts
        'time_window' => 3600,    // 1 hour in seconds
    ];

    /**
     * Email Verification Rate Limiting
     */
    public array $email_verification = [
        'max_attempts' => 5,      // Maximum attempts
        'time_window' => 3600,    // 1 hour in seconds
    ];
}