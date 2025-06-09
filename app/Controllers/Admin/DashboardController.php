<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

class DashboardController extends BaseController
{
    protected $userModel;
    protected $session;
    protected $validation;

    public function __construct()
    {
        $this->userModel = model('UserModel');
        $this->session = session();
        $this->validation = \Config\Services::validation();
        helper(['url', 'form', 'text']);
    }

    /**
     * Admin Dashboard Main Page
     */
    public function index(): string
    {
        // Check if user is admin
        if ($this->session->get('role') !== 'admin') {
            return redirect()->to('/dashboard')->with('error', 'Access denied');
        }

        // Get dashboard data
        $data = [
            'title' => 'Admin Dashboard',
            'user' => $this->userModel->find($this->session->get('user_id')),
            'stats' => $this->getDashboardStats(),
            'recent_users' => $this->getRecentUsers(5),
            'system_info' => $this->getSystemInfo(),
            'recent_activities' => $this->getRecentActivities(10),
            'charts_data' => $this->getChartsData()
        ];

        return view('admin/dashboard/index', $data);
    }

    /**
     * Get comprehensive dashboard statistics
     */
    private function getDashboardStats(): array
    {
        $userStats = $this->getUserStats();
        $systemStats = $this->getSystemStats();
        $securityStats = $this->getSecurityStats();

        return [
            'users' => $userStats,
            'system' => $systemStats,
            'security' => $securityStats,
            'overview' => [
                'total_users' => $userStats['total'],
                'active_users' => $userStats['active'],
                'new_users_today' => $userStats['new_today'],
                'new_users_week' => $userStats['new_week']
            ]
        ];
    }

    /**
     * Get user statistics
     */
    private function getUserStats(): array
    {
        $userModel = $this->userModel;

        return [
            'total' => $userModel->countAll(),
            'active' => $userModel->where('is_active', 1)->countAllResults(),
            'inactive' => $userModel->where('is_active', 0)->countAllResults(),
            'admins' => $userModel->where('role', 'admin')->countAllResults(),
            'technicians' => $userModel->where('role', 'technician')->countAllResults(),
            'customers' => $userModel->where('role', 'customer')->countAllResults(),
            'new_today' => $userModel->where('DATE(created_at)', date('Y-m-d'))->countAllResults(),
            'new_week' => $userModel->where('created_at >=', date('Y-m-d', strtotime('-7 days')))->countAllResults(),
            'new_month' => $userModel->where('created_at >=', date('Y-m-d', strtotime('-30 days')))->countAllResults()
        ];
    }

    /**
     * Get system statistics
     */
    private function getSystemStats(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'ci_version' => \CodeIgniter\CodeIgniter::CI_VERSION,
            'server_time' => date('Y-m-d H:i:s'),
            'uptime' => $this->getServerUptime(),
            'memory_usage' => $this->formatBytes(memory_get_usage(true)),
            'memory_limit' => ini_get('memory_limit'),
            'disk_usage' => $this->getDiskUsage()
        ];
    }

    /**
     * Get security statistics
     */
    private function getSecurityStats(): array
    {
        // You can implement this based on your logging system
        return [
            'failed_logins_today' => $this->getFailedLoginsCount('today'),
            'failed_logins_week' => $this->getFailedLoginsCount('week'),
            'locked_accounts' => 0, // Implement based on your lockout system
            'suspicious_activities' => 0 // Implement based on your security monitoring
        ];
    }

    /**
     * Get recent users
     */
    private function getRecentUsers(int $limit = 5): array
    {
        return $this->userModel
            ->select('id, username, email, role, created_at, is_active, last_login')
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->findAll();
    }

    /**
     * Get recent activities (you need to implement activity logging)
     */
    private function getRecentActivities(int $limit = 10): array
    {
        // Implement this based on your activity logging system
        // For now, return mock data
        return [
            [
                'user' => 'admin',
                'action' => 'User created',
                'description' => 'New user "john_doe" created',
                'timestamp' => date('Y-m-d H:i:s', strtotime('-5 minutes')),
                'type' => 'info'
            ],
            [
                'user' => 'admin',
                'action' => 'Settings updated',
                'description' => 'System settings modified',
                'timestamp' => date('Y-m-d H:i:s', strtotime('-1 hour')),
                'type' => 'warning'
            ]
        ];
    }

    /**
     * Get data for dashboard charts
     */
    private function getChartsData(): array
    {
        return [
            'user_registration' => $this->getUserRegistrationChart(),
            'user_roles' => $this->getUserRolesChart(),
            'login_activity' => $this->getLoginActivityChart()
        ];
    }

    /**
     * Get user registration chart data (last 30 days)
     */
    private function getUserRegistrationChart(): array
    {
        $data = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $count = $this->userModel->where('DATE(created_at)', $date)->countAllResults();
            $data[] = [
                'date' => $date,
                'count' => $count
            ];
        }
        return $data;
    }

    /**
     * Get user roles distribution
     */
    private function getUserRolesChart(): array
    {
        return [
            'admin' => $this->userModel->where('role', 'admin')->countAllResults(),
            'technician' => $this->userModel->where('role', 'technician')->countAllResults(),
            'customer' => $this->userModel->where('role', 'customer')->countAllResults()
        ];
    }

    /**
     * Get login activity chart (last 7 days)
     */
    private function getLoginActivityChart(): array
    {
        // This would require a login_logs table
        // For now, return mock data
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $data[] = [
                'date' => $date,
                'logins' => rand(10, 50) // Mock data
            ];
        }
        return $data;
    }

    /**
     * Get system info
     */
    private function getSystemInfo(): array
    {
        return [
            'environment' => ENVIRONMENT,
            'php_version' => PHP_VERSION,
            'codeigniter_version' => \CodeIgniter\CodeIgniter::CI_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'database_version' => $this->getDatabaseVersion(),
            'timezone' => date_default_timezone_get()
        ];
    }

    /**
     * Helper methods
     */
    private function getServerUptime(): string
    {
        if (function_exists('sys_getloadavg')) {
            $uptime = shell_exec('uptime');
            return $uptime ? trim($uptime) : 'Unknown';
        }
        return 'Unknown';
    }

    private function formatBytes(int $size, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }
        return round($size, $precision) . ' ' . $units[$i];
    }

    private function getDiskUsage(): array
    {
        $total = disk_total_space('.');
        $free = disk_free_space('.');
        $used = $total - $free;

        return [
            'total' => $this->formatBytes($total),
            'used' => $this->formatBytes($used),
            'free' => $this->formatBytes($free),
            'percentage' => round(($used / $total) * 100, 2)
        ];
    }

    private function getDatabaseVersion(): string
    {
        try {
            $db = \Config\Database::connect();
            return $db->getVersion();
        } catch (\Exception $e) {
            return 'Unknown';
        }
    }

    private function getFailedLoginsCount(string $period): int
    {
        // Implement based on your logging system
        // This would require a security_logs table
        return 0;
    }

    /**
     * AJAX endpoint for real-time dashboard updates
     */
    public function ajaxStats(): ResponseInterface
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(404);
        }

        $stats = $this->getDashboardStats();

        return $this->response->setJSON([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Quick actions endpoint
     */
    public function quickAction(): ResponseInterface
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(404);
        }

        $action = $this->request->getPost('action');

        switch ($action) {
            case 'clear_cache':
                return $this->clearCache();

            case 'backup_database':
                return $this->backupDatabase();

            default:
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Invalid action'
                ]);
        }
    }

    /**
     * Clear cache action
     */
    private function clearCache(): ResponseInterface
    {
        try {
            // Clear CodeIgniter cache
            if (function_exists('opcache_reset')) {
                opcache_reset();
            }

            // Clear any custom cache
            cache()->clean();

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Cache cleared successfully'
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to clear cache'
            ]);
        }
    }

    /**
     * Database backup action
     */
    private function backupDatabase(): ResponseInterface
    {
        try {
            // Implement database backup logic
            // Implement database backup logic
            // This is a placeholder
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Database backup initiated'
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to backup database'
            ]);
        }
    }
}