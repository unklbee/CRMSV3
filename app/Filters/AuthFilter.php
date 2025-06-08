<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    // Session timeout dalam detik (30 menit)
    private const SESSION_TIMEOUT = 1800;

    public function before(RequestInterface $request, $arguments = null)
    {
        // Check if user is logged in dengan key yang konsisten
        if (!session()->get('isLoggedIn')) {
            return $this->redirectToLogin('Please login first');
        }

        // Check session timeout
        $lastActivity = session()->get('last_activity');
        if ($lastActivity && (time() - $lastActivity) > self::SESSION_TIMEOUT) {
            session()->destroy();
            return $this->redirectToLogin('Session expired. Please login again');
        }

        // Update last activity time
        session()->set('last_activity', time());

        // Check if user role is valid
        $role = session()->get('role');
        if (!in_array($role, ['admin', 'technician', 'customer'])) {
            // Log unauthorized access attempt
            log_message('warning', 'Unauthorized access attempt by user: ' . session()->get('username') . ' with role: ' . $role);

            return $this->redirectToLogin('Invalid user role');
        }

        // Additional check untuk admin-only routes
        if ($this->isAdminOnlyRoute($request) && $role !== 'admin') {
            return redirect()->to('/dashboard')->with('error', 'Admin access required');
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Add security headers
        $response->setHeader('X-Frame-Options', 'DENY')
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setHeader('X-XSS-Protection', '1; mode=block')
            ->setHeader('Referrer-Policy', 'strict-origin-when-cross-origin');

        return $response;
    }

    /**
     * Helper method untuk redirect ke login
     */
    private function redirectToLogin(string $message): ResponseInterface
    {
        // Jika request adalah AJAX, return JSON response
        if (service('request')->isAJAX()) {
            return response()->setJSON([
                'success' => false,
                'message' => $message,
                'redirect' => '/auth/signin'
            ])->setStatusCode(401);
        }

        return redirect()->to('/auth/signin')->with('error', $message);
    }

    /**
     * Check apakah route hanya untuk admin
     */
    private function isAdminOnlyRoute(RequestInterface $request): bool
    {
        $uri = $request->getUri()->getPath();

        $adminOnlyPaths = [
            '/admin/users',
            '/admin/settings',
            '/admin/audit',
            '/admin/maintenance'
        ];

        foreach ($adminOnlyPaths as $path) {
            if (str_starts_with($uri, $path)) {
                return true;
            }
        }

        return false;
    }
}