<?php

namespace App\Config;

/**
 * Application Configuration Class
 * จัดการ paths และ URLs ของ application
 */
class App
{
    /**
     * Get base path
     */
    public static function basePath($path = '')
    {
        return BASE_PATH . ($path ? '/' . ltrim($path, '/') : '');
    }

    /**
     * Get public path
     */
    public static function publicPath($path = '')
    {
        return self::basePath('public') . ($path ? '/' . ltrim($path, '/') : '');
    }

    /**
     * Get app path
     */
    public static function appPath($path = '')
    {
        return self::basePath('app') . ($path ? '/' . ltrim($path, '/') : '');
    }

    /**
     * Get storage path
     */
    public static function storagePath($path = '')
    {
        return self::basePath('storage') . ($path ? '/' . ltrim($path, '/') : '');
    }

    /**
     * Get database path
     */
    public static function databasePath($path = '')
    {
        return self::basePath('database') . ($path ? '/' . ltrim($path, '/') : '');
    }

    /**
     * Get application URL
     */
    public static function url($path = '')
    {
        $baseUrl = rtrim($_ENV['APP_URL'], '/');
        return $baseUrl . ($path ? '/' . ltrim($path, '/') : '');
    }

    /**
     * Get asset URL
     */
    public static function asset($path)
    {
        return self::url('assets/' . ltrim($path, '/'));
    }

    /**
     * Get application name
     */
    public static function name()
    {
        return $_ENV['APP_NAME'] ?? 'Application';
    }

    /**
     * Check if app is in debug mode
     */
    public static function debug()
    {
        return ($_ENV['APP_DEBUG'] ?? 'false') === 'true';
    }

    /**
     * Get environment
     */
    public static function environment()
    {
        return $_ENV['APP_ENV'] ?? 'production';
    }

    /**
     * Check if environment is local
     */
    public static function isLocal()
    {
        return self::environment() === 'local';
    }

    /**
     * Check if environment is production
     */
    public static function isProduction()
    {
        return self::environment() === 'production';
    }
}
