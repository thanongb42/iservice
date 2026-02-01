<?php

namespace App\Models;

use App\Core\Model;

/**
 * Department Model
 * จัดการโครงสร้างหน่วยงาน 4 ระดับ
 */
class Department extends Model
{
    protected $table = 'departments';
    protected $primaryKey = 'department_id';
    protected $fillable = [
        'parent_department_id', 'department_code', 'department_name',
        'short_name', 'level', 'level_type', 'manager_user_id',
        'building', 'floor', 'phone', 'email', 'budget_code', 'status'
    ];

    /**
     * Get departments by level
     */
    public function getByLevel($level, $parentId = null)
    {
        if ($parentId === null) {
            $sql = "SELECT * FROM departments
                    WHERE level = ? AND parent_department_id IS NULL AND status = 'active'
                    ORDER BY department_name";
            return $this->query($sql, [$level])->fetchAll();
        } else {
            $sql = "SELECT * FROM departments
                    WHERE level = ? AND parent_department_id = ? AND status = 'active'
                    ORDER BY department_name";
            return $this->query($sql, [$level, $parentId])->fetchAll();
        }
    }

    /**
     * Get complete department tree (recursive CTE)
     */
    public function getTree()
    {
        $sql = "WITH RECURSIVE dept_tree AS (
                    SELECT *, 1 as depth, CAST(department_name AS CHAR(1000)) as path
                    FROM departments
                    WHERE parent_department_id IS NULL AND status = 'active'

                    UNION ALL

                    SELECT d.*, dt.depth + 1, CONCAT(dt.path, ' > ', d.department_name)
                    FROM departments d
                    INNER JOIN dept_tree dt ON d.parent_department_id = dt.department_id
                    WHERE d.status = 'active'
                )
                SELECT * FROM dept_tree ORDER BY path";

        return $this->query($sql)->fetchAll();
    }

    /**
     * Get department tree as nested array
     */
    public function getTreeNested($parentId = null)
    {
        if ($parentId === null) {
            $sql = "SELECT * FROM departments WHERE parent_department_id IS NULL AND status = 'active' ORDER BY department_name";
            $departments = $this->query($sql)->fetchAll();
        } else {
            $sql = "SELECT * FROM departments WHERE parent_department_id = ? AND status = 'active' ORDER BY department_name";
            $departments = $this->query($sql, [$parentId])->fetchAll();
        }

        // Recursively get children
        foreach ($departments as &$dept) {
            $dept['children'] = $this->getTreeNested($dept['department_id']);
        }

        return $departments;
    }

    /**
     * Check if department code exists
     */
    public function codeExists($code, $excludeId = null)
    {
        if ($excludeId) {
            $sql = "SELECT COUNT(*) as count FROM departments WHERE department_code = ? AND department_id != ?";
            $result = $this->query($sql, [$code, $excludeId])->fetch();
        } else {
            $sql = "SELECT COUNT(*) as count FROM departments WHERE department_code = ?";
            $result = $this->query($sql, [$code])->fetch();
        }

        return $result['count'] > 0;
    }

    /**
     * Get parent departments for level
     */
    public function getParentsForLevel($level)
    {
        if ($level <= 1) {
            return [];
        }

        $sql = "SELECT * FROM departments
                WHERE level < ? AND status = 'active'
                ORDER BY level, department_name";
        return $this->query($sql, [$level])->fetchAll();
    }

    /**
     * Get children of department
     */
    public function getChildren($departmentId)
    {
        $sql = "SELECT * FROM departments
                WHERE parent_department_id = ? AND status = 'active'
                ORDER BY department_name";
        return $this->query($sql, [$departmentId])->fetchAll();
    }

    /**
     * Check if department has children
     */
    public function hasChildren($departmentId)
    {
        $sql = "SELECT COUNT(*) as count FROM departments WHERE parent_department_id = ?";
        $result = $this->query($sql, [$departmentId])->fetch();
        return $result['count'] > 0;
    }

    /**
     * Get full path of department
     */
    public function getPath($departmentId)
    {
        $sql = "WITH RECURSIVE dept_path AS (
                    SELECT department_id, department_name, parent_department_id, 1 as level
                    FROM departments
                    WHERE department_id = ?

                    UNION ALL

                    SELECT d.department_id, d.department_name, d.parent_department_id, dp.level + 1
                    FROM departments d
                    INNER JOIN dept_path dp ON d.department_id = dp.parent_department_id
                )
                SELECT GROUP_CONCAT(department_name ORDER BY level DESC SEPARATOR ' > ') as path
                FROM dept_path";

        $result = $this->query($sql, [$departmentId])->fetch();
        return $result['path'] ?? '';
    }
}
