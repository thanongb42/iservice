<?php

namespace App\Core;

/**
 * Session Class
 * จัดการ PHP sessions
 */
class Session
{
    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            $lifetime = $_ENV['SESSION_LIFETIME'] ?? 7200;
            session_set_cookie_params($lifetime);
            session_start();
        }
    }

    /**
     * Set session value
     */
    public function set($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Get session value
     */
    public function get($key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Check if session key exists
     */
    public function has($key)
    {
        return isset($_SESSION[$key]);
    }

    /**
     * Remove session key
     */
    public function remove($key)
    {
        unset($_SESSION[$key]);
    }

    /**
     * Destroy all sessions
     */
    public function destroy()
    {
        session_destroy();
        $_SESSION = [];
    }

    /**
     * Flash data (available for next request only)
     */
    public function flash($key, $value)
    {
        $_SESSION['_flash'][$key] = $value;
    }

    /**
     * Get flash data
     */
    public function getFlash($key, $default = null)
    {
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }

    /**
     * Check if flash data exists
     */
    public function hasFlash($key)
    {
        return isset($_SESSION['_flash'][$key]);
    }

    /**
     * Regenerate session ID
     */
    public function regenerate()
    {
        session_regenerate_id(true);
    }

    /**
     * Get all session data
     */
    public function all()
    {
        return $_SESSION;
    }

    /**
     * Clear all session data
     */
    public function clear()
    {
        $_SESSION = [];
    }

    /**
     * Get session ID
     */
    public function getId()
    {
        return session_id();
    }
}
