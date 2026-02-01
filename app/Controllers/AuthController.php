<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\AuthService;
use App\Models\Prefix;
use App\Models\Department;

/**
 * Authentication Controller
 * จัดการ login, register, logout
 */
class AuthController extends Controller
{
    private $authService;

    public function __construct()
    {
        parent::__construct();
        $this->authService = new AuthService();
    }

    /**
     * Show login form
     */
    public function showLogin()
    {
        // If already logged in, redirect
        if ($this->authService->check()) {
            if ($this->authService->isAdmin()) {
                return $this->redirect('/admin');
            }
            return $this->redirect('/');
        }

        return $this->view('auth/login', [], 'minimal');
    }

    /**
     * Handle login
     */
    public function login()
    {
        $username = $this->input('username');
        $password = $this->input('password');

        // Validate
        $validation = $this->validate([
            'username' => 'required',
            'password' => 'required'
        ]);

        if (!$validation['valid']) {
            return $this->json([
                'success' => false,
                'message' => 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน',
                'errors' => $validation['errors']
            ]);
        }

        // Attempt login
        $result = $this->authService->attempt($username, $password);

        if ($result['success']) {
            // Check redirect
            $redirectUrl = $_SESSION['_redirect_after_login'] ?? null;
            unset($_SESSION['_redirect_after_login']);

            if (!$redirectUrl) {
                $redirectUrl = $this->authService->isAdmin() ? '/admin' : '/';
            }

            return $this->json([
                'success' => true,
                'message' => $result['message'],
                'redirect' => $redirectUrl
            ]);
        }

        return $this->json([
            'success' => false,
            'message' => $result['message']
        ]);
    }

    /**
     * Show register form
     */
    public function showRegister()
    {
        // If already logged in, redirect
        if ($this->authService->check()) {
            return $this->redirect('/');
        }

        $prefixModel = new Prefix();
        $departmentModel = new Department();

        return $this->view('auth/register', [
            'prefixes' => $prefixModel->getActive(),
            'departments' => $departmentModel->getByLevel(1)
        ], 'minimal');
    }

    /**
     * Handle registration
     */
    public function register()
    {
        // Validate
        $validation = $this->validate([
            'username' => 'required|alphanumeric|min:4',
            'email' => 'required|email',
            'password' => 'required|min:6',
            'password_confirmation' => 'required',
            'first_name' => 'required',
            'last_name' => 'required',
            'prefix_id' => 'required'
        ]);

        if (!$validation['valid']) {
            return $this->json([
                'success' => false,
                'message' => 'กรุณากรอกข้อมูลให้ครบถ้วน',
                'errors' => $validation['errors']
            ]);
        }

        // Check password confirmation
        if ($this->input('password') !== $this->input('password_confirmation')) {
            return $this->json([
                'success' => false,
                'message' => 'รหัสผ่านไม่ตรงกัน'
            ]);
        }

        // Register
        $data = [
            'username' => $this->input('username'),
            'email' => $this->input('email'),
            'password' => $this->input('password'),
            'first_name' => $this->input('first_name'),
            'last_name' => $this->input('last_name'),
            'prefix_id' => $this->input('prefix_id'),
            'department_id' => $this->input('department_id'),
            'position' => $this->input('position'),
            'phone' => $this->input('phone'),
            'role' => 'user',
            'status' => 'active'
        ];

        $result = $this->authService->register($data);

        if ($result['success']) {
            return $this->json([
                'success' => true,
                'message' => $result['message'],
                'redirect' => '/login'
            ]);
        }

        return $this->json([
            'success' => false,
            'message' => $result['message']
        ]);
    }

    /**
     * Logout
     */
    public function logout()
    {
        $this->authService->logout();
        return $this->redirect('/login');
    }
}
