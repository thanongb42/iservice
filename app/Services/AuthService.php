<?php

namespace App\Services;

use App\Models\User;
use App\Core\Session;

/**
 * Authentication Service
 * จัดการ authentication และ authorization
 */
class AuthService
{
    private $session;
    private $userModel;

    public function __construct()
    {
        $this->session = new Session();
        $this->userModel = new User();
    }

    /**
     * Attempt to authenticate user
     */
    public function attempt($username, $password)
    {
        $user = $this->userModel->findByUsernameOrEmail($username);

        if (!$user) {
            return [
                'success' => false,
                'message' => 'ไม่พบผู้ใช้งานในระบบ'
            ];
        }

        if (!password_verify($password, $user['password'])) {
            return [
                'success' => false,
                'message' => 'รหัสผ่านไม่ถูกต้อง'
            ];
        }

        if ($user['status'] !== 'active') {
            return [
                'success' => false,
                'message' => 'บัญชีผู้ใช้ถูกระงับการใช้งาน'
            ];
        }

        // Login successful
        $this->login($user);
        $this->userModel->updateLastLogin($user['user_id']);

        return [
            'success' => true,
            'user' => $user,
            'message' => 'เข้าสู่ระบบสำเร็จ'
        ];
    }

    /**
     * Login user (set session)
     */
    public function login($user)
    {
        $this->session->set('user_id', $user['user_id']);
        $this->session->set('username', $user['username']);
        $this->session->set('role', $user['role']);
        $this->session->set('full_name', $user['first_name'] . ' ' . $user['last_name']);
        $this->session->set('email', $user['email']);
        $this->session->set('department_id', $user['department_id']);

        // Regenerate session ID for security
        $this->session->regenerate();
    }

    /**
     * Logout user
     */
    public function logout()
    {
        $this->session->destroy();
    }

    /**
     * Check if user is logged in
     */
    public function check()
    {
        return $this->session->has('user_id');
    }

    /**
     * Get current authenticated user
     */
    public function user()
    {
        if (!$this->check()) {
            return null;
        }

        $userId = $this->session->get('user_id');
        return $this->userModel->getFullDetails($userId);
    }

    /**
     * Get user ID
     */
    public function id()
    {
        return $this->session->get('user_id');
    }

    /**
     * Check if user is admin
     */
    public function isAdmin()
    {
        return $this->session->get('role') === 'admin';
    }

    /**
     * Check if user is staff
     */
    public function isStaff()
    {
        $role = $this->session->get('role');
        return $role === 'admin' || $role === 'staff';
    }

    /**
     * Register new user
     */
    public function register($data)
    {
        // Check if username exists
        if ($this->userModel->usernameExists($data['username'])) {
            return [
                'success' => false,
                'message' => 'ชื่อผู้ใช้นี้ถูกใช้งานแล้ว'
            ];
        }

        // Check if email exists
        if ($this->userModel->emailExists($data['email'])) {
            return [
                'success' => false,
                'message' => 'อีเมลนี้ถูกใช้งานแล้ว'
            ];
        }

        // Hash password
        $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);

        // Set default role and status
        $data['role'] = $data['role'] ?? 'user';
        $data['status'] = $data['status'] ?? 'active';

        try {
            $userId = $this->userModel->create($data);

            return [
                'success' => true,
                'user_id' => $userId,
                'message' => 'สมัครสมาชิกสำเร็จ'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get session data
     */
    public function getSession($key, $default = null)
    {
        return $this->session->get($key, $default);
    }
}
