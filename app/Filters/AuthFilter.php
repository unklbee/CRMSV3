<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    private const SESSION_TIMEOUT = 1800; // 30 minutes

    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();

        // Check if user is logged in
        if (!$session->get('isLoggedIn')) {
            return $this->redirectToLogin('Please login first');
        }

        // Check session timeout
        $lastActivity = $session->get('last_activity');
        if ($lastActivity && (time() - $lastActivity) > self::SESSION_TIMEOUT) {
            $session->destroy();
            return $this->redirectToLogin('Session expired. Please login again');
        }

        // Update last activity
        $session->set('last_activity', time());

        // Validate user and role exist
        $userId = $session->get('user_id');
        $roleSlug = $session->get('role_slug');

        if (!$userId || !$roleSlug) {
            $session->destroy();
            return $this->redirectToLogin('Invalid session data');
        }

        // Check if user account is still active and role is valid
        if (!$this->validateUserSession($userId, $roleSlug)) {
            $session->destroy();
            return $this->redirectToLogin('Account access has been revoked');
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Add security headers
        return $response->setHeader('X-Frame-Options', 'DENY')
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setHeader('X-XSS-Protection', '1; mode=block')
            ->setHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    private function redirectToLogin(string $message): ResponseInterface
    {
        if (service('request')->isAJAX()) {
            return response()->setJSON([
                'success' => false,
                'message' => $message,
                'redirect' => '/auth/signin'
            ])->setStatusCode(401);
        }

        return redirect()->to('/auth/signin')->with('error', $message);
    }

    private function validateUserSession(int $userId, string $roleSlug): bool
    {
        $userModel = model('UserModel');
        $user = $userModel->findWithRole($userId);

        if (!$user || !$user['is_active'] || $user['role_slug'] !== $roleSlug) {
            return false;
        }

        // Update last activity in database occasionally (every 5 minutes)
        $lastDbUpdate = session()->get('last_db_activity_update') ?? 0;
        if ((time() - $lastDbUpdate) > 300) {
            $userModel->update($userId, ['last_activity' => date('Y-m-d H:i:s')]);
            session()->set('last_db_activity_update', time());
        }

        return true;
    }
}

/**
 * Permission-based Filter
 */
class PermissionFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();

        if (!$session->get('isLoggedIn')) {
            return $this->redirectToLogin('Authentication required');
        }

        $userId = $session->get('user_id');
        $requiredPermission = $arguments[0] ?? null;

        if (!$requiredPermission) {
            return redirect()->back()->with('error', 'Permission configuration error');
        }

        $userModel = model('UserModel');
        if (!$userModel->hasPermission($userId, $requiredPermission)) {
            log_message('warning', "Permission denied for user {$userId}: {$requiredPermission}");

            if ($request->isAJAX()) {
                return response()->setJSON([
                    'success' => false,
                    'message' => 'You do not have permission to access this resource',
                    'required_permission' => $requiredPermission
                ])->setStatusCode(403);
            }

            return redirect()->to('/dashboard')->with('error', 'You do not have permission to access this resource');
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }

    private function redirectToLogin(string $message): ResponseInterface
    {
        if (service('request')->isAJAX()) {
            return response()->setJSON([
                'success' => false,
                'message' => $message,
                'redirect' => '/auth/signin'
            ])->setStatusCode(401);
        }

        return redirect()->to('/auth/signin')->with('error', $message);
    }
}

/**
 * Role-based Filter (backward compatibility)
 */
class RoleFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();

        if (!$session->get('isLoggedIn')) {
            return $this->redirectToLogin('Authentication required');
        }

        $userRoleSlug = $session->get('role_slug');
        $allowedRoles = $arguments ?? [];

        if (empty($allowedRoles)) {
            return null; // No restrictions
        }

        if (!in_array($userRoleSlug, $allowedRoles)) {
            log_message('warning', "Role access denied for user role '{$userRoleSlug}' to restricted resource");

            if ($request->isAJAX()) {
                return response()->setJSON([
                    'success' => false,
                    'message' => 'Insufficient role permissions',
                    'required_roles' => $allowedRoles,
                    'user_role' => $userRoleSlug
                ])->setStatusCode(403);
            }

            return redirect()->to('/dashboard')->with('error', 'You do not have the required role to access this resource');
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }

    private function redirectToLogin(string $message): ResponseInterface
    {
        if (service('request')->isAJAX()) {
            return response()->setJSON([
                'success' => false,
                'message' => $message,
                'redirect' => '/auth/signin'
            ])->setStatusCode(401);
        }

        return redirect()->to('/auth/signin')->with('error', $message);
    }
}

/**
 * Admin Filter (uses permission-based check)
 */
class AdminFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();

        if (!$session->get('isLoggedIn')) {
            return $this->redirectToLogin('Authentication required');
        }

        $userId = $session->get('user_id');
        $userModel = model('UserModel');

        // Check if user has admin dashboard permission
        if (!$userModel->hasPermission($userId, 'dashboard.admin')) {
            log_message('warning', "Admin access denied for user {$userId}");

            if ($request->isAJAX()) {
                return response()->setJSON([
                    'success' => false,
                    'message' => 'Admin access required'
                ])->setStatusCode(403);
            }

            return redirect()->to('/dashboard')->with('error', 'Admin access required');
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }

    private function redirectToLogin(string $message): ResponseInterface
    {
        if (service('request')->isAJAX()) {
            return response()->setJSON([
                'success' => false,
                'message' => $message,
                'redirect' => '/auth/signin'
            ])->setStatusCode(401);
        }

        return redirect()->to('/auth/signin')->with('error', $message);
    }
}

/**
 * Technician Filter
 */
class TechnicianFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();

        if (!$session->get('isLoggedIn')) {
            return $this->redirectToLogin('Authentication required');
        }

        $userId = $session->get('user_id');
        $userModel = model('UserModel');

        // Check if user has technician dashboard permission
        if (!$userModel->hasPermission($userId, 'dashboard.technician')) {
            log_message('warning', "Technician access denied for user {$userId}");

            if ($request->isAJAX()) {
                return response()->setJSON([
                    'success' => false,
                    'message' => 'Technician access required'
                ])->setStatusCode(403);
            }

            return redirect()->to('/dashboard')->with('error', 'Technician access required');
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }

    private function redirectToLogin(string $message): ResponseInterface
    {
        if (service('request')->isAJAX()) {
            return response()->setJSON([
                'success' => false,
                'message' => $message,
                'redirect' => '/auth/signin'
            ])->setStatusCode(401);
        }

        return redirect()->to('/auth/signin')->with('error', $message);
    }
}

/**
 * Customer Filter
 */
class CustomerFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();

        if (!$session->get('isLoggedIn')) {
            return $this->redirectToLogin('Authentication required');
        }

        $userId = $session->get('user_id');
        $userModel = model('UserModel');

        // Check if user has customer dashboard permission
        if (!$userModel->hasPermission($userId, 'dashboard.customer')) {
            log_message('warning', "Customer access denied for user {$userId}");

            if ($request->isAJAX()) {
                return response()->setJSON([
                    'success' => false,
                    'message' => 'Customer access required'
                ])->setStatusCode(403);
            }

            return redirect()->to('/dashboard')->with('error', 'Customer access required');
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }

    private function redirectToLogin(string $message): ResponseInterface
    {
        if (service('request')->isAJAX()) {
            return response()->setJSON([
                'success' => false,
                'message' => $message,
                'redirect' => '/auth/signin'
            ])->setStatusCode(401);
        }

        return redirect()->to('/auth/signin')->with('error', $message);
    }
}