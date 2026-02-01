<?php

namespace App\Services;

use App\Models\ServiceRequest;
use App\Models\Service;
use App\Models\Department;

/**
 * Service Request Service
 * จัดการ business logic สำหรับคำขอบริการ
 */
class ServiceRequestService
{
    private $serviceRequestModel;
    private $serviceModel;
    private $departmentModel;

    public function __construct()
    {
        $this->serviceRequestModel = new ServiceRequest();
        $this->serviceModel = new Service();
        $this->departmentModel = new Department();
    }

    /**
     * Create new service request
     */
    public function createRequest($data, $detailsData, $serviceCode)
    {
        try {
            // Generate request code
            $requestCode = $this->serviceRequestModel->generateRequestCode();
            $data['request_code'] = $requestCode;

            // Get service name
            $service = $this->serviceModel->findByCode($serviceCode);
            if (!$service) {
                return [
                    'success' => false,
                    'message' => 'ไม่พบบริการนี้ในระบบ'
                ];
            }

            $data['service_code'] = $serviceCode;
            $data['service_name'] = $service['service_name'];

            // Get department name
            if (!empty($data['department_id'])) {
                $dept = $this->departmentModel->find($data['department_id']);
                if ($dept) {
                    $data['department_name'] = $dept['department_name'];
                }
            }

            // Set default status
            $data['status'] = 'pending';

            // Create request with details
            $requestId = $this->serviceRequestModel->createWithDetails($data, $detailsData, $serviceCode);

            return [
                'success' => true,
                'request_id' => $requestId,
                'request_code' => $requestCode,
                'message' => 'สร้างคำขอสำเร็จ'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update request status
     */
    public function updateStatus($id, $status, $notes = null)
    {
        try {
            $this->serviceRequestModel->updateStatus($id, $status, $notes);

            return [
                'success' => true,
                'message' => 'อัปเดตสถานะสำเร็จ'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Assign request to user
     */
    public function assignTo($id, $userId)
    {
        try {
            $this->serviceRequestModel->assignTo($id, $userId);

            return [
                'success' => true,
                'message' => 'มอบหมายงานสำเร็จ'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get all requests with filters
     */
    public function getRequests($filters = [])
    {
        if (!empty($filters['status'])) {
            return $this->serviceRequestModel->getByStatus($filters['status']);
        }

        if (!empty($filters['service_code'])) {
            return $this->serviceRequestModel->getByServiceCode($filters['service_code']);
        }

        if (!empty($filters['assigned_to'])) {
            return $this->serviceRequestModel->getAssignedTo($filters['assigned_to']);
        }

        return $this->serviceRequestModel->getAllWithDetails();
    }

    /**
     * Get request statistics
     */
    public function getStatistics()
    {
        return $this->serviceRequestModel->getStatistics();
    }

    /**
     * Get request details
     */
    public function getDetails($id)
    {
        return $this->serviceRequestModel->find($id);
    }
}
