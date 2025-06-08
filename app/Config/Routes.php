<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// Default route
$routes->get('/', 'Home::index');

// Authentication routes dengan security middleware
$routes->group('auth', [
    'filter' => 'guest',
    'namespace' => 'App\Controllers'
], function($routes) {
    // Login routes
    $routes->get('signin', 'AuthController::signin');
    $routes->post('processLogin', 'AuthController::processLogin', ['filter' => 'security']);

    // Registration routes
    $routes->get('signup', 'AuthController::signup');
    $routes->post('processRegister', 'AuthController::processRegister', ['filter' => 'security']);

    // Password recovery
    $routes->get('forgot-password', 'AuthController::forgotPassword');
    $routes->post('processForgotPassword', 'AuthController::processForgotPassword', ['filter' => 'security']);
    $routes->get('reset-password/(:segment)', 'AuthController::resetPassword/$1');
    $routes->post('processResetPassword', 'AuthController::processResetPassword', ['filter' => 'security']);

    // CSRF token refresh untuk AJAX
    $routes->get('csrf-token', 'AuthController::getCsrfToken');
});

// Logout route (untuk user yang sudah login)
$routes->get('auth/logout', 'AuthController::logout', ['filter' => 'auth']);

// Redirect routes untuk kemudahan akses
$routes->get('login', 'AuthController::signin', ['filter' => 'guest']);
$routes->get('register', 'AuthController::signup', ['filter' => 'guest']);
$routes->get('logout', 'AuthController::logout', ['filter' => 'auth']);

// Dashboard route dengan session check
$routes->get('dashboard', 'DashboardController::index', ['filter' => 'auth']);

// Admin routes dengan security middleware tambahan
$routes->group('admin', [
    'namespace' => 'App\Controllers\Admin',
    'filter' => ['auth', 'security']
], function($routes) {

    // Dashboard admin
    $routes->get('/', 'DashboardController::index');
    $routes->get('dashboard', 'DashboardController::index');

    // User management dengan role-based access
    $routes->group('users', ['filter' => 'admin'], function($routes) {
        $routes->get('/', 'UserController::index');
        $routes->get('datatables', 'UserController::datatables'); // untuk AJAX DataTables
        $routes->get('create', 'UserController::create');
        $routes->post('store', 'UserController::store');
        $routes->get('edit/(:num)', 'UserController::edit/$1');
        $routes->put('update/(:num)', 'UserController::update/$1');
        $routes->delete('delete/(:num)', 'UserController::delete/$1');
        $routes->get('profile/(:num)', 'UserController::profile/$1');
        $routes->post('bulk-action', 'UserController::bulkAction'); // bulk operations
        $routes->post('import', 'UserController::import'); // import users
        $routes->get('export', 'UserController::export'); // export users
    });

    // Settings dengan admin-only access
    $routes->group('settings', ['filter' => 'admin'], function($routes) {
        $routes->get('/', 'SettingsController::index');
        $routes->post('update', 'SettingsController::update');
        $routes->get('backup', 'SettingsController::backup');
        $routes->post('restore', 'SettingsController::restore');
    });

    // Reports dengan caching
    $routes->group('reports', function($routes) {
        $routes->get('/', 'ReportsController::index');
        $routes->get('users', 'ReportsController::users');
        $routes->get('activity', 'ReportsController::activity');
        $routes->get('security', 'ReportsController::security', ['filter' => 'admin']);
        $routes->get('performance', 'ReportsController::performance', ['filter' => 'admin']);
    });

    // Audit logs (admin only)
    $routes->group('audit', ['filter' => 'admin'], function($routes) {
        $routes->get('/', 'AuditController::index');
        $routes->get('view/(:num)', 'AuditController::view/$1');
        $routes->delete('clear', 'AuditController::clear');
    });

    // System maintenance
    $routes->group('maintenance', ['filter' => 'admin'], function($routes) {
        $routes->get('/', 'MaintenanceController::index');
        $routes->post('cache/clear', 'MaintenanceController::clearCache');
        $routes->post('logs/clear', 'MaintenanceController::clearLogs');
        $routes->get('system-info', 'MaintenanceController::systemInfo');
    });
});

// User profile routes dengan optimized filtering
$routes->group('profile', ['filter' => 'auth'], function($routes) {
    $routes->get('/', 'ProfileController::index');
    $routes->get('edit', 'ProfileController::edit');
    $routes->post('update', 'ProfileController::update', ['filter' => 'security']);
    $routes->get('change-password', 'ProfileController::changePassword');
    $routes->post('updatePassword', 'ProfileController::updatePassword', ['filter' => 'security']);
    $routes->get('activity', 'ProfileController::activity'); // user activity log
    $routes->post('avatar', 'ProfileController::uploadAvatar'); // avatar upload
});

// Technician routes (role-based)
$routes->group('technician', [
    'namespace' => 'App\Controllers\Technician',
    'filter' => ['auth', 'technician']
], function($routes) {
    $routes->get('/', 'DashboardController::index');
    $routes->get('dashboard', 'DashboardController::index');

    // Work orders management
    $routes->group('orders', function($routes) {
        $routes->get('/', 'OrderController::index');
        $routes->get('assigned', 'OrderController::assigned');
        $routes->get('view/(:num)', 'OrderController::view/$1');
        $routes->post('update-status/(:num)', 'OrderController::updateStatus/$1');
    });
});

// API routes dengan throttling yang ketat
$routes->group('api/v1', [
    'namespace' => 'App\Controllers\Api',
    'filter' => 'security'
], function($routes) {

    // Public API endpoints
    $routes->post('auth/login', 'AuthController::login');
    $routes->post('auth/register', 'AuthController::register');
    $routes->post('auth/forgot-password', 'AuthController::forgotPassword');

    // Protected API endpoints
    $routes->group('', ['filter' => 'api-auth'], function($routes) {
        // User endpoints
        $routes->get('user/profile', 'UserController::profile');
        $routes->put('user/profile', 'UserController::updateProfile');
        $routes->post('auth/logout', 'AuthController::logout');
        $routes->post('auth/refresh', 'AuthController::refresh');

        // Admin API endpoints
        $routes->group('admin', ['filter' => 'api-admin'], function($routes) {
            $routes->get('users', 'AdminController::getUsers');
            $routes->post('users', 'AdminController::createUser');
            $routes->put('users/(:num)', 'AdminController::updateUser/$1');
            $routes->delete('users/(:num)', 'AdminController::deleteUser/$1');
            $routes->get('statistics', 'AdminController::getStatistics');
        });
    });
});

// Health check endpoint (untuk monitoring)
$routes->get('health', 'HealthController::check');

// Static file serving dengan proper caching
$routes->group('assets', function($routes) {
    $routes->get('avatar/(:segment)', 'AssetsController::avatar/$1');
    $routes->get('uploads/(:any)', 'AssetsController::uploads/$1');
});

// Error handling routes
$routes->get('error/403', 'ErrorController::show403');
$routes->get('error/404', 'ErrorController::show404');
$routes->get('error/500', 'ErrorController::show500');

// Maintenance mode
$routes->get('maintenance', 'MaintenanceController::show', ['filter' => 'maintenance']);

// Catch-all route untuk 404 (letakkan di paling bawah)
$routes->set404Override('ErrorController::show404');