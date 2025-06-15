<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;

class AuthController extends BaseController
{
    protected $userModel;
    protected $roleModel;
    protected $session;
    protected $rateLimiter;

    public function __construct()
    {
        $this->userModel = model('UserModel');
        $this->roleModel = model('RoleModel');
        $this->session = session();
        $this->rateLimiter = service('rateLimiter');
        helper(['url', 'form']);
    }

    /**
     * Show login page
     */
    public function signin(): string|RedirectResponse
    {
        if ($this->session->get('isLoggedIn')) {
            return redirect()->to('/dashboard');
        }

        $data = ['title' => 'Sign In'];
        return view('auth/signin', $data);
    }

    /**
     * Show registration page
     */
    public function signup(): string|RedirectResponse
    {
        if ($this->session->get('isLoggedIn')) {
            return redirect()->to('/dashboard');
        }

        $data = ['title' => 'Sign Up'];
        return view('auth/signup', $data);
    }

    /**
     * Process login with role-based authentication
     */
    public function processLogin(): ResponseInterface
    {
        $identifier = $this->request->getPost('identifier');
        $password = $this->request->getPost('password');
        $clientIP = $this->request->getIPAddress();

        // Rate limiting
        $rateLimitKey = 'login_' . $clientIP;
        $config = config('RateLimit');

        if (!$this->rateLimiter->isAllowed($rateLimitKey, $config->login['max_attempts'], $config->login['time_window'])) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Too many login attempts. Please try again later.',
                'csrf_token' => csrf_token(),
                'csrf_hash' => csrf_hash()
            ])->setStatusCode(429);
        }

        // Validate input
        $rules = [
            'identifier' => 'required|min_length[3]|max_length[100]',
            'password' => 'required'
        ];

        if (!$this->validate($rules)) {
            $this->rateLimiter->attempt($rateLimitKey, $config->login['max_attempts'], $config->login['time_window']);

            return $this->response->setJSON([
                'success' => false,
                'message' => 'Invalid input provided',
                'errors' => $this->validator->getErrors(),
                'csrf_token' => csrf_token(),
                'csrf_hash' => csrf_hash()
            ])->setStatusCode(400);
        }

        // Check if user is locked
        if ($this->userModel->isLocked($identifier)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Account is temporarily locked due to too many failed attempts.',
                'csrf_token' => csrf_token(),
                'csrf_hash' => csrf_hash()
            ])->setStatusCode(423);
        }

        // Attempt login
        $user = $this->attemptLogin($identifier, $password);

        if ($user) {
            // Clear rate limiting and login attempts
            $this->rateLimiter->clear($rateLimitKey);
            $this->userModel->clearLoginAttempts($user['id']);
            $this->userModel->updateLastLogin($user['id']);

            // Set session with role information
            $this->setUserSession($user);

            // Log successful login
            log_message('info', "Successful login: {$user['username']} (Role: {$user['role_slug']}) from IP: {$clientIP}");

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Login successful',
                'redirect' => '/dashboard',
                'user' => [
                    'username' => $user['username'],
                    'role' => $user['role_slug'],
                    'name' => $user['first_name'] . ' ' . $user['last_name']
                ],
                'csrf_token' => csrf_token(),
                'csrf_hash' => csrf_hash()
            ]);
        } else {
            // Increment login attempts and potentially lock account
            $this->userModel->incrementLoginAttempts($identifier);
            $this->rateLimiter->attempt($rateLimitKey, $config->login['max_attempts'], $config->login['time_window']);

            $remainingAttempts = $this->rateLimiter->getRemainingAttempts($rateLimitKey, $config->login['max_attempts']);

            log_message('warning', "Failed login attempt for: {$identifier} from IP: {$clientIP}");

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
     * Process registration with default role assignment
     */
    public function processRegister(): ResponseInterface
    {
        $clientIP = $this->request->getIPAddress();
        $rateLimitKey = 'register_' . $clientIP;
        $config = config('RateLimit');

        // Rate limiting
        if (!$this->rateLimiter->isAllowed($rateLimitKey, $config->register['max_attempts'], $config->register['time_window'])) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Too many registration attempts. Please try again later.',
                'csrf_token' => csrf_token(),
                'csrf_hash' => csrf_hash()
            ])->setStatusCode(429);
        }

        // Validation rules
        $rules = [
            'username' => 'required|min_length[3]|max_length[50]|is_unique[users.username]|alpha_numeric',
            'email' => 'required|valid_email|is_unique[users.email]',
            'password' => 'required|min_length[8]',
            'password_confirm' => 'required|matches[password]',
            'first_name' => 'required|min_length[2]|max_length[100]',
            'last_name' => 'required|min_length[2]|max_length[100]'
        ];

        if (!$this->validate($rules)) {
            $this->rateLimiter->attempt($rateLimitKey, $config->register['max_attempts'], $config->register['time_window']);

            return $this->response->setJSON([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $this->validator->getErrors(),
                'csrf_token' => csrf_token(),
                'csrf_hash' => csrf_hash()
            ])->setStatusCode(400);
        }

        try {
            // Get default role
            $defaultRole = $this->roleModel->getDefaultRole();
            if (!$defaultRole) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'System configuration error. Please contact administrator.',
                    'csrf_token' => csrf_token(),
                    'csrf_hash' => csrf_hash()
                ])->setStatusCode(500);
            }

            // Prepare user data
            $userData = [
                'username' => $this->request->getPost('username'),
                'email' => $this->request->getPost('email'),
                'password' => $this->request->getPost('password'),
                'first_name' => $this->request->getPost('first_name'),
                'last_name' => $this->request->getPost('last_name'),
                'role_id' => $defaultRole['id'],
                'is_active' => 1
            ];

            // Create user
            $userId = $this->userModel->createWithRole($userData);

            if ($userId) {
                log_message('info', "New user registered: {$userData['username']} with role: {$defaultRole['slug']}");

                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Registration successful! You can now login.',
                    'redirect' => '/auth/signin',
                    'csrf_token' => csrf_token(),
                    'csrf_hash' => csrf_hash()
                ]);
            } else {
                $this->rateLimiter->attempt($rateLimitKey, $config->register['max_attempts'], $config->register['time_window']);

                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Registration failed. Please try again.',
                    'csrf_token' => csrf_token(),
                    'csrf_hash' => csrf_hash()
                ])->setStatusCode(500);
            }
        } catch (\Exception $e) {
            log_message('error', 'Registration error: ' . $e->getMessage());

            return $this->response->setJSON([
                'success' => false,
                'message' => 'Registration failed. Please try again.',
                'csrf_token' => csrf_token(),
                'csrf_hash' => csrf_hash()
            ])->setStatusCode(500);
        }
    }

    /**
     * Show forgot password page
     */
    public function forgotPassword(): string|RedirectResponse
    {
        if ($this->session->get('isLoggedIn')) {
            return redirect()->to('/dashboard');
        }

        $data = ['title' => 'Forgot Password'];
        return view('auth/forgot_password', $data);
    }

    /**
     * Process forgot password request
     */
    public function processForgotPassword(): ResponseInterface
    {
        try {
            log_message('debug', 'processForgotPassword called');

            $email = $this->request->getPost('email');
            $clientIP = $this->request->getIPAddress();
            $rateLimitKey = 'forgot_password_' . $clientIP;

            log_message('debug', "Email: {$email}, IP: {$clientIP}");

            // Load RateLimit config
            $config = config('RateLimit');
            if (!$config) {
                log_message('error', 'RateLimit config not found');
                throw new \Exception('Configuration error');
            }

            // Use password_reset config from RateLimit.php
            $maxAttempts = $config->password_reset['max_attempts'];
            $timeWindow = $config->password_reset['time_window'];

            log_message('debug', "Rate limit config - Max attempts: {$maxAttempts}, Time window: {$timeWindow}");

            // Rate limiting check
            if (!$this->rateLimiter->isAllowed($rateLimitKey, $maxAttempts, $timeWindow)) {
                log_message('warning', "Rate limit exceeded for forgot password from IP: {$clientIP}");

                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Too many password reset attempts. Please try again later.',
                    'csrf_token' => csrf_token(),
                    'csrf_hash' => csrf_hash()
                ])->setStatusCode(429);
            }

            // Validate email
            $validation = \Config\Services::validation();
            $validation->setRules(['email' => 'required|valid_email']);

            if (!$validation->run(['email' => $email])) {
                log_message('debug', 'Email validation failed');

                // Increment rate limiter on validation failure
                $this->rateLimiter->attempt($rateLimitKey, $maxAttempts, $timeWindow);

                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Please provide a valid email address.',
                    'errors' => $validation->getErrors(),
                    'csrf_token' => csrf_token(),
                    'csrf_hash' => csrf_hash()
                ])->setStatusCode(400);
            }

            log_message('debug', 'Email validation passed');

            // Check if user exists
            $user = $this->userModel->findByUsernameOrEmail($email);
            log_message('debug', 'User lookup completed: ' . ($user ? 'found' : 'not found'));

            // Always increment rate limiter to prevent email enumeration
            $this->rateLimiter->attempt($rateLimitKey, $maxAttempts, $timeWindow);

            if ($user && $user['is_active']) {
                try {
                    // Generate reset token
                    $resetToken = bin2hex(random_bytes(32));
                    log_message('debug', 'Reset token generated');

                    // Save reset token using direct update (avoiding the previous error)
                    $updateData = [
                        'reset_token' => password_hash($resetToken, PASSWORD_DEFAULT),
                        'reset_expires' => date('Y-m-d H:i:s', strtotime('+1 hour')),
                        'updated_at' => date('Y-m-d H:i:s')
                    ];

                    $tokenSaved = $this->userModel->update($user['id'], $updateData);

                    if ($tokenSaved) {
                        log_message('debug', 'Reset token saved successfully');

                        // Send reset email
                        $this->sendPasswordResetEmail($user['email'], $user['first_name'], $resetToken);

                        log_message('info', "Password reset requested for: {$email} from IP: {$clientIP}");
                    } else {
                        log_message('error', "Failed to save reset token for user: {$user['id']}");
                    }

                } catch (\Exception $e) {
                    log_message('error', 'Error processing reset for valid user: ' . $e->getMessage());
                }
            } else {
                log_message('warning', "Password reset requested for non-existent/inactive email: {$email} from IP: {$clientIP}");
            }

            // Always return success to prevent email enumeration
            return $this->response->setJSON([
                'success' => true,
                'message' => 'If an account with that email exists, a password reset link has been sent.',
                'csrf_token' => csrf_token(),
                'csrf_hash' => csrf_hash()
            ]);

        } catch (\Exception $e) {
            log_message('error', 'processForgotPassword fatal error: ' . $e->getMessage());

            return $this->response->setJSON([
                'success' => false,
                'message' => 'An error occurred while processing your request. Please try again.',
                'csrf_token' => csrf_token(),
                'csrf_hash' => csrf_hash()
            ])->setStatusCode(500);
        }
    }

    /**
     * Show reset password page
     */
    public function resetPassword(string $token): string|RedirectResponse
    {
        if ($this->session->get('isLoggedIn')) {
            return redirect()->to('/dashboard');
        }

        // Verify token
        $user = $this->userModel->findByResetToken($token);

        if (!$user) {
            return redirect()->to('/auth/forgot-password')
                ->with('error', 'Invalid or expired reset token. Please request a new password reset.');
        }

        $data = [
            'title' => 'Reset Password',
            'token' => $token
        ];

        return view('auth/reset_password', $data);
    }

    /**
     * Process password reset
     */
    public function processResetPassword(): ResponseInterface
    {
        try {
            $token = $this->request->getPost('token');
            $password = $this->request->getPost('password');
            $passwordConfirm = $this->request->getPost('password_confirm');
            $clientIP = $this->request->getIPAddress();

            log_message('debug', 'processResetPassword called');

            // Load RateLimit config
            $config = config('RateLimit');
            if (!$config) {
                log_message('error', 'RateLimit config not found');
                throw new \Exception('Configuration error');
            }

            // Use general rate limiting for reset password process (or create specific config)
            $rateLimitKey = 'reset_password_' . $clientIP;
            $maxAttempts = 5; // Or add to config: $config->password_reset_process['max_attempts']
            $timeWindow = 900; // 15 minutes, or add to config: $config->password_reset_process['time_window']

            log_message('debug', "Rate limit config for reset - Max attempts: {$maxAttempts}, Time window: {$timeWindow}");

            // Rate limiting for reset attempts
            if (!$this->rateLimiter->isAllowed($rateLimitKey, $maxAttempts, $timeWindow)) {
                log_message('warning', "Rate limit exceeded for password reset from IP: {$clientIP}");

                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Too many reset attempts. Please try again later.',
                    'csrf_token' => csrf_token(),
                    'csrf_hash' => csrf_hash()
                ])->setStatusCode(429);
            }

            // Validate input
            $rules = [
                'token' => 'required',
                'password' => 'required|min_length[8]',
                'password_confirm' => 'required|matches[password]'
            ];

            if (!$this->validate($rules)) {
                $this->rateLimiter->attempt($rateLimitKey, $maxAttempts, $timeWindow);

                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $this->validator->getErrors(),
                    'csrf_token' => csrf_token(),
                    'csrf_hash' => csrf_hash()
                ])->setStatusCode(400);
            }

            // Verify token and reset password
            $user = $this->userModel->findByResetToken($token);

            if (!$user) {
                $this->rateLimiter->attempt($rateLimitKey, $maxAttempts, $timeWindow);

                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Invalid or expired reset token.',
                    'csrf_token' => csrf_token(),
                    'csrf_hash' => csrf_hash()
                ])->setStatusCode(400);
            }

            // Reset password
            if ($this->userModel->resetPassword($token, $password)) {
                log_message('info', "Password reset successful for user: {$user['username']} from IP: {$clientIP}");

                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Password reset successful! You can now login with your new password.',
                    'redirect' => '/auth/signin',
                    'csrf_token' => csrf_token(),
                    'csrf_hash' => csrf_hash()
                ]);
            } else {
                $this->rateLimiter->attempt($rateLimitKey, $maxAttempts, $timeWindow);

                log_message('error', "Password reset failed for token from IP: {$clientIP}");

                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Password reset failed. Please try again.',
                    'csrf_token' => csrf_token(),
                    'csrf_hash' => csrf_hash()
                ])->setStatusCode(500);
            }

        } catch (\Exception $e) {
            log_message('error', 'processResetPassword fatal error: ' . $e->getMessage());

            return $this->response->setJSON([
                'success' => false,
                'message' => 'An error occurred while processing your request. Please try again.',
                'csrf_token' => csrf_token(),
                'csrf_hash' => csrf_hash()
            ])->setStatusCode(500);
        }
    }

    /**
     * Send password reset email
     */
    private function sendPasswordResetEmail(string $email, string $firstName, string $token): void
    {
        try {
            $emailService = service('email');

            $resetUrl = base_url("/auth/reset-password/{$token}");
            $subject = 'Password Reset Request';

            // Simple email message (you can create a proper email template)
            $message = "
        <h2>Password Reset Request</h2>
        <p>Hello {$firstName},</p>
        <p>You requested a password reset for your account. Click the link below to reset your password:</p>
        <p><a href='{$resetUrl}' style='background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Reset Password</a></p>
        <p>This link will expire in 1 hour.</p>
        <p>If you did not request this reset, please ignore this email.</p>
        <br>
        <p>Best regards,<br>Your Support Team</p>
        ";

            $emailService->setTo($email)
                ->setSubject($subject)
                ->setMessage($message)
                ->send();

            log_message('info', "Password reset email sent to: {$email}");
        } catch (\Exception $e) {
            log_message('error', "Failed to send password reset email to {$email}: " . $e->getMessage());
        }
    }

    /**
     * Process logout
     */
    public function logout(): ResponseInterface
    {
        $username = $this->session->get('username');
        $clientIP = $this->request->getIPAddress();

        // Update last activity before logout
        if ($userId = $this->session->get('user_id')) {
            $this->userModel->updateLastActivity($userId);
        }

        // Destroy session
        $this->session->destroy();

        log_message('info', "User logged out: {$username} from IP: {$clientIP}");

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Logged out successfully',
                'redirect' => '/auth/signin'
            ]);
        }

        return redirect()->to('/auth/signin')->with('success', 'Logged out successfully');
    }

    /**
     * Refresh user session data (useful after role/permission changes)
     */
    public function refreshSession(): ResponseInterface
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(404);
        }

        if (!$this->session->get('isLoggedIn')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Not authenticated'
            ])->setStatusCode(401);
        }

        $userId = $this->session->get('user_id');
        $user = $this->userModel->findWithRole($userId);

        if (!$user || !$user['is_active']) {
            $this->session->destroy();
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Account access revoked',
                'redirect' => '/auth/signin'
            ])->setStatusCode(401);
        }

        // Update session with fresh data
        $this->setUserSession($user);

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Session refreshed successfully',
            'user' => [
                'username' => $user['username'],
                'role' => $user['role_slug'],
                'permissions' => array_column($user['permissions'], 'slug')
            ]
        ]);
    }

    /**
     * Private helper methods
     */

    /**
     * Attempt login with role verification
     */
    private function attemptLogin(string $identifier, string $password): array|false
    {
        try {
            $user = $this->userModel->findByUsernameOrEmail($identifier);

            if (!$user || !password_verify($password, $user['password'])) {
                return false;
            }

            // Check if user is active
            if (!$user['is_active']) {
                log_message('warning', "Login attempt for inactive user: {$user['username']}");
                return false;
            }

            // Check if role exists and is active
            if (!$user['role_slug']) {
                log_message('warning', "Login attempt for user with inactive role: {$user['username']}");
                return false;
            }

            return $user;
        } catch (\Exception $e) {
            log_message('error', 'Login error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Set user session with role and permission data
     */
    private function setUserSession(array $user): void
    {
        $permissions = $user['permissions'] ?? [];

        $sessionData = [
            'user_id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'full_name' => $user['first_name'] . ' ' . $user['last_name'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'role_id' => $user['role_id'],
            'role_slug' => $user['role_slug'],
            'role_name' => $user['role_name'],
            'role_level' => $user['role_level'],
            'permissions' => array_column($permissions, 'slug'),
            'isLoggedIn' => true,
            'login_time' => time(),
            'last_activity' => time()
        ];

        $this->session->set($sessionData);
    }
}