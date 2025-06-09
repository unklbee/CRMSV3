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
     */
    public function index()
    {
        // Debug: Log session data
        log_message('debug', 'Dashboard access - Session data: ' . json_encode([
                'isLoggedIn' => $this->session->get('isLoggedIn'),
                'user_id' => $this->session->get('user_id'),
                'role_slug' => $this->session->get('role_slug'),
                'username' => $this->session->get('username')
            ]));

        $roleSlug = $this->session->get('role_slug');
        $userId = $this->session->get('user_id');

        if (!$userId || !$roleSlug) {
            log_message('warning', 'Dashboard access without proper session data');
            $this->session->destroy();
            return redirect()->to('/auth/signin')->with('error', 'Session expired. Please login again.');
        }

        // Role-based dashboard routing
        switch ($roleSlug) {
            case 'manager':
            case 'admin':
                return $this->adminDashboard();

            // Manager uses same dashboard as admin

            case 'support':
            case 'technician':
                return $this->technicianDashboard();

            // Support uses same dashboard as technician

            case 'customer':
                return $this->customerDashboard();

            default:
                log_message('error', "Unknown role: {$roleSlug} for user: {$userId}");
                $this->session->destroy();
                return redirect()->to('/auth/signin')->with('error', 'Invalid user role. Please contact administrator.');
        }
    }

    /**
     * Admin Dashboard
     */
    public function adminDashboard(): string
    {
        $userId = $this->session->get('user_id');
        $role = $this->session->get('role_slug');
        $first_name = $this->session->get('first_name');
        $email = $this->session->get('email');

        $data = [
            'title' => ucfirst($role) . ' Dashboard',
            'page_title' => 'Dashboard',
            'user' => [
                'id' => $userId,
                'name' => $this->session->get('full_name'),
                'role' => $role,
                'first_name' => $first_name,
                'email' => $email,
                'username' => $this->session->get('username')
            ],
            'stats' => $this->getAdminStats($userId, $role),
            'charts' => $this->getAdminCharts(),
            'recent_activities' => $this->getRecentActivities(),
            'system_info' => $this->getSystemInfo()
        ];

        return view('dashboard/admin', $data);
    }

    /**
     * Technician Dashboard
     */
    public function technicianDashboard(): string
    {
        $userId = $this->session->get('user_id');
        $role = $this->session->get('role_slug');

        $data = [
            'title' => ucfirst($role) . ' Dashboard',
            'page_title' => 'Dashboard',
            'user' => [
                'id' => $userId,
                'name' => $this->session->get('full_name'),
                'role' => $role,
                'username' => $this->session->get('username')
            ],
            'stats' => $this->getTechnicianStats($userId, $role),
            'assigned_orders' => $this->getAssignedOrders($userId),
            'work_summary' => $this->getWorkSummary($userId),
            'upcoming_tasks' => $this->getUpcomingTasks($userId)
        ];

        return view('dashboard/technician', $data);
    }

    /**
     * Customer Dashboard
     */
    public function customerDashboard(): string
    {
        $userId = $this->session->get('user_id');

        $data = [
            'title' => 'Customer Dashboard',
            'page_title' => 'My Dashboard',
            'user' => [
                'id' => $userId,
                'name' => $this->session->get('full_name'),
                'role' => 'customer',
                'username' => $this->session->get('username'),
                'created_at' =>  $this->session->get('created_at')
            ],
            'stats' => $this->getCustomerStats($userId),
            'active_services' => $this->getActiveServices($userId),
            'recent_orders' => $this->getRecentOrders($userId),
            'support_tickets' => $this->getSupportTickets($userId)
        ];

        return view('dashboard/customer', $data);
    }

    /**
     * Get dashboard stats for API
     */
    public function getStats(): ResponseInterface
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(404);
        }

        $userId = $this->session->get('user_id');
        $role = $this->session->get('role_slug');

        $stats = match($role) {
            'admin', 'manager' => $this->getAdminStats($userId, $role),
            'technician', 'support' => $this->getTechnicianStats($userId, $role),
            'customer' => $this->getCustomerStats($userId),
            default => []
        };

        return $this->response->setJSON([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Get chart data for dashboard
     */
    public function getChartData(string $chartType): ResponseInterface
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(404);
        }

        $userId = $this->session->get('user_id');
        $role = $this->session->get('role_slug');

        $data = match($chartType) {
            'user_registration' => $this->getUserRegistrationChart(),
            'user_roles' => $this->getUserRolesChart(),
            'order_status' => $this->getOrderStatusChart($userId, $role),
            'work_completion' => $this->getWorkCompletionChart($userId, $role),
            'service_requests' => $this->getServiceRequestsChart($userId, $role),
            default => []
        };

        return $this->response->setJSON([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Refresh dashboard data
     */
    public function refreshData(): ResponseInterface
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(404);
        }

        // Update last activity
        $userId = $this->session->get('user_id');
        if ($userId) {
            $this->userModel->updateLastActivity($userId);
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Dashboard data refreshed',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    // ============================================================================
    // PRIVATE HELPER METHODS
    // ============================================================================

    /**
     * Get admin statistics
     */
    private function getAdminStats(int $userId, string $role): array
    {
        // Mock data - replace with actual database queries
        return [
            'total_users' => $this->userModel->countAllResults(),
            'active_users' => $this->userModel->where('is_active', 1)->countAllResults(),
            'total_orders' => $this->getTotalOrders($userId, $role),
            'pending_orders' => $this->getPendingOrders($userId, $role),
            'completed_today' => $this->getCompletedToday($userId, $role),
            'revenue_today' => $this->getRevenueToday(),
            'system_health' => $this->getSystemHealth(),
            'security_alerts' => $this->getSecurityAlerts()
        ];
    }

    /**
     * Get technician statistics
     */
    private function getTechnicianStats(int $userId, string $role): array
    {
        return [
            'assigned_orders' => $this->getAssignedOrdersCount($userId),
            'pending_orders' => $this->getPendingOrders($userId, $role),
            'completed_today' => $this->getCompletedToday($userId, $role),
            'in_progress' => $this->getInProgressOrders($userId, $role),
            'overdue_orders' => $this->getOverdueOrders($userId, $role),
            'completion_rate' => $this->getCompletionRate($userId, $role),
            'avg_resolution_time' => $this->getAvgResolutionTime($userId),
            'customer_rating' => $this->getCustomerRating($userId)
        ];
    }

    /**
     * Get customer statistics
     */
    private function getCustomerStats(int $userId): array
    {
        return [
            'active_services' => $this->getActiveServicesCount($userId),
            'pending_requests' => $this->getPendingRequests($userId),
            'completed_services' => $this->getCompletedServices($userId),
            'support_tickets' => $this->getSupportTicketsCount($userId),
            'total_spent' => $this->getTotalSpent($userId),
            'last_service_date' => $this->getLastServiceDate($userId)
        ];
    }

    /**
     * Get admin charts data
     */
    private function getAdminCharts(): array
    {
        return [
            'user_registration' => $this->getUserRegistrationChart(),
            'user_roles' => $this->getUserRolesChart(),
            'order_status' => $this->getOrderStatusChart(),
            'revenue_trend' => $this->getRevenueTrendChart(),
            'system_performance' => $this->getSystemPerformanceChart()
        ];
    }

    // Mock implementations (replace with actual database queries)
    private function getTotalOrders(int $userId, string $role): int { return rand(100, 500); }
    private function getPendingOrders(int $userId, string $role): int { return rand(5, 25); }
    private function getCompletedToday(int $userId, string $role): int { return rand(2, 15); }
    private function getRevenueToday(): float { return rand(1000, 5000); }
    private function getSystemHealth(): string { return 'Good'; }
    private function getSecurityAlerts(): int { return rand(0, 3); }

    private function getAssignedOrdersCount(int $userId): int { return rand(5, 20); }
    private function getInProgressOrders(int $userId, string $role): int { return rand(3, 12); }
    private function getOverdueOrders(int $userId, string $role): int { return rand(0, 5); }
    private function getCompletionRate(int $userId, string $role): float { return round(rand(75, 98) + rand(0, 99)/100, 2); }
    private function getAvgResolutionTime(int $userId): float { return round(rand(2, 8) + rand(0, 99)/100, 1); }
    private function getCustomerRating(int $userId): float { return round(rand(4, 5) + rand(0, 99)/100, 1); }

    private function getActiveServicesCount(int $userId): int { return rand(1, 8); }
    private function getPendingRequests(int $userId): int { return rand(0, 5); }
    private function getCompletedServices(int $userId): int { return rand(10, 50); }
    private function getSupportTicketsCount(int $userId): int { return rand(0, 10); }
    private function getTotalSpent(int $userId): float { return rand(500, 5000); }
    private function getLastServiceDate(int $userId): string { return date('Y-m-d', strtotime('-' . rand(1, 30) . ' days')); }

    private function getRecentActivities(): array
    {
        return [
            ['action' => 'User login', 'user' => 'admin', 'time' => '2 minutes ago'],
            ['action' => 'Order completed', 'user' => 'tech1', 'time' => '15 minutes ago'],
            ['action' => 'New user registered', 'user' => 'customer123', 'time' => '1 hour ago']
        ];
    }

    private function getSystemInfo(): array
    {
        return [
            'environment' => ENVIRONMENT,
            'php_version' => PHP_VERSION,
            'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
            'uptime' => $this->getServerUptime()
        ];
    }

    private function getAssignedOrders(int $userId): array
    {
        // Mock data - replace with actual database query
        return [
            ['id' => 1, 'title' => 'Network Setup', 'customer' => 'ABC Corp', 'priority' => 'High', 'due_date' => date('Y-m-d', strtotime('+2 days'))],
            ['id' => 2, 'title' => 'Server Maintenance', 'customer' => 'XYZ Ltd', 'priority' => 'Medium', 'due_date' => date('Y-m-d', strtotime('+5 days'))]
        ];
    }

    private function getWorkSummary(int $userId): array
    {
        return [
            'today_tasks' => $this->getTodayTasks($userId),
            'week_progress' => rand(60, 90),
            'month_completion' => rand(70, 95)
        ];
    }

    private function getUpcomingTasks(int $userId): array
    {
        return [
            ['task' => 'Install new software', 'due' => 'Tomorrow', 'priority' => 'High'],
            ['task' => 'System backup', 'due' => 'This week', 'priority' => 'Medium']
        ];
    }

    private function getActiveServices(int $userId): array
    {
        return [
            ['id' => 1, 'service' => 'Web Development', 'status' => 'In Progress', 'progress' => 75],
            ['id' => 2, 'service' => 'SEO Optimization', 'status' => 'Planning', 'progress' => 25]
        ];
    }

    private function getRecentOrders(int $userId): array
    {
        return [
            ['id' => 1, 'service' => 'Website Design', 'status' => 'Completed', 'date' => date('Y-m-d', strtotime('-5 days'))],
            ['id' => 2, 'service' => 'App Development', 'status' => 'In Progress', 'date' => date('Y-m-d', strtotime('-10 days'))]
        ];
    }

    private function getSupportTickets(int $userId): array
    {
        return [
            ['id' => 1, 'subject' => 'Login Issue', 'status' => 'Open', 'priority' => 'High', 'created' => date('Y-m-d H:i', strtotime('-2 hours'))],
            ['id' => 2, 'subject' => 'Feature Request', 'status' => 'Pending', 'priority' => 'Low', 'created' => date('Y-m-d H:i', strtotime('-1 day'))]
        ];
    }

    private function getUserRegistrationChart(): array
    {
        $data = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $count = $this->userModel->where('DATE(created_at)', $date)->countAllResults();
            $data[] = [
                'date' => $date,
                'count' => $count ?: rand(0, 5) // Use actual count or mock data
            ];
        }
        return $data;
    }

    private function getUserRolesChart(): array
    {
        $roles = $this->userModel
            ->select('roles.name, roles.slug, COUNT(users.id) as user_count')
            ->join('roles', 'roles.id = users.role_id', 'left')
            ->where('users.is_active', 1)
            ->groupBy('roles.id, roles.name, roles.slug')
            ->findAll();

        $data = [];
        foreach ($roles as $role) {
            $data[$role['slug']] = $role['user_count'];
        }

        // Add mock data if no actual data
        if (empty($data)) {
            $data = [
                'admin' => rand(1, 5),
                'technician' => rand(5, 15),
                'customer' => rand(20, 100)
            ];
        }

        return $data;
    }

    private function getOrderStatusChart(int $userId = null, string $role = null): array
    {
        // Mock data - replace with actual database query
        return [
            'pending' => rand(5, 15),
            'in_progress' => rand(10, 25),
            'completed' => rand(50, 100),
            'cancelled' => rand(0, 5)
        ];
    }

    private function getWorkCompletionChart(int $userId = null, string $role = null): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $data[] = [
                'date' => $date,
                'completed' => rand(2, 10),
                'assigned' => rand(5, 15)
            ];
        }
        return $data;
    }

    private function getServiceRequestsChart(int $userId = null, string $role = null): array
    {
        $data = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-$i months"));
            $data[] = [
                'month' => $month,
                'requests' => rand(10, 50),
                'completed' => rand(8, 45)
            ];
        }
        return $data;
    }

    private function getRevenueTrendChart(): array
    {
        $data = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-$i months"));
            $data[] = [
                'month' => $month,
                'revenue' => rand(10000, 50000)
            ];
        }
        return $data;
    }

    private function getSystemPerformanceChart(): array
    {
        $data = [];
        for ($i = 23; $i >= 0; $i--) {
            $hour = date('H:00', strtotime("-$i hours"));
            $data[] = [
                'time' => $hour,
                'cpu' => rand(20, 80),
                'memory' => rand(30, 70),
                'disk' => rand(40, 90)
            ];
        }
        return $data;
    }

    private function getTodayTasks(int $userId): array
    {
        return [
            'completed' => rand(3, 8),
            'pending' => rand(1, 5),
            'total' => rand(5, 12)
        ];
    }

    private function getServerUptime(): string
    {
        if (function_exists('sys_getloadavg')) {
            $uptime = shell_exec('uptime');
            return $uptime ? trim($uptime) : 'Unknown';
        }
        return 'Unknown';
    }
}