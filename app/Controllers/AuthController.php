<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Validation\UserValidation;
use App\Libraries\RateLimiter;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\PasswordResetModel;
use App\Libraries\EmailService;

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
     * Get email service based on environment
     */
    private function getEmailService(): EmailService
    {
        return new EmailService();
    }

    /**
     * Process forgot password
     */
    /**
     * Process forgot password - Updated with email factory
     */
    public function processForgotPassword(): ResponseInterface
    {
        $clientIP = $this->request->getIPAddress();
        $rateLimitKey = 'forgot_password_' . $clientIP;

        // Rate limiting untuk forgot password (3 attempts per hour)
        if (!$this->rateLimiter->check($rateLimitKey, 3, 3600)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Too many password reset attempts. Please try again later.',
                'csrf_token' => csrf_token(),
                'csrf_hash' => csrf_hash()
            ])->setStatusCode(429);
        }

        $rules = UserValidation::getForgotPasswordRules();

        if (!$this->validate($rules)) {
            $this->rateLimiter->attempt($rateLimitKey, 3, 3600);

            return $this->response->setJSON([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $this->validator->getErrors(),
                'csrf_token' => csrf_token(),
                'csrf_hash' => csrf_hash()
            ]);
        }

        $email = $this->request->getPost('email');

        try {
            // Check if user exists
            $user = $this->userModel->findByEmail($email);

            if ($user) {
                $passwordResetModel = new PasswordResetModel();

                // Check for recent reset requests
                if ($passwordResetModel->hasRecentRequest($email, 5)) {
                    return $this->response->setJSON([
                        'success' => false,
                        'message' => 'A password reset link was recently sent. Please check your email or wait 5 minutes.',
                        'csrf_token' => csrf_token(),
                        'csrf_hash' => csrf_hash()
                    ]);
                }

                // Generate reset token
                $token = $passwordResetModel->createToken($email);

                if ($token) {
                    // Send reset email using appropriate service
                    $emailService = $this->getEmailService();
                    $emailSent = $emailService->sendPasswordResetEmail($email, $token, $user['first_name']);

                    if (!$emailSent && ENVIRONMENT !== 'development') {
                        log_message('error', "Failed to send password reset email to: {$email}");
                    }

                    log_message('info', "Password reset requested for: {$email} from IP: {$clientIP}");
                }
            }

            // Different messages for development vs production
            $message = ENVIRONMENT === 'development'
                ? 'Password reset email has been logged. Check writable/emails/ folder for the email content.'
                : 'If the email address exists in our system, a password reset link has been sent.';

            return $this->response->setJSON([
                'success' => true,
                'message' => $message,
                'csrf_token' => csrf_token(),
                'csrf_hash' => csrf_hash()
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Password reset error: ' . $e->getMessage());

            return $this->response->setJSON([
                'success' => false,
                'message' => 'An error occurred. Please try again later.',
                'csrf_token' => csrf_token(),
                'csrf_hash' => csrf_hash()
            ]);
        }
    }

    /**
     * Reset password page
     */
    public function resetPassword(string $token): RedirectResponse
    {
        $passwordResetModel = new PasswordResetModel();

        // Verify token exists and is valid
        $resetData = $passwordResetModel->verifyToken($token);

        if (!$resetData) {
            // Token invalid or expired
            return redirect()->to('/auth/forgot-password')
                ->with('error', 'Invalid or expired reset token. Please request a new one.');
        }

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

        $token = $this->request->getPost('token');
        $password = $this->request->getPost('password');

        try {
            $passwordResetModel = new PasswordResetModel();

            // Verify token
            $resetData = $passwordResetModel->verifyToken($token);

            if (!$resetData) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Invalid or expired reset token.',
                    'csrf_token' => csrf_token(),
                    'csrf_hash' => csrf_hash()
                ]);
            }

            // Find user by email
            $user = $this->userModel->findByEmail($resetData['email']);

            if (!$user) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'User not found.',
                    'csrf_token' => csrf_token(),
                    'csrf_hash' => csrf_hash()
                ]);
            }

            // Update password
            $updateData = [
                'password' => password_hash($password, PASSWORD_ARGON2ID)
            ];

            if ($this->userModel->update($user['id'], $updateData)) {
                // Mark token as used
                $passwordResetModel->markTokenAsUsed($token);

                // Log password reset
                log_message('info', "Password reset completed for user: {$user['username']}");

                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Password has been reset successfully. You can now login with your new password.',
                    'redirect' => '/auth/signin',
                    'csrf_token' => csrf_token(),
                    'csrf_hash' => csrf_hash()
                ]);
            } else {
                throw new \RuntimeException('Failed to update password');
            }

        } catch (\Exception $e) {
            log_message('error', 'Password reset processing error: ' . $e->getMessage());

            return $this->response->setJSON([
                'success' => false,
                'message' => 'An error occurred while resetting your password. Please try again.',
                'csrf_token' => csrf_token(),
                'csrf_hash' => csrf_hash()
            ]);
        }
    }
}