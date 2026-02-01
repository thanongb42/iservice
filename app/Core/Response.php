<?php

namespace App\Core;

/**
 * Response Class
 * จัดการ HTTP Response
 */
class Response
{
    /**
     * Return JSON response
     */
    public static function json($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Redirect to URL
     */
    public static function redirect($url)
    {
        header("Location: $url");
        exit;
    }

    /**
     * Redirect back
     */
    public static function back()
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        self::redirect($referer);
    }

    /**
     * Set HTTP status code
     */
    public static function status($code)
    {
        http_response_code($code);
        return new static();
    }

    /**
     * Return view
     */
    public static function view($view, $data = [], $layout = 'main')
    {
        $viewInstance = new View();
        return $viewInstance->render($view, $data, $layout);
    }

    /**
     * Download file
     */
    public static function download($file, $filename = null)
    {
        if (!file_exists($file)) {
            throw new \Exception("File not found: {$file}");
        }

        $filename = $filename ?: basename($file);

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit;
    }
}
