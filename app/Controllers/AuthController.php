<?php

namespace App\Controllers;

use App\Controllers\BaseController;
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
    public function signin(): string
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
    public function signup(): string
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
        $config = config('RateLimit')->login;

        if (!$this->rateLimiter->isAllowed($rateLimitKey, $config['max_attempts'], $config['time_window'])) {
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
            $this->rateLimiter->attempt($rateLimitKey, $config['max_attempts'], $config['time_window']);

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
            $this->rateLimiter->attempt($rateLimitKey, $config['max_attempts'], $config['time_window']);

            $remainingAttempts = $this->rateLimiter->getRemainingAttempts($rateLimitKey, $config['max_attempts']);

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
        $config = config('RateLimit')->register;

        // Rate limiting
        if (!$this->rateLimiter->isAllowed($rateLimitKey, $config['max_attempts'], $config['time_window'])) {
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
            $this->rateLimiter->attempt($rateLimitKey, $config['max_attempts'], $config['time_window']);

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
                'password' => $this->request->getPost('password'), // Will be hashed in model
                'first_name' => $this->request->getPost('first_name'),
                'last_name' => $this->request->getPost('last_name'),
                'phone' => $this->request->getPost('phone'),
                'role_id' => $defaultRole['id'],
                'is_active' => 1,
                'email_verified_at' => null // Set to null initially, verify via email
            ];

            // Create user
            $userId = $this->userModel->createWithRole($userData);

            if ($userId) {
                // Clear rate limiting
                $this->rateLimiter->clear($rateLimitKey);

                // Send welcome email (optional)
                $this->sendWelcomeEmail($userData['email'], $userData['first_name']);

                // Log registration
                log_message('info', "New user registered: {$userData['username']} (Role: {$defaultRole['slug']}) from IP: {$clientIP}");

                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Registration successful! You can now login with your credentials.',
                    'redirect' => '/auth/signin',
                    'csrf_token' => csrf_token(),
                    'csrf_hash' => csrf_hash()
                ]);
            } else {
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
                'message' => 'Registration failed due to server error.',
                'csrf_token' => csrf_token(),
                'csrf_hash' => csrf_hash()
            ])->setStatusCode(500);
        }
    }

    /**
     * Show forgot password page
     */
    public function forgotPassword(): string
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
        $email = $this->request->getPost('email');
        $clientIP = $this->request->getIPAddress();
        $rateLimitKey = 'forgot_password_' . $clientIP;

        // Rate limiting
        if (!$this->rateLimiter->isAllowed($rateLimitKey, 5, 3600)) { // 5 attempts per hour
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Too many password reset attempts. Please try again later.',
                'csrf_token' => csrf_token(),
                'csrf_hash' => csrf_hash()
            ])->setStatusCode(429);
        }

        // Validate email
        if (!$this->validate(['email' => 'required|valid_email'])) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Please provide a valid email address.',
                'csrf_token' => csrf_token(),
                'csrf_hash' => csrf_hash()
            ])->setStatusCode(400);
        }

        // Check if user exists
        $user = $this->userModel->findByUsernameOrEmail($email);

        // Always return success to prevent email enumeration
        $this->rateLimiter->attempt($rateLimitKey, 5, 3600);

        if ($user && $user['is_active']) {
            // Generate reset token
            $resetToken = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Save reset token (you'll need to add this to users table or separate table)
            $this->userModel->update($user['id'], [
                'reset_token' => $resetToken,
                'reset_expires' => $expiry
            ]);

            // Send reset email
            $this->sendPasswordResetEmail($user['email'], $user['first_name'], $resetToken);

            log_message('info', "Password reset requested for: {$email} from IP: {$clientIP}");
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => 'If an account with that email exists, a password reset link has been sent.',
            'csrf_token' => csrf_token(),
            'csrf_hash' => csrf_hash()
        ]);
    }

    /**
     * Show reset password page
     */
    public function resetPassword(string $token): string
    {
        if ($this->session->get('isLoggedIn')) {
            return redirect()->to('/dashboard');
        }

        // Validate token
        $user = $this->userModel->where('reset_token', $token)
            ->where('reset_expires >', date('Y-m-d H:i:s'))
            ->first();

        if (!$user) {
            return redirect()->to('/auth/forgot-password')
                ->with('error', 'Invalid or expired reset token.');
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
        $token = $this->request->getPost('token');
        $password = $this->request->getPost('password');
        $passwordConfirm = $this->request->getPost('password_confirm');

        // Validate input
        $rules = [
            'token' => 'required',
            'password' => 'required|min_length[8]',
            'password_confirm' => 'required|matches[password]'
        ];

        if (!$this->validate($rules)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $this->validator->getErrors(),
                'csrf_token' => csrf_token(),
                'csrf_hash' => csrf_hash()
            ])->setStatusCode(400);
        }

        // Find user by token
        $user = $this->userModel->where('reset_token', $token)
            ->where('reset_expires >', date('Y-m-d H:i:s'))
            ->first();

        if (!$user) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Invalid or expired reset token.',
                'csrf_token' => csrf_token(),
                'csrf_hash' => csrf_hash()
            ])->setStatusCode(400);
        }

        // Update password and clear reset token
        $updateData = [
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'reset_token' => null,
            'reset_expires' => null,
            'login_attempts' => 0,
            'locked_until' => null
        ];

        if ($this->userModel->update($user['id'], $updateData)) {
            log_message('info', "Password reset completed for user: {$user['username']}");

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Password has been reset successfully. You can now login.',
                'redirect' => '/auth/signin',
                'csrf_token' => csrf_token(),
                'csrf_hash' => csrf_hash()
            ]);
        }

        return $this->response->setJSON([
            'success' => false,
            'message' => 'Failed to reset password. Please try again.',
            'csrf_token' => csrf_token(),
            'csrf_hash' => csrf_hash()
        ])->setStatusCode(500);
    }

    /**
     * Logout user and clear session
     */
    public function logout()
    {
        $username = $this->session->get('username');
        $roleSlug = $this->session->get('role_slug');

        // Log logout
        if ($username) {
            log_message('info', "User logout: {$username} (Role: {$roleSlug})");
        }

        // Destroy session
        $this->session->destroy();

        // Clear any remember me tokens (if implemented)
        $this->clearRememberTokens();

        return redirect()->to('/auth/signin')->with('success', 'You have been logged out successfully');
    }

    /**
     * Get CSRF token for AJAX requests
     */
    public function getCsrfToken(): ResponseInterface
    {
        return $this->response->setJSON([
            'csrf_token' => csrf_token(),
            'csrf_hash' => csrf_hash()
        ]);
    }

    /**
     * Check current user permissions (AJAX endpoint)
     */
    public function checkPermissions(): ResponseInterface
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
        $permissions = $this->userModel->getUserPermissions($userId);

        return $this->response->setJSON([
            'success' => true,
            'permissions' => array_column($permissions, 'slug'),
            'role' => [
                'slug' => $this->session->get('role_slug'),
                'name' => $this->session->get('role_name'),
                'level' => $this->session->get('role_level')
            ]
        ]);
    }

    /**
     * Get user info for frontend (AJAX endpoint)
     */
    public function getUserInfo(): ResponseInterface
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

        return $this->response->setJSON([
            'success' => true,
            'user' => [
                'id' => $this->session->get('user_id'),
                'username' => $this->session->get('username'),
                'email' => $this->session->get('email'),
                'full_name' => $this->session->get('full_name'),
                'role' => [
                    'slug' => $this->session->get('role_slug'),
                    'name' => $this->session->get('role_name'),
                    'level' => $this->session->get('role_level')
                ],
                'permissions' => $this->session->get('permissions') ?? []
            ]
        ]);
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

            // Check if role is active
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
        $sessionData = [
            'user_id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'full_name' => $user['first_name'] . ' ' . $user['last_name'],
            'role_id' => $user['role_id'],
            'role_slug' => $user['role_slug'],
            'role_name' => $user['role_name'],
            'role_level' => $user['role_level'],
            'isLoggedIn' => true,
            'login_time' => time(),
            'last_activity' => time(),
            'last_db_activity_update' => time()
        ];

        // Store basic permissions in session for quick access
        $permissions = array_column($user['permissions'], 'slug');
        $sessionData['permissions'] = $permissions;

        $this->session->set($sessionData);
    }

    /**
     * Send welcome email to new user
     */
    private function sendWelcomeEmail(string $email, string $firstName): void
    {
        try {
            // Implement welcome email sending
            // This is a placeholder - implement based on your email service
            $emailService = service('email');

            $subject = 'Welcome to Our Platform';
            $message = view('emails/welcome', [
                'first_name' => $firstName,
                'login_url' => base_url('/auth/signin')
            ]);

            $emailService->setTo($email)
                ->setSubject($subject)
                ->setMessage($message)
                ->send();

            log_message('info', "Welcome email sent to: {$email}");
        } catch (\Exception $e) {
            log_message('error', "Failed to send welcome email to {$email}: " . $e->getMessage());
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
            $message = view('emails/password_reset', [
                'first_name' => $firstName,
                'reset_url' => $resetUrl,
                'expires_in' => '1 hour'
            ]);

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
     * Clear remember me tokens (if implemented)
     */
    private function clearRememberTokens(): void
    {
        // Implement remember token clearing if you have this feature
        // This would involve database cleanup of remember tokens
        $userId = $this->session->get('user_id');
        if ($userId) {
            // Clear remember tokens from database
            // $this->userModel->clearRememberTokens($userId);
        }
    }
}