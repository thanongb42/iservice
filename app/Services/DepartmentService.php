<?php

namespace App\Services;

use App\Models\Department;

/**
 * Department Service
 * จัดการ business logic สำหรับ departments
 */
class DepartmentService
{
    private $departmentModel;

    public function __construct()
    {
        $this->departmentModel = new Department();
    }

    /**
     * Build department tree for display
     */
    public function buildTree($parentId = null)
    {
        return $this->departmentModel->getTreeNested($parentId);
    }

    /**
     * Get departments for cascade dropdown
     */
    public function getCascade($level, $parentId = null)
    {
        return $this->departmentModel->getByLevel($level, $parentId);
    }

    /**
     * Get all departments as flat list
     */
    public function getAllFlat()
    {
        return $this->departmentModel->getTree();
    }

    /**
     * Validate department data
     */
    public function validate($data, $isUpdate = false)
    {
        $errors = [];

        // Required fields
        if (empty($data['department_code'])) {
            $errors['department_code'] = 'กรุณากรอกรหัสหน่วยงาน';
        }

        if (empty($data['department_name'])) {
            $errors['department_name'] = 'กรุณากรอกชื่อหน่วยงาน';
        }

        if (empty($data['level'])) {
            $errors['level'] = 'กรุณาเลือกระดับ';
        }

        // Check unique code
        if (!empty($data['department_code'])) {
            $excludeId = $isUpdate ? ($data['department_id'] ?? null) : null;

            if ($this->departmentModel->codeExists($data['department_code'], $excludeId)) {
                $errors['department_code'] = 'รหัสหน่วยงานนี้ถูกใช้งานแล้ว';
            }
        }

        // Check parent is required for level > 1
        if (!empty($data['level']) && $data['level'] > 1) {
            if (empty($data['parent_department_id'])) {
                $errors['parent_department_id'] = 'กรุณาเลือกหน่วยงานแม่';
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Create department
     */
    public function create($data)
    {
        $validation = $this->validate($data);

        if (!$validation['valid']) {
            return [
                'success' => false,
                'errors' => $validation['errors']
            ];
        }

        try {
            // Convert empty parent_id to null
            if (empty($data['parent_department_id'])) {
                $data['parent_department_id'] = null;
            }

            // Ensure code is uppercase
            $data['department_code'] = strtoupper($data['department_code']);

            if (!empty($data['short_name'])) {
                $data['short_name'] = strtoupper($data['short_name']);
            }

            $id = $this->departmentModel->create($data);

            return [
                'success' => true,
                'id' => $id,
                'message' => 'เพิ่มหน่วยงานสำเร็จ'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update department
     */
    public function update($id, $data)
    {
        $data['department_id'] = $id;
        $validation = $this->validate($data, true);

        if (!$validation['valid']) {
            return [
                'success' => false,
                'errors' => $validation['errors']
            ];
        }

        try {
            // Ensure code is uppercase
            $data['department_code'] = strtoupper($data['department_code']);

            if (!empty($data['short_name'])) {
                $data['short_name'] = strtoupper($data['short_name']);
            }

            $this->departmentModel->update($id, $data);

            return [
                'success' => true,
                'message' => 'อัปเดตหน่วยงานสำเร็จ'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Delete department
     */
    public function delete($id)
    {
        // Check if department has children
        if ($this->departmentModel->hasChildren($id)) {
            return [
                'success' => false,
                'message' => 'ไม่สามารถลบได้ เนื่องจากมีหน่วยงานย่อย กรุณาลบหน่วยงานย่อยก่อน'
            ];
        }

        try {
            $this->departmentModel->delete($id);

            return [
                'success' => true,
                'message' => 'ลบหน่วยงานสำเร็จ'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get department details
     */
    public function getDetails($id)
    {
        $dept = $this->departmentModel->find($id);

        if (!$dept) {
            return null;
        }

        // Add path
        $dept['path'] = $this->departmentModel->getPath($id);

        return $dept;
    }
}
