<?php
/**
 * Front Controller - Entry Point
 * จุดเริ่มต้นของ application ทุก requests จะมาที่นี่
 */

// Define BASE_PATH constant
define('BASE_PATH', dirname(__DIR__));

// Load Composer autoloader
require BASE_PATH . '/vendor/autoload.php';

// Load environment variables from .env
$dotenv = parse_ini_file(BASE_PATH . '/.env');
if ($dotenv === false) {
    die('Error: Could not load .env file');
}

foreach ($dotenv as $key => $value) {
    $_ENV[$key] = $value;
    putenv("$key=$value");
}

// Error handling based on environment
if (($_ENV['APP_DEBUG'] ?? 'false') === 'true') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Set timezone
date_default_timezone_set($_ENV['TIMEZONE'] ?? 'Asia/Bangkok');

// Initialize and run application
use App\Core\Application;

try {
    $app = new Application();
    $app->run();
} catch (\Exception $e) {
    if (($_ENV['APP_DEBUG'] ?? 'false') === 'true') {
        echo '<h1>Application Error</h1>';
        echo '<p>' . $e->getMessage() . '</p>';
        echo '<pre>' . $e->getTraceAsString() . '</pre>';
    } else {
        http_response_code(500);
        echo 'Internal Server Error';
    }
}
