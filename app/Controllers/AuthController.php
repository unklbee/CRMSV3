<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Validation\UserValidation;
use App\Libraries\RateLimiter;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use ReflectionException;

class AuthController extends Controller
{
    protected UserModel $userModel;
    protected RateLimiter $rateLimiter;

    // Rate limiting configuration
    private array $rateLimitConfig = [
        'login' => [
            'max_attempts' => 5,
            'time_window' => 900, // 15 minutes
            'lockout_time' => 1800 // 30 minutes
        ],
        'register' => [
            'max_attempts' => 3,
            'time_window' => 600, // 10 minutes
            'lockout_time' => 1800 // 30 minutes
        ]
    ];

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->rateLimiter = new RateLimiter();
        helper(['form', 'url']);
    }

    public function signin(): string|RedirectResponse
    {
        if (session()->get('isLoggedIn')) {
            return redirect()->to('/dashboard');
        }

        $data = [
            'title' => 'Sign In',
            'remainingAttempts' => $this->getRemainingAttempts()
        ];
        return view('auth/signin', $data);
    }

    /**
     * Process login dengan rate limiting yang diperbaiki
     */
    public function processLogin(): ResponseInterface
    {
        $clientIP = $this->request->getIPAddress();
        $identifier = $this->request->getPost('identifier', FILTER_SANITIZE_STRING) ?? '';

        // Check rate limiting
        $rateLimitKey = 'login_' . $clientIP;
        $config = $this->rateLimitConfig['login'];

        // Check if locked out
        if ($this->rateLimiter->isLockedOut($rateLimitKey)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Account temporarily locked due to too many failed attempts.',
                'lockout_remaining' => $this->rateLimiter->getLockoutTime($rateLimitKey),
                'csrf_token' => csrf_token(),
                'csrf_hash' => csrf_hash()
            ])->setStatusCode(429);
        }

        // Check rate limit
        if (!$this->rateLimiter->check($rateLimitKey, $config['max_attempts'], $config['time_window'])) {
            // Set lockout
            $this->rateLimiter->setLockout($rateLimitKey, $config['lockout_time']);

            return $this->response->setJSON([
                'success' => false,
                'message' => 'Too many login attempts. Account locked for 30 minutes.',
                'csrf_token' => csrf_token(),
                'csrf_hash' => csrf_hash()
            ])->setStatusCode(429);
        }

        // Simplified validation
        $rules = [
            'identifier' => 'required|min_length[3]|max_length[100]',
            'password' => 'required|min_length[1]'
        ];

        if (!$this->validate($rules)) {
            // Increment failed attempts
            $this->rateLimiter->attempt($rateLimitKey, $config['max_attempts'], $config['time_window']);

            return $this->response->setJSON([
                'success' => false,
                'message' => 'Invalid input provided',
                'errors' => $this->validator->getErrors(),
                'csrf_token' => csrf_token(),
                'csrf_hash' => csrf_hash()
            ])->setStatusCode(400);
        }

        $password = $this->request->getPost('password');

        // Attempt login
        $user = $this->attemptLogin($identifier, $password);

        if ($user) {
            // Clear rate limiting on successful login
            $this->rateLimiter->clear($rateLimitKey);
            $this->rateLimiter->clearLockout($rateLimitKey);

            // Set minimal session data
            $this->setUserSession($user);

            // Log successful login
            log_message('info', "Successful login for user: {$user['username']} from IP: {$clientIP}");

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Login successful',
                'redirect' => '/dashboard',
                'csrf_token' => csrf_token(),
                'csrf_hash' => csrf_hash()
            ]);
        } else {
            // Increment failed attempts
            $this->rateLimiter->attempt($rateLimitKey, $config['max_attempts'], $config['time_window']);

            // Log failed attempt
            log_message('warning', "Failed login attempt for identifier: {$identifier} from IP: {$clientIP}");

            $remainingAttempts = $this->rateLimiter->getRemainingAttempts($rateLimitKey, $config['max_attempts']);

            return $this->response->setJSON([
                'success' => false,
                'message' => 'Invalid credentials provided',
                'remaining_attempts' => $remainingAttempts,
                'csrf_token' => csrf_token(),
                'csrf_hash' => csrf_hash()
            ])->setStatusCode(401);
        }
    }

    /**
     * Attempt login with optimized query
     */
    private function attemptLogin(string $identifier, string $password): array|false
    {
        // Single query to find and verify user
        $user = $this->userModel->findByUsernameOrEmail($identifier);

        if (!$user || !password_verify($password, $user['password'])) {
            return false;
        }

        // Update last_login in background (non-blocking)
        $this->updateLastLoginAsync($user['id']);

        return $user;
    }

    /**
     * Set minimal session data for security
     */
    private function setUserSession(array $user): void
    {
        $sessionData = [
            'user_id' => $user['id'],
            'username' => $user['username'],
            'role' => $user['role'],
            'isLoggedIn' => true,
            'login_time' => time(),
            'last_activity' => time()
        ];

        session()->set($sessionData);
    }

    /**
     * Update last login asynchronously untuk menghindari blocking
     */
    private function updateLastLoginAsync(int $userId): void
    {
        // In production, gunakan queue system seperti Redis/RabbitMQ
        // Untuk sekarang, update langsung tapi di background
        try {
            $this->userModel->update($userId, [
                'last_login' => date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            // Log error tapi jangan block login process
            log_message('error', 'Failed to update last_login for user ' . $userId . ': ' . $e->getMessage());
        }
    }

    /**
     * Get remaining login attempts menggunakan cache
     */
    private function getRemainingAttempts(string $key = null): int
    {
        if (!$key) {
            $key = 'login_' . $this->request->getIPAddress();
        }

        // Menggunakan cache untuk tracking attempts
        $cacheKey = 'throttle_' . $key;
        $attempts = cache()->get($cacheKey, 0);

        $config = $this->getRateLimitConfig('login');
        return max(0, $config['max_attempts'] - $attempts);
    }

    /**
     * Check if user is currently locked out
     */
    private function isLockedOut(string $key): bool
    {
        $lockKey = 'lockout_' . $key;
        return cache()->get($lockKey, false) !== false;
    }

    /**
     * Set lockout untuk user
     */
    private function setLockout(string $key): void
    {
        $config = $this->getRateLimitConfig('login');
        $lockKey = 'lockout_' . $key;
        cache()->save($lockKey, time(), $config['lockout_time']);
    }

    public function signup(): string|RedirectResponse
    {
        if (session()->get('isLoggedIn')) {
            return redirect()->to('/dashboard');
        }

        $data = ['title' => 'Sign Up'];
        return view('auth/signup', $data);
    }

    /**
     * Process registration dengan rate limiting
     */
    public function processRegister(): ResponseInterface
    {
        $clientIP = $this->request->getIPAddress();
        $rateLimitKey = 'register_' . $clientIP;
        $config = $this->getRateLimitConfig('register');

        // Rate limiting untuk registrasi
        if ($this->rateLimiter->check($rateLimitKey, $config['max_attempts'], $config['time_window']) === false) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Too many registration attempts. Please try again later.',
                'csrf_token' => csrf_token(),
                'csrf_hash' => csrf_hash()
            ])->setStatusCode(429);
        }

        // Full validation untuk registrasi
        $rules = UserValidation::getRegistrationRules();

        if (!$this->validate($rules)) {
            $this->rateLimiter->hit($rateLimitKey, $config['time_window']);

            return $this->response->setJSON([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $this->validator->getErrors(),
                'csrf_token' => csrf_token(),
                'csrf_hash' => csrf_hash()
            ]);
        }

        $userData = [
            'username' => $this->request->getPost('username'),
            'email' => $this->request->getPost('email'),
            'password' => $this->request->getPost('password'),
            'first_name' => $this->request->getPost('first_name'),
            'last_name' => $this->request->getPost('last_name')
        ];

        try {
            $userId = $this->userModel->createUser($userData);

            if ($userId) {
                // Clear rate limiting on successful registration
                cache()->delete('throttle_' . $rateLimitKey);

                log_message('info', "New user registered: {$userData['username']} from IP: {$clientIP}");

                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Registration successful! Please login.',
                    'redirect' => '/auth/signin',
                    'csrf_token' => csrf_token(),
                    'csrf_hash' => csrf_hash()
                ]);
            }
        } catch (\Exception $e) {
            log_message('error', 'Registration error: ' . $e->getMessage());
        }

        $this->rateLimiter->hit($rateLimitKey, $config['time_window']);

        return $this->response->setJSON([
            'success' => false,
            'message' => 'Registration failed. Please try again.',
            'csrf_token' => csrf_token(),
            'csrf_hash' => csrf_hash()
        ]);
    }

    /**
     * Get rate limiting config from Security config
     */
    private function getRateLimitConfig(string $type = 'login'): array
    {
        $defaultConfig = [
            'login' => [
                'max_attempts' => 5,
                'time_window' => 900,
                'lockout_time' => 1800
            ],
            'register' => [
                'max_attempts' => 3,
                'time_window' => 600,
                'lockout_time' => 1800
            ]
        ];

        return $defaultConfig[$type] ?? $defaultConfig['login'];
    }

    /**
     * Logout dengan session cleanup
     */
    public function logout(): RedirectResponse
    {
        $username = session()->get('username');

        // Log logout
        if ($username) {
            log_message('info', "User logged out: {$username}");
        }

        // Destroy session completely
        session()->destroy();

        return redirect()->to('/auth/signin')->with('message', 'You have been logged out successfully');
    }

    /**
     * Get fresh CSRF token (untuk AJAX requests)
     */
    public function getCsrfToken(): ResponseInterface
    {
        return $this->response->setJSON([
            'csrf_token' => csrf_token(),
            'csrf_hash' => csrf_hash()
        ]);
    }
}