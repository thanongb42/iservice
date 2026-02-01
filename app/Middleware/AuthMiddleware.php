<?php

namespace App\Middleware;

use App\Core\Session;
use App\Core\Response;

/**
 * Authentication Middleware
 * ตรวจสอบว่าผู้ใช้ล็อกอินแล้วหรือยัง
 */
class AuthMiddleware
{
    private $session;

    public function __construct()
    {
        $this->session = new Session();
    }

    /**
     * Handle middleware logic
     */
    public function handle()
    {
        // Check if user is logged in
        if (!$this->session->has('user_id')) {
            // Redirect to login page
            $_SESSION['_redirect_after_login'] = $_SERVER['REQUEST_URI'];
            Response::redirect('/login');
        }
    }
}
