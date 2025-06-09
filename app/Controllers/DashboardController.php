<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

class DashboardController extends BaseController
{
    protected $userModel;
    protected $session;

    public function __construct()
    {
        $this->userModel = model('UserModel');
        $this->session = session();
        helper(['url', 'form']);
    }

    /**
     * Main dashboard - redirect berdasarkan role
     * Admin dan Technician → Unified Dashboard
     * Customer → Customer Dashboard
     */
    public function index(): string|\CodeIgniter\HTTP\RedirectResponse
    {
        $role = $this->session->get('role');
        $userId = $this->session->get('user_id');

        switch ($role) {
            case 'admin':
            case 'technician':
                // Admin dan Technician menggunakan dashboard yang sama
                return $this->unifiedDashboard();

            case 'customer':
                // Customer memiliki dashboard terpisah
                return $this->customerDashboard();

            default:
                $this->session->destroy();
                return redirect()->to('/auth/signin')->with('error', 'Invalid user role. Please login again.');
        }
    }

    /**
     * Unified Dashboard untuk Admin dan Technician
     */
    private function unifiedDashboard(): string
    {
        $userId = $this->session->get('user_id');
        $role = $this->session->get('role');

        $data = [
            'title' => ucfirst($role) . ' Dashboard',
            'user' => $this->userModel->find($userId),
            'role' => $role,
            'stats' => $this->getUnifiedStats($userId, $role),
            'recent_activities' => $this->getRecentActivities($userId, 10),
            'notifications' => $this->getNotifications($userId),
            'charts_data' => $this->getChartsData($role),
            'quick_actions' => $this->getQuickActions($role),
            'system_info' => $this->getSystemInfo(),
            'work_summary' => $this->getWorkSummary($userId, $role)
        ];

        return view('dashboard/unified', $data);
    }

    /**
     * Customer Dashboard (terpisah)
     */
    private function customerDashboard(): string
    {
        $userId = $this->session->get('user_id');

        $data = [
            'title' => 'Customer Dashboard',
            'user' => $this->userModel->find($userId),
            'stats' => $this->getCustomerStats($userId),
            'recent_activities' => $this->getRecentActivities($userId, 5),
            'notifications' => $this->getNotifications($userId),
            'services' => $this->getCustomerServices($userId),
            'support_tickets' => $this->getSupportTickets($userId)
        ];

        return view('dashboard/customer', $data);
    }

    /**
     * Get Unified Stats untuk Admin dan Technician
     */
    private function getUnifiedStats(int $userId, string $role): array
    {
        $baseStats = [
            'users' => $this->getUserStats(),
            'system' => $this->getSystemStats()
        ];

        if ($role === 'admin') {
            // Admin melihat semua statistik sistem
            $baseStats['admin'] = [
                'total_users' => $this->userModel->countAll(),
                'active_users' => $this->userModel->where('is_active', 1)->countAllResults(),
                'new_users_today' => $this->userModel->where('DATE(created_at)', date('Y-m-d'))->countAllResults(),
                'new_users_week' => $this->userModel->where('created_at >=', date('Y-m-d', strtotime('-7 days')))->countAllResults(),
                'security_alerts' => $this->getSecurityAlerts(),
                'system_health' => $this->getSystemHealth()
            ];
        }

        if ($role === 'technician' || $role === 'admin') {
            // Admin dan Technician melihat work statistics
            $baseStats['work'] = [
                'total_orders' => $this->getTotalOrders($userId, $role),
                'pending_orders' => $this->getPendingOrders($userId, $role),
                'completed_today' => $this->getCompletedToday($userId, $role),
                'in_progress' => $this->getInProgressOrders($userId, $role),
                'overdue_orders' => $this->getOverdueOrders($userId, $role),
                'completion_rate' => $this->getCompletionRate($userId, $role)
            ];
        }

        return $baseStats;
    }

    /**
     * Get Customer Stats
     */
    private function getCustomerStats(int $userId): array
    {
        return [
            'active_services' => $this->getActiveServices($userId),
            'support_tickets' => $this->getSupportTicketsCount($userId),
            'pending_requests' => $this->getPendingRequests($userId),
            'completed_services' => $this->getCompletedServices($userId)
        ];
    }

    /**
     * Get Charts Data berdasarkan role
     */
    private function getChartsData(string $role): array
    {
        $baseCharts = [];

        if ($role === 'admin') {
            $baseCharts['user_registration'] = $this->getUserRegistrationChart();
            $baseCharts['user_roles'] = $this->getUserRolesChart();
            $baseCharts['system_performance'] = $this->getSystemPerformanceChart();
        }

        if ($role === 'technician' || $role === 'admin') {
            $baseCharts['work_completion'] = $this->getWorkCompletionChart();
            $baseCharts['order_status'] = $this->getOrderStatusChart();
        }

        return $baseCharts;
    }

    /**
     * Get Quick Actions berdasarkan role
     */
    private function getQuickActions(string $role): array
    {
        $actions = [];

        if ($role === 'admin') {
            $actions = [
                'create_user' => [
                    'title' => 'Create User',
                    'icon' => 'fas fa-user-plus',
                    'url' => '/admin/users/create',
                    'class' => 'btn-primary'
                ],
                'system_backup' => [
                    'title' => 'System Backup',
                    'icon' => 'fas fa-download',
                    'action' => 'backupSystem',
                    'class' => 'btn-success'
                ],
                'clear_cache' => [
                    'title' => 'Clear Cache',
                    'icon' => 'fas fa-broom',
                    'action' => 'clearCache',
                    'class' => 'btn-warning'
                ],
                'view_reports' => [
                    'title' => 'View Reports',
                    'icon' => 'fas fa-chart-bar',
                    'url' => '/admin/reports',
                    'class' => 'btn-info'
                ]
            ];
        }

        if ($role === 'technician') {
            $actions = [
                'view_orders' => [
                    'title' => 'My Orders',
                    'icon' => 'fas fa-clipboard-list',
                    'url' => '/technician/orders',
                    'class' => 'btn-primary'
                ],
                'create_report' => [
                    'title' => 'Create Report',
                    'icon' => 'fas fa-file-alt',
                    'url' => '/technician/reports/create',
                    'class' => 'btn-success'
                ],
                'view_schedule' => [
                    'title' => 'My Schedule',
                    'icon' => 'fas fa-calendar-alt',
                    'url' => '/technician/schedule',
                    'class' => 'btn-info'
                ]
            ];
        }

        return $actions;
    }

    /**
     * Get Work Summary
     */
    private function getWorkSummary(int $userId, string $role): array
    {
        if ($role === 'customer') {
            return [];
        }

        return [
            'today_tasks' => $this->getTodayTasks($userId, $role),
            'upcoming_deadlines' => $this->getUpcomingDeadlines($userId, $role),
            'recent_completions' => $this->getRecentCompletions($userId, $role)
        ];
    }

    /**
     * AJAX Endpoints
     */
    public function ajaxStats(): ResponseInterface
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(404);
        }

        $role = $this->session->get('role');
        $userId = $this->session->get('user_id');

        $stats = $this->getUnifiedStats($userId, $role);

        return $this->response->setJSON([
            'success' => true,
            'data' => $stats
        ]);
    }

    public function quickAction(): ResponseInterface
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(404);
        }

        $action = $this->request->getPost('action');
        $role = $this->session->get('role');

        // Hanya admin yang bisa melakukan sistem actions
        if ($role !== 'admin' && in_array($action, ['clear_cache', 'backup_system'])) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Admin access required'
            ]);
        }

        switch ($action) {
            case 'clear_cache':
                return $this->clearCache();
            case 'backup_system':
                return $this->backupSystem();
            default:
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Invalid action'
                ]);
        }
    }

    /**
     * Helper Methods (implementasi mock data untuk saat ini)
     */
    private function getUserStats(): array
    {
        return [
            'total' => $this->userModel->countAll(),
            'active' => $this->userModel->where('is_active', 1)->countAllResults(),
            'admins' => $this->userModel->where('role', 'admin')->countAllResults(),
            'technicians' => $this->userModel->where('role', 'technician')->countAllResults(),
            'customers' => $this->userModel->where('role', 'customer')->countAllResults()
        ];
    }

    private function getSystemStats(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'ci_version' => \CodeIgniter\CodeIgniter::CI_VERSION,
            'memory_usage' => $this->formatBytes(memory_get_usage(true)),
            'server_time' => date('Y-m-d H:i:s')
        ];
    }

    private function getRecentActivities(int $userId, int $limit): array
    {
        // Mock data - implement dengan activity logging system
        return [
            [
                'user' => $this->session->get('username'),
                'action' => 'Dashboard accessed',
                'timestamp' => date('Y-m-d H:i:s'),
                'type' => 'info'
            ]
        ];
    }

    private function getNotifications(int $userId): array
    {
        // Mock data - implement dengan notification system
        return [];
    }

    private function getSystemInfo(): array
    {
        return [
            'environment' => ENVIRONMENT,
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'
        ];
    }

    // Mock implementations untuk work-related methods
    private function getTotalOrders(int $userId, string $role): int { return rand(50, 200); }
    private function getPendingOrders(int $userId, string $role): int { return rand(5, 20); }
    private function getCompletedToday(int $userId, string $role): int { return rand(2, 10); }
    private function getInProgressOrders(int $userId, string $role): int { return rand(3, 15); }
    private function getOverdueOrders(int $userId, string $role): int { return rand(0, 5); }
    private function getCompletionRate(int $userId, string $role): float { return round(rand(75, 95) + rand(0, 99)/100, 2); }

    // Mock implementations untuk customer methods
    private function getActiveServices(int $userId): int { return rand(1, 5); }
    private function getSupportTicketsCount(int $userId): int { return rand(0, 10); }
    private function getPendingRequests(int $userId): int { return rand(0, 3); }
    private function getCompletedServices(int $userId): int { return rand(5, 20); }

    // Chart methods (return mock data)
    private function getUserRegistrationChart(): array
    {
        $data = [];
        for ($i = 29; $i >= 0; $i--) {
            $data[] = [
                'date' => date('Y-m-d', strtotime("-$i days")),
                'count' => rand(0, 5)
            ];
        }
        return $data;
    }

    private function getUserRolesChart(): array
    {
        return [
            'admin' => $this->userModel->where('role', 'admin')->countAllResults(),
            'technician' => $this->userModel->where('role', 'technician')->countAllResults(),
            'customer' => $this->userModel->where('role', 'customer')->countAllResults()
        ];
    }

    private function getWorkCompletionChart(): array { return []; }
    private function getOrderStatusChart(): array { return []; }
    private function getSystemPerformanceChart(): array { return []; }

    // Work summary methods
    private function getTodayTasks(int $userId, string $role): array { return []; }
    private function getUpcomingDeadlines(int $userId, string $role): array { return []; }
    private function getRecentCompletions(int $userId, string $role): array { return []; }

    // Security and system methods
    private function getSecurityAlerts(): int { return rand(0, 3); }
    private function getSystemHealth(): string { return 'Good'; }

    // Customer specific methods
    private function getCustomerServices(int $userId): array { return []; }
    private function getSupportTickets(int $userId): array { return []; }

    // Utility methods
    private function formatBytes(int $size, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }
        return round($size, $precision) . ' ' . $units[$i];
    }

    private function clearCache(): ResponseInterface
    {
        try {
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

    private function backupSystem(): ResponseInterface
    {
        try {
            // Implement backup logic
            return $this->response->setJSON([
                'success' => true,
                'message' => 'System backup initiated'
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to backup system'
            ]);
        }
    }
}