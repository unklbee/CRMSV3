<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// ============================================================================
// FRONTEND WEBSITE ROUTES (Public)
// ============================================================================

// Homepage - localhost:8080/
$routes->get('/', 'HomeController::index');

// Public pages
$routes->get('about', 'HomeController::about');
$routes->get('services', 'HomeController::services');
$routes->get('contact', 'HomeController::contact');
$routes->post('contact', 'HomeController::processContact');
$routes->get('pricing', 'HomeController::pricing');
$routes->get('blog', 'HomeController::blog');
$routes->get('blog/(:segment)', 'HomeController::blogPost/$1');

// Call-to-action
$routes->get('get-started', 'HomeController::getStarted');

// Legal pages
$routes->get('privacy', 'HomeController::privacy');
$routes->get('terms', 'HomeController::terms');

// AJAX endpoints
$routes->post('newsletter/subscribe', 'HomeController::subscribeNewsletter');

// ============================================================================
// AUTHENTICATION ROUTES
// ============================================================================

$routes->group('auth', ['filter' => 'guest'], function($routes) {
    // Login
    $routes->get('signin', 'AuthController::signin');
    $routes->get('login', 'AuthController::signin'); // Alias
    $routes->post('processLogin', 'AuthController::processLogin');

    // Registration
    $routes->get('signup', 'AuthController::signup');
    $routes->get('register', 'AuthController::signup'); // Alias
    $routes->post('register', 'AuthController::processRegister');

    // Password Recovery
    $routes->get('forgot-password', 'AuthController::forgotPassword');
    $routes->post('processForgotPassword', 'AuthController::processForgotPassword');
    $routes->get('reset-password/(:segment)', 'AuthController::resetPassword/$1');
    $routes->post('processResetPassword', 'AuthController::processResetPassword');

    // CSRF Token for AJAX
    $routes->get('csrf-token', 'AuthController::getCsrfToken');
});

// Logout (for authenticated users)
$routes->get('auth/logout', 'AuthController::logout', ['filter' => 'auth']);
$routes->get('logout', 'AuthController::logout', ['filter' => 'auth']); // Alias

// ============================================================================
// BACKEND DASHBOARD ROUTES (Authenticated Users)
// ============================================================================

// Main Dashboard Entry Point
$routes->get('dashboard', 'DashboardController::index', ['filter' => 'auth']);
$routes->get('dashboard/stats', 'DashboardController::ajaxStats', ['filter' => 'auth']);
$routes->post('dashboard/quick-action', 'DashboardController::quickAction', ['filter' => 'auth']);

// Alternative dashboard routes
$routes->get('app', 'DashboardController::index', ['filter' => 'auth']); // /app sebagai alias
$routes->get('panel', 'DashboardController::index', ['filter' => 'auth']); // /panel sebagai alias

// ============================================================================
// ADMIN BACKEND ROUTES (Admin Only)
// ============================================================================

$routes->group('admin', [
    'namespace' => 'App\Controllers\Admin',
    'filter' => ['auth', 'admin']
], function($routes) {

    // Admin Dashboard
    $routes->get('/', 'DashboardController::index');
    $routes->get('dashboard', 'DashboardController::index');

    // User Management
    $routes->group('users', function($routes) {
        $routes->get('/', 'UserController::index');
        $routes->get('datatables', 'UserController::datatables');
        $routes->get('create', 'UserController::create');
        $routes->post('/', 'UserController::store');
        $routes->get('(:num)', 'UserController::show/$1');
        $routes->get('(:num)/edit', 'UserController::edit/$1');
        $routes->put('(:num)', 'UserController::update/$1');
        $routes->delete('(:num)', 'UserController::delete/$1');
        $routes->post('bulk-action', 'UserController::bulkAction');
        $routes->get('export', 'UserController::export');
        $routes->post('import', 'UserController::import');
    });

    // System Settings
    $routes->group('settings', function($routes) {
        $routes->get('/', 'SettingsController::index');
        $routes->post('update', 'SettingsController::update');
        $routes->get('general', 'SettingsController::general');
        $routes->get('email', 'SettingsController::email');
        $routes->get('security', 'SettingsController::security');
        $routes->post('test-email', 'SettingsController::testEmail');
    });

    // Reports & Analytics
    $routes->group('reports', function($routes) {
        $routes->get('/', 'ReportsController::index');
        $routes->get('users', 'ReportsController::users');
        $routes->get('activity', 'ReportsController::activity');
        $routes->get('security', 'ReportsController::security');
        $routes->get('performance', 'ReportsController::performance');
        $routes->get('export/(:segment)', 'ReportsController::export/$1');
    });

    // Audit & Security
    $routes->group('audit', function($routes) {
        $routes->get('/', 'AuditController::index');
        $routes->get('logs', 'AuditController::logs');
        $routes->get('view/(:num)', 'AuditController::view/$1');
        $routes->delete('clear', 'AuditController::clear');
        $routes->get('export', 'AuditController::export');
    });

    // System Maintenance
    $routes->group('maintenance', function($routes) {
        $routes->get('/', 'MaintenanceController::index');
        $routes->post('cache/clear', 'MaintenanceController::clearCache');
        $routes->post('logs/clear', 'MaintenanceController::clearLogs');
        $routes->get('system-info', 'MaintenanceController::systemInfo');
        $routes->post('backup/create', 'MaintenanceController::createBackup');
        $routes->get('backups', 'MaintenanceController::listBackups');
        $routes->post('backup/restore/(:segment)', 'MaintenanceController::restoreBackup/$1');
    });

    // Content Management (untuk frontend)
    $routes->group('content', function($routes) {
        $routes->get('/', 'ContentController::index');
        $routes->get('pages', 'ContentController::pages');
        $routes->get('blog', 'ContentController::blog');
        $routes->get('testimonials', 'ContentController::testimonials');
        $routes->get('services', 'ContentController::services');
    });
});

// ============================================================================
// WORK MANAGEMENT ROUTES (Admin & Technician)
// ============================================================================

$routes->group('work', [
    'namespace' => 'App\Controllers\Work',
    'filter' => ['auth', 'role_permission:admin,technician']
], function($routes) {

    // Work Orders
    $routes->group('orders', function($routes) {
        $routes->get('/', 'OrderController::index');
        $routes->get('datatables', 'OrderController::datatables');
        $routes->get('create', 'OrderController::create');
        $routes->post('/', 'OrderController::store');
        $routes->get('(:num)', 'OrderController::show/$1');
        $routes->get('(:num)/edit', 'OrderController::edit/$1');
        $routes->put('(:num)', 'OrderController::update/$1');
        $routes->delete('(:num)', 'OrderController::delete/$1');

        // Status Management
        $routes->post('(:num)/assign', 'OrderController::assign/$1');
        $routes->post('(:num)/start', 'OrderController::start/$1');
        $routes->post('(:num)/complete', 'OrderController::complete/$1');
        $routes->post('(:num)/update-status', 'OrderController::updateStatus/$1');

        // Technician specific
        $routes->get('assigned', 'OrderController::assigned');
        $routes->get('my-orders', 'OrderController::myOrders');
    });

    // Schedule & Calendar
    $routes->group('schedule', function($routes) {
        $routes->get('/', 'ScheduleController::index');
        $routes->get('calendar', 'ScheduleController::calendar');
        $routes->get('today', 'ScheduleController::today');
        $routes->get('week', 'ScheduleController::week');
        $routes->get('month', 'ScheduleController::month');
        $routes->post('/', 'ScheduleController::create');
        $routes->put('(:num)', 'ScheduleController::update/$1');
        $routes->delete('(:num)', 'ScheduleController::delete/$1');
    });

    // Work Reports
    $routes->group('reports', function($routes) {
        $routes->get('/', 'WorkReportController::index');
        $routes->get('create', 'WorkReportController::create');
        $routes->post('/', 'WorkReportController::store');
        $routes->get('(:num)', 'WorkReportController::show/$1');
        $routes->get('my-reports', 'WorkReportController::myReports');
        $routes->get('export/(:segment)', 'WorkReportController::export/$1');
    });

    // Tasks
    $routes->group('tasks', function($routes) {
        $routes->get('/', 'TaskController::index');
        $routes->get('my-tasks', 'TaskController::myTasks');
        $routes->post('(:num)/complete', 'TaskController::complete/$1');
        $routes->post('update-status', 'TaskController::updateStatus');
    });
});

// ============================================================================
// CUSTOMER PORTAL ROUTES (Customer Only)
// ============================================================================

$routes->group('customer', [
    'namespace' => 'App\Controllers\Customer',
    'filter' => ['auth', 'customer']
], function($routes) {

    // Customer Dashboard
    $routes->get('/', 'DashboardController::index');
    $routes->get('dashboard', 'DashboardController::index');

    // Service Requests
    $routes->group('services', function($routes) {
        $routes->get('/', 'ServiceController::index');
        $routes->get('new', 'ServiceController::create');
        $routes->post('request', 'ServiceController::store');
        $routes->get('(:num)', 'ServiceController::show/$1');
        $routes->get('history', 'ServiceController::history');
        $routes->get('track/(:segment)', 'ServiceController::track/$1');
    });

    // Support Tickets
    $routes->group('tickets', function($routes) {
        $routes->get('/', 'TicketController::index');
        $routes->get('create', 'TicketController::create');
        $routes->post('/', 'TicketController::store');
        $routes->get('(:num)', 'TicketController::show/$1');
        $routes->post('(:num)/reply', 'TicketController::reply/$1');
        $routes->post('(:num)/close', 'TicketController::close/$1');
    });

    // Orders & Requests
    $routes->group('orders', function($routes) {
        $routes->get('/', 'OrderController::index');
        $routes->get('create', 'OrderController::create');
        $routes->post('/', 'OrderController::store');
        $routes->get('(:num)', 'OrderController::show/$1');
        $routes->get('track/(:segment)', 'OrderController::track/$1');
    });

    // Billing & Payments
    $routes->group('billing', function($routes) {
        $routes->get('/', 'BillingController::index');
        $routes->get('invoices', 'BillingController::invoices');
        $routes->get('invoices/(:num)', 'BillingController::viewInvoice/$1');
        $routes->get('payment-methods', 'BillingController::paymentMethods');
        $routes->post('payment-methods', 'BillingController::addPaymentMethod');
        $routes->delete('payment-methods/(:num)', 'BillingController::removePaymentMethod/$1');
    });

    // Documents & Downloads
    $routes->group('documents', function($routes) {
        $routes->get('/', 'DocumentController::index');
        $routes->get('(:num)', 'DocumentController::show/$1');
        $routes->get('(:num)/download', 'DocumentController::download/$1');
    });

    // Help & Support
    $routes->get('help', 'HelpController::index');
    $routes->get('help/(:segment)', 'HelpController::article/$1');
    $routes->get('faq', 'HelpController::faq');
});

// ============================================================================
// COMMON ROUTES (All Authenticated Users)
// ============================================================================

// Profile Management
$routes->group('profile', ['filter' => 'auth'], function($routes) {
    $routes->get('/', 'ProfileController::index');
    $routes->get('edit', 'ProfileController::edit');
    $routes->post('update', 'ProfileController::update');
    $routes->get('security', 'ProfileController::security');
    $routes->post('change-password', 'ProfileController::changePassword');
    $routes->get('activity', 'ProfileController::activity');
    $routes->post('upload-avatar', 'ProfileController::uploadAvatar');
    $routes->delete('avatar', 'ProfileController::deleteAvatar');
});

// Notifications
$routes->group('notifications', ['filter' => 'auth'], function($routes) {
    $routes->get('/', 'NotificationController::index');
    $routes->post('mark-read/(:num)', 'NotificationController::markRead/$1');
    $routes->post('mark-all-read', 'NotificationController::markAllRead');
    $routes->delete('(:num)', 'NotificationController::delete/$1');
});

// ============================================================================
// API ROUTES
// ============================================================================

$routes->group('api/v1', [
    'namespace' => 'App\Controllers\Api',
    'filter' => 'cors'
], function($routes) {

    // Public API (no auth required)
    $routes->post('auth/login', 'AuthApiController::login');
    $routes->post('auth/register', 'AuthApiController::register');
    $routes->post('contact', 'PublicApiController::contact');
    $routes->post('newsletter', 'PublicApiController::newsletter');

    // Protected API (authentication required)
    $routes->group('', ['filter' => 'api_auth'], function($routes) {

        // User API
        $routes->get('user', 'UserApiController::profile');
        $routes->put('user', 'UserApiController::updateProfile');
        $routes->post('auth/logout', 'AuthApiController::logout');

        // Dashboard API
        $routes->get('dashboard/stats', 'DashboardApiController::getStats');
        $routes->get('notifications', 'DashboardApiController::getNotifications');

        // Admin API
        $routes->group('admin', ['filter' => 'api_admin'], function($routes) {
            $routes->get('users', 'AdminApiController::getUsers');
            $routes->post('users', 'AdminApiController::createUser');
            $routes->put('users/(:num)', 'AdminApiController::updateUser/$1');
            $routes->delete('users/(:num)', 'AdminApiController::deleteUser/$1');
            $routes->get('stats', 'AdminApiController::getStats');
        });

        // Work API
        $routes->group('work', ['filter' => 'api_work'], function($routes) {
            $routes->get('orders', 'WorkApiController::getOrders');
            $routes->post('orders', 'WorkApiController::createOrder');
            $routes->put('orders/(:num)', 'WorkApiController::updateOrder/$1');
            $routes->get('schedule', 'WorkApiController::getSchedule');
        });

        // Customer API
        $routes->group('customer', ['filter' => 'api_customer'], function($routes) {
            $routes->get('services', 'CustomerApiController::getServices');
            $routes->post('services', 'CustomerApiController::requestService');
            $routes->get('tickets', 'CustomerApiController::getTickets');
            $routes->post('tickets', 'CustomerApiController::createTicket');
        });
    });
});

// ============================================================================
// UTILITY ROUTES
// ============================================================================

// File handling
$routes->get('files/download/(:segment)', 'FileController::download/$1', ['filter' => 'auth']);
$routes->post('upload/avatar', 'UploadController::avatar', ['filter' => 'auth']);
$routes->post('upload/document', 'UploadController::document', ['filter' => 'auth']);

// Search
$routes->get('search', 'SearchController::index', ['filter' => 'auth']);
$routes->post('search/global', 'SearchController::global', ['filter' => 'auth']);

// Health check
$routes->get('health', 'HealthController::check');
$routes->get('health/detailed', 'HealthController::detailed', ['filter' => 'admin']);

// Sitemap & SEO
$routes->get('sitemap.xml', 'SitemapController::index');
$routes->get('robots.txt', 'SitemapController::robots');

// Error pages
$routes->get('403', 'ErrorController::show403');
$routes->get('404', 'ErrorController::show404');
$routes->get('500', 'ErrorController::show500');

// Maintenance mode
$routes->get('maintenance', 'MaintenanceController::show');

// Catch-all untuk 404 (letakkan di paling bawah)
$routes->set404Override('ErrorController::show404');

// ============================================================================
// ROUTE SUMMARY:
// ============================================================================

/*
FRONTEND WEBSITE (Public Access):
- localhost:8080/                    → Homepage
- localhost:8080/about               → About page
- localhost:8080/services            → Services page
- localhost:8080/contact             → Contact page
- localhost:8080/pricing             → Pricing page
- localhost:8080/blog                → Blog listing
- localhost:8080/blog/article-slug   → Individual blog post
- localhost:8080/get-started         → CTA to registration

AUTHENTICATION:
- localhost:8080/auth/signin         → Login page
- localhost:8080/auth/signup         → Registration page
- localhost:8080/auth/forgot-password → Password recovery

BACKEND DASHBOARDS (Authenticated):
- localhost:8080/dashboard           → Unified dashboard (Admin/Technician)
- localhost:8080/customer/dashboard  → Customer dashboard

ADMIN BACKEND:
- localhost:8080/admin/              → Admin dashboard
- localhost:8080/admin/users         → User management
- localhost:8080/admin/settings      → System settings
- localhost:8080/admin/reports       → Reports & analytics
- localhost:8080/admin/audit         → Audit logs
- localhost:8080/admin/maintenance   → System maintenance

WORK MANAGEMENT (Admin & Technician):
- localhost:8080/work/orders         → Work orders
- localhost:8080/work/schedule       → Schedule management
- localhost:8080/work/reports        → Work reports
- localhost:8080/work/tasks          → Task management

CUSTOMER PORTAL:
- localhost:8080/customer/services   → Service requests
- localhost:8080/customer/tickets    → Support tickets
- localhost:8080/customer/orders     → Order management
- localhost:8080/customer/billing    → Billing & payments
- localhost:8080/customer/documents  → Documents

COMMON FEATURES:
- localhost:8080/profile             → User profile
- localhost:8080/notifications       → Notifications
- localhost:8080/search              → Global search

API ENDPOINTS:
- localhost:8080/api/v1/             → RESTful API
*/