<?php

namespace App\Models;

use App\Core\Model;

/**
 * User Model
 * จัดการข้อมูลผู้ใช้งาน
 */
class User extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'user_id';
    protected $fillable = [
        'username', 'email', 'password', 'first_name', 'last_name',
        'prefix_id', 'department_id', 'position', 'phone',
        'role', 'status', 'profile_image'
    ];

    /**
     * Find user by username or email
     */
    public function findByUsernameOrEmail($identifier)
    {
        $sql = "SELECT u.*, p.prefix_name, d.department_name, d.department_code
                FROM users u
                LEFT JOIN prefixes p ON u.prefix_id = p.prefix_id
                LEFT JOIN departments d ON u.department_id = d.department_id
                WHERE (u.username = ? OR u.email = ?) AND u.status = 'active'
                LIMIT 1";

        return $this->query($sql, [$identifier, $identifier])->fetch();
    }

    /**
     * Update last login timestamp
     */
    public function updateLastLogin($userId)
    {
        $sql = "UPDATE users SET last_login = NOW() WHERE user_id = ?";
        return $this->query($sql, [$userId]);
    }

    /**
     * Get user with full details (using view)
     */
    public function getFullDetails($userId)
    {
        $sql = "SELECT * FROM v_users_full WHERE user_id = ?";
        return $this->query($sql, [$userId])->fetch();
    }

    /**
     * Get all users with full details
     */
    public function getAllWithDetails()
    {
        $sql = "SELECT * FROM v_users_full ORDER BY created_at DESC";
        return $this->query($sql)->fetchAll();
    }

    /**
     * Check if username exists
     */
    public function usernameExists($username, $excludeId = null)
    {
        if ($excludeId) {
            $sql = "SELECT COUNT(*) as count FROM users WHERE username = ? AND user_id != ?";
            $result = $this->query($sql, [$username, $excludeId])->fetch();
        } else {
            $sql = "SELECT COUNT(*) as count FROM users WHERE username = ?";
            $result = $this->query($sql, [$username])->fetch();
        }

        return $result['count'] > 0;
    }

    /**
     * Check if email exists
     */
    public function emailExists($email, $excludeId = null)
    {
        if ($excludeId) {
            $sql = "SELECT COUNT(*) as count FROM users WHERE email = ? AND user_id != ?";
            $result = $this->query($sql, [$email, $excludeId])->fetch();
        } else {
            $sql = "SELECT COUNT(*) as count FROM users WHERE email = ?";
            $result = $this->query($sql, [$email])->fetch();
        }

        return $result['count'] > 0;
    }

    /**
     * Get users by role
     */
    public function getByRole($role)
    {
        $sql = "SELECT * FROM v_users_full WHERE role = ? ORDER BY first_name";
        return $this->query($sql, [$role])->fetchAll();
    }

    /**
     * Get users by department
     */
    public function getByDepartment($departmentId)
    {
        $sql = "SELECT * FROM v_users_full WHERE department_id = ? ORDER BY first_name";
        return $this->query($sql, [$departmentId])->fetchAll();
    }
}
