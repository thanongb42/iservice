<?php

namespace App\Middleware;

use App\Core\Session;
use App\Core\Response;

/**
 * Admin Middleware
 * ตรวจสอบว่าผู้ใช้มีสิทธิ์ admin หรือไม่
 */
class AdminMiddleware
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
            Response::redirect('/login');
        }

        // Check if user has admin role
        $role = $this->session->get('role');

        if ($role !== 'admin') {
            // Not authorized - redirect to home
            http_response_code(403);
            Response::redirect('/');
        }
    }
}
