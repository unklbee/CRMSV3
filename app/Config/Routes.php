<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

// Admin routes
$routes->group('admin', ['namespace' => 'App\Controllers\Admin'], function($routes) {
    // Auth routes (tidak pakai filter auth)
    $routes->get('login', 'AuthController::login', ['filter' => 'guest']);
    $routes->post('login', 'AuthController::authenticate');
    $routes->get('logout', 'AuthController::logout');

    // Dashboard (requires auth)
    $routes->group('', ['filter' => 'auth'], function($routes) {
        $routes->get('/', 'DashboardController::index'); // redirect ke dashboard
        $routes->get('dashboard', 'DashboardController::index');

    });
});