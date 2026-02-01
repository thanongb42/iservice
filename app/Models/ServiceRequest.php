<?php

namespace App\Models;

use App\Core\Model;

/**
 * ServiceRequest Model
 * จัดการคำขอบริการ
 */
class ServiceRequest extends Model
{
    protected $table = 'service_requests';
    protected $primaryKey = 'id';
    protected $fillable = [
        'request_code', 'service_code', 'service_name',
        'user_id', 'requester_prefix_id', 'requester_name',
        'requester_position', 'requester_phone', 'requester_email',
        'department_id', 'department_name', 'subject', 'description',
        'request_data', 'status', 'priority', 'assigned_to',
        'admin_notes', 'rejection_reason', 'completion_notes',
        'attachment_file', 'expected_completion_date'
    ];

    /**
     * Generate unique request code
     */
    public function generateRequestCode()
    {
        $year = date('Y');

        $sql = "SELECT request_code FROM service_requests
                WHERE request_code LIKE ?
                ORDER BY id DESC LIMIT 1";

        $lastRequest = $this->query($sql, ["REQ-$year-%"])->fetch();

        if ($lastRequest) {
            $lastNum = intval(substr($lastRequest['request_code'], -4));
            $newNum = str_pad($lastNum + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNum = '0001';
        }

        return "REQ-$year-$newNum";
    }

    /**
     * Get all requests with full details (using view)
     */
    public function getAllWithDetails()
    {
        $sql = "SELECT * FROM v_service_requests_full ORDER BY created_at DESC";
        return $this->query($sql)->fetchAll();
    }

    /**
     * Get requests by status
     */
    public function getByStatus($status)
    {
        $sql = "SELECT * FROM v_service_requests_full WHERE status = ? ORDER BY created_at DESC";
        return $this->query($sql, [$status])->fetchAll();
    }

    /**
     * Get requests by service code
     */
    public function getByServiceCode($serviceCode)
    {
        $sql = "SELECT * FROM v_service_requests_full WHERE service_code = ? ORDER BY created_at DESC";
        return $this->query($sql, [$serviceCode])->fetchAll();
    }

    /**
     * Get requests assigned to user
     */
    public function getAssignedTo($userId)
    {
        $sql = "SELECT * FROM v_service_requests_full WHERE assigned_to = ? ORDER BY created_at DESC";
        return $this->query($sql, [$userId])->fetchAll();
    }

    /**
     * Create request with details (transaction)
     */
    public function createWithDetails($mainData, $detailsData, $serviceCode)
    {
        $this->beginTransaction();

        try {
            // Insert main request
            $requestId = $this->create($mainData);

            // Insert service-specific details
            $detailModel = $this->getDetailModel($serviceCode);
            if ($detailModel) {
                $detailsData['request_id'] = $requestId;
                $detailModel->create($detailsData);
            }

            $this->commit();
            return $requestId;

        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    /**
     * Get detail model for service code
     */
    private function getDetailModel($serviceCode)
    {
        $modelMap = [
            'EMAIL' => 'EmailDetail',
            'NAS' => 'NasDetail',
            'IT_SUPPORT' => 'ItSupportDetail',
            'INTERNET' => 'InternetDetail',
            'QR_CODE' => 'QrCodeDetail',
            'PHOTOGRAPHY' => 'PhotographyDetail',
            'WEB_DESIGN' => 'WebDesignDetail',
            'PRINTER' => 'PrinterDetail'
        ];

        if (isset($modelMap[$serviceCode])) {
            $modelClass = "App\\Models\\ServiceDetails\\{$modelMap[$serviceCode]}";
            if (class_exists($modelClass)) {
                return new $modelClass();
            }
        }

        return null;
    }

    /**
     * Update request status
     */
    public function updateStatus($id, $status, $notes = null)
    {
        $data = ['status' => $status];

        if ($status === 'in_progress' && !$this->find($id)['started_at']) {
            $data['started_at'] = date('Y-m-d H:i:s');
        }

        if ($status === 'completed') {
            $data['completed_at'] = date('Y-m-d H:i:s');
            if ($notes) {
                $data['completion_notes'] = $notes;
            }
        }

        if ($status === 'cancelled') {
            $data['cancelled_at'] = date('Y-m-d H:i:s');
        }

        return $this->update($id, $data);
    }

    /**
     * Assign request to user
     */
    public function assignTo($id, $userId)
    {
        return $this->update($id, ['assigned_to' => $userId]);
    }

    /**
     * Get statistics
     */
    public function getStatistics()
    {
        $sql = "SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
                    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
                FROM service_requests";

        return $this->query($sql)->fetch();
    }
}
