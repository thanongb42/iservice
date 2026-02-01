<?php

namespace App\Core;

/**
 * Application Bootstrap Class
 * คลาสหลักสำหรับ bootstrap application
 */
class Application
{
    private $router;

    public function __construct()
    {
        $this->router = new Router();
        $this->loadRoutes();
    }

    /**
     * Load routes definition
     */
    private function loadRoutes()
    {
        $routesFile = BASE_PATH . '/routes.php';
        if (file_exists($routesFile)) {
            // Make router available in routes file
            $router = $this->router;
            require $routesFile;
        }
    }

    /**
     * Run the application
     */
    public function run()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Remove base path if app is in subdirectory
        $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
        if (strpos($uri, $basePath) === 0) {
            $uri = substr($uri, strlen($basePath));
        }
        $uri = $uri ?: '/';

        try {
            $this->router->dispatch($method, $uri);
        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Handle exceptions
     */
    private function handleException(\Exception $e)
    {
        if ($_ENV['APP_DEBUG'] === 'true') {
            echo '<h1>Error</h1>';
            echo '<p>' . $e->getMessage() . '</p>';
            echo '<pre>' . $e->getTraceAsString() . '</pre>';
        } else {
            http_response_code(500);
            echo 'Internal Server Error';
        }
    }

    /**
     * Get router instance
     */
    public function getRouter()
    {
        return $this->router;
    }
}
