<?php
/**
 * Application Routes
 * กำหนด URL routes สำหรับ application
 *
 * $router variable is available from Application class
 */

// ====================
// Public Routes
// ====================

// Home
$router->get('/', 'HomeController@index');

// Test route (for testing purposes)
$router->get('/test', function() {
    echo '<h1>MVC Framework is Working!</h1>';
    echo '<p>Welcome to Green Theme MVC Architecture</p>';
    echo '<p>Base Path: ' . BASE_PATH . '</p>';
    echo '<p>PHP Version: ' . PHP_VERSION . '</p>';
    echo '<p>Database: Connected</p>';
    echo '<pre>';
    print_r($_ENV);
    echo '</pre>';
});

// ====================
// Authentication Routes
// ====================

// Guest routes (only for non-logged-in users)
$router->group(['middleware' => ['GuestMiddleware']], function($router) {
    $router->get('/login', 'AuthController@showLogin');
    $router->post('/login', 'AuthController@login');
    $router->get('/register', 'AuthController@showRegister');
    $router->post('/register', 'AuthController@register');
});

// Logout (requires authentication)
$router->group(['middleware' => ['AuthMiddleware']], function($router) {
    $router->get('/logout', 'AuthController@logout');
});

// ====================
// Department Routes
// ====================
// Will be implemented in Phase 6

// ====================
// Service Request Routes
// ====================
// Will be implemented in Phase 6

// ====================
// Admin Routes
// ====================
// Will be implemented in Phase 7

// ====================
// API Routes
// ====================
// Will be implemented in Phase 8
