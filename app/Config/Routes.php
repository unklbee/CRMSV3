<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// Default route
$routes->get('/', 'Home::index');

// Authentication routes (untuk user yang belum login)
$routes->group('auth', ['filter' => 'guest'], function($routes) {
    $routes->get('signin', 'AuthController::signin');
    $routes->post('processLogin', 'AuthController::processLogin');
    $routes->get('signup', 'AuthController::signup');
    $routes->post('processRegister', 'AuthController::processRegister');
    $routes->get('forgot-password', 'AuthController::forgotPassword');
    $routes->post('processForgotPassword', 'AuthController::processForgotPassword');
    $routes->get('reset-password/(:segment)', 'AuthController::resetPassword/$1');
    $routes->post('processResetPassword', 'AuthController::processResetPassword');
});

// Logout route (untuk user yang sudah login)
$routes->get('auth/logout', 'AuthController::logout', ['filter' => 'auth']);

// Redirect routes untuk kemudahan akses
$routes->get('login', 'AuthController::signin', ['filter' => 'guest']);
$routes->get('register', 'AuthController::signup', ['filter' => 'guest']);
$routes->get('logout', 'AuthController::logout', ['filter' => 'auth']);

// Dashboard route (bisa diakses oleh semua user yang login)
$routes->get('dashboard', 'DashboardController::index', ['filter' => 'auth']);

// Admin routes
$routes->group('admin', ['namespace' => 'App\Controllers\Admin', 'filter' => 'auth'], function($routes) {

    // Dashboard admin
    $routes->get('/', 'DashboardController::index');
    $routes->get('dashboard', 'DashboardController::index');

    // User management
    $routes->group('users', function($routes) {
        $routes->get('/', 'UserController::index');
        $routes->get('create', 'UserController::create');
        $routes->post('store', 'UserController::store');
        $routes->get('edit/(:num)', 'UserController::edit/$1');
        $routes->put('update/(:num)', 'UserController::update/$1');
        $routes->delete('delete/(:num)', 'UserController::delete/$1');
        $routes->get('profile/(:num)', 'UserController::profile/$1');
    });

    // Settings
    $routes->group('settings', function($routes) {
        $routes->get('/', 'SettingsController::index');
        $routes->post('update', 'SettingsController::update');
    });

    // Reports
    $routes->group('reports', function($routes) {
        $routes->get('/', 'ReportsController::index');
        $routes->get('users', 'ReportsController::users');
        $routes->get('activity', 'ReportsController::activity');
    });
});

// User profile routes (untuk user biasa yang sudah login)
$routes->group('profile', ['filter' => 'auth'], function($routes) {
    $routes->get('/', 'ProfileController::index');
    $routes->get('edit', 'ProfileController::edit');
    $routes->post('update', 'ProfileController::update');
    $routes->get('change-password', 'ProfileController::changePassword');
    $routes->post('updatePassword', 'ProfileController::updatePassword');
});

// API routes (jika diperlukan)
$routes->group('api', ['namespace' => 'App\Controllers\Api'], function($routes) {
    // Auth API
    $routes->post('login', 'AuthController::login');
    $routes->post('logout', 'AuthController::logout', ['filter' => 'auth']);
    $routes->post('refresh', 'AuthController::refresh', ['filter' => 'auth']);

    // Protected API routes
    $routes->group('', ['filter' => 'auth'], function($routes) {
        $routes->get('user', 'UserController::profile');
        $routes->put('user', 'UserController::updateProfile');
    });
});

// Catch-all route untuk 404 (letakkan di paling bawah)
$routes->set404Override('ErrorController::show404');