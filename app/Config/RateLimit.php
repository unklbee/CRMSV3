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
     * Password Reset Request Rate Limiting
     */
    public array $password_reset = [
        'max_attempts' => 5,      // Maximum reset requests
        'time_window' => 3600,    // 1 hour in seconds
    ];

    /**
     * Password Reset Process Rate Limiting (when actually resetting with token)
     */
    public array $password_reset_process = [
        'max_attempts' => 5,      // Maximum reset attempts with token
        'time_window' => 900,     // 15 minutes in seconds
    ];

    /**
     * Email Verification Rate Limiting
     */
    public array $email_verification = [
        'max_attempts' => 5,      // Maximum attempts
        'time_window' => 3600,    // 1 hour in seconds
    ];

    /**
     * Two-Factor Authentication Rate Limiting
     */
    public array $two_factor = [
        'max_attempts' => 5,      // Maximum 2FA attempts
        'time_window' => 900,     // 15 minutes in seconds
    ];

    /**
     * Account Lockout Rate Limiting
     */
    public array $account_lockout = [
        'max_attempts' => 10,     // Maximum failed attempts before lockout
        'lockout_duration' => 1800, // 30 minutes lockout
    ];

    /**
     * Contact Form Rate Limiting
     */
    public array $contact_form = [
        'max_attempts' => 3,      // Maximum contact form submissions
        'time_window' => 3600,    // 1 hour in seconds
    ];

    /**
     * Newsletter Subscription Rate Limiting
     */
    public array $newsletter = [
        'max_attempts' => 5,      // Maximum subscription attempts
        'time_window' => 3600,    // 1 hour in seconds
    ];
}