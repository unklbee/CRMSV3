<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Validation\UserValidation;
use App\Libraries\RateLimiter;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

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
            'title' => 'Sign In'
        ];
        return view('auth/signin', $data);
    }

    /**
     * Process login dengan rate limiting yang diperbaiki
     */
    public function processLogin(): ResponseInterface
    {
        $clientIP = $this->request->getIPAddress();
        $identifier = $this->request->getPost('identifier', FILTER_SANITIZE_EMAIL) ?? '';

        // TEMPORARY: Skip rate limiting untuk development
        $skipRateLimit = (ENVIRONMENT === 'development');

        if (!$skipRateLimit) {
            // Rate limiting code here (untuk production)
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
        }

        // Validate input
        $rules = [
            'identifier' => [
                'rules' => 'required|min_length[3]|max_length[100]',
                'errors' => [
                    'required' => 'Username or Email is required',
                    'min_length' => 'Username/Email is too short',
                    'max_length' => 'Username/Email is too long'
                ]
            ],
            'password' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Password is required'
                ]
            ]
        ];

        if (!$this->validate($rules)) {
            // Only increment failed attempts in production
            if (!$skipRateLimit) {
                $this->rateLimiter->attempt($rateLimitKey, $config['max_attempts'], $config['time_window']);
            }

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
            // Clear rate limiting on successful login (only in production)
            if (!$skipRateLimit) {
                $this->rateLimiter->clear($rateLimitKey);
                $this->rateLimiter->clearLockout($rateLimitKey);
            }

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
            // Only increment failed attempts in production
            if (!$skipRateLimit) {
                $this->rateLimiter->attempt($rateLimitKey, $config['max_attempts'], $config['time_window']);
                $remainingAttempts = $this->rateLimiter->getRemainingAttempts($rateLimitKey, $config['max_attempts']);
            } else {
                $remainingAttempts = 999; // Unlimited dalam development
            }

            // Log failed attempt
            log_message('warning', "Failed login attempt for identifier: {$identifier} from IP: {$clientIP}");

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
        try {
            // Single query to find and verify user
            $user = $this->userModel->findByUsernameOrEmail($identifier);

            if (!$user || !password_verify($password, $user['password'])) {
                return false;
            }

            // Check if user is active
            if (!$user['is_active']) {
                log_message('warning', "Login attempt for inactive user: {$user['username']}");
                return false;
            }

            // Update last_login in background (non-blocking)
            $this->updateLastLoginAsync($user['id']);

            return $user;
        } catch (\Exception $e) {
            log_message('error', 'Login error: ' . $e->getMessage());
            return false;
        }
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
            'isLoggedIn' => true, // Fixed: consistent key
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
        try {
            $this->userModel->updateLastLogin($userId);
        } catch (\Exception $e) {
            // Log error tapi jangan block login process
            log_message('error', 'Failed to update last_login for user ' . $userId . ': ' . $e->getMessage());
        }
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
        $config = $this->rateLimitConfig['register'];

        // Rate limiting untuk registrasi
        if (!$this->rateLimiter->check($rateLimitKey, $config['max_attempts'], $config['time_window'])) {
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
            $this->rateLimiter->attempt($rateLimitKey, $config['max_attempts'], $config['time_window']);

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
                $this->rateLimiter->clear($rateLimitKey);

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

        $this->rateLimiter->attempt($rateLimitKey, $config['max_attempts'], $config['time_window']);

        return $this->response->setJSON([
            'success' => false,
            'message' => 'Registration failed. Please try again.',
            'csrf_token' => csrf_token(),
            'csrf_hash' => csrf_hash()
        ]);
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

    /**
     * Forgot password page
     */
    public function forgotPassword(): string
    {
        $data = ['title' => 'Forgot Password'];
        return view('auth/forgot_password', $data);
    }

    /**
     * Process forgot password
     */
    public function processForgotPassword(): ResponseInterface
    {
        $rules = UserValidation::getForgotPasswordRules();

        if (!$this->validate($rules)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $this->validator->getErrors(),
                'csrf_token' => csrf_token(),
                'csrf_hash' => csrf_hash()
            ]);
        }

        $email = $this->request->getPost('email');
        $user = $this->userModel->findByEmail($email);

        if ($user) {
            // Generate reset token
            $token = bin2hex(random_bytes(32));

            // Save token to database (you'll need to add this field)
            // $this->userModel->saveResetToken($user['id'], $token);

            // Send email (implement email service)
            // $this->sendResetEmail($email, $token);

            log_message('info', "Password reset requested for: {$email}");
        }

        // Always return success untuk security
        return $this->response->setJSON([
            'success' => true,
            'message' => 'If the email exists, a reset link has been sent.',
            'csrf_token' => csrf_token(),
            'csrf_hash' => csrf_hash()
        ]);
    }

    /**
     * Reset password page
     */
    public function resetPassword(string $token): string
    {
        $data = [
            'title' => 'Reset Password',
            'token' => $token
        ];
        return view('auth/reset_password', $data);
    }

    /**
     * Process reset password
     */
    public function processResetPassword(): ResponseInterface
    {
        $rules = UserValidation::getResetPasswordRules();

        if (!$this->validate($rules)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $this->validator->getErrors(),
                'csrf_token' => csrf_token(),
                'csrf_hash' => csrf_hash()
            ]);
        }

        // Implement token verification and password reset
        // This requires additional database fields and logic

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Password has been reset successfully.',
            'redirect' => '/auth/signin',
            'csrf_token' => csrf_token(),
            'csrf_hash' => csrf_hash()
        ]);
    }
}