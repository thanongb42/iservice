<?php

namespace App\Middleware;

use App\Core\Session;
use App\Core\Response;

/**
 * Guest Middleware
 * Redirect ถ้า user ล็อกอินแล้ว (สำหรับหน้า login/register)
 */
class GuestMiddleware
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
        // If user is already logged in, redirect to home
        if ($this->session->has('user_id')) {
            $role = $this->session->get('role');

            // Redirect admin to admin dashboard
            if ($role === 'admin') {
                Response::redirect('/admin');
            } else {
                Response::redirect('/');
            }
        }
    }
}
