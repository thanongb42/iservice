<?php

namespace App\Services;

use App\Core\View;
use App\Core\Validator;

/**
 * Service Form Builder
 * จัดการ dynamic forms สำหรับแต่ละ service
 */
class ServiceFormBuilder
{
    private $serviceCode;
    private $view;

    public function __construct($serviceCode)
    {
        $this->serviceCode = strtoupper($serviceCode);
        $this->view = new View();
    }

    /**
     * Render form fields for service
     */
    public function render()
    {
        $viewPath = $this->getFormViewPath();

        if (!file_exists($viewPath)) {
            return '<p class="text-red-500">Form not found for service: ' . $this->serviceCode . '</p>';
        }

        ob_start();
        require $viewPath;
        return ob_get_clean();
    }

    /**
     * Get form view path
     */
    private function getFormViewPath()
    {
        $formName = strtolower($this->serviceCode);
        return BASE_PATH . '/app/Views/service-requests/forms/' . $formName . '.php';
    }

    /**
     * Validate form data
     */
    public function validate($data)
    {
        $rules = $this->getValidationRules();

        if (empty($rules)) {
            return ['valid' => true, 'errors' => []];
        }

        return Validator::make($data, $rules);
    }

    /**
     * Get validation rules for service
     */
    private function getValidationRules()
    {
        $rules = [
            'EMAIL' => [
                'requested_username' => 'required',
                'email_format' => 'required',
                'purpose' => 'required'
            ],
            'NAS' => [
                'folder_name' => 'required',
                'storage_size_gb' => 'required|numeric',
                'permission_type' => 'required',
                'purpose' => 'required'
            ],
            'IT_SUPPORT' => [
                'issue_type' => 'required',
                'device_type' => 'required',
                'symptoms' => 'required',
                'location' => 'required'
            ],
            'INTERNET' => [
                'request_type' => 'required',
                'location' => 'required',
                'purpose' => 'required'
            ],
            'QR_CODE' => [
                'qr_type' => 'required',
                'purpose' => 'required'
            ],
            'PHOTOGRAPHY' => [
                'event_type' => 'required',
                'event_name' => 'required',
                'event_date' => 'required',
                'location' => 'required'
            ],
            'WEB_DESIGN' => [
                'website_type' => 'required',
                'website_purpose' => 'required',
                'page_count' => 'required|numeric'
            ],
            'PRINTER' => [
                'request_type' => 'required',
                'location' => 'required',
                'issue_description' => 'required'
            ]
        ];

        return $rules[$this->serviceCode] ?? [];
    }

    /**
     * Extract service-specific details from request data
     */
    public function extractDetailsData($data)
    {
        $fields = $this->getDetailsFields();
        $detailsData = [];

        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $detailsData[$field] = $data[$field];
            }
        }

        return $detailsData;
    }

    /**
     * Get detail fields for service
     */
    private function getDetailsFields()
    {
        $fields = [
            'EMAIL' => [
                'requested_username', 'email_format', 'quota_mb',
                'purpose', 'is_new_account', 'existing_email'
            ],
            'NAS' => [
                'folder_name', 'storage_size_gb', 'permission_type',
                'shared_with', 'purpose', 'backup_required'
            ],
            'IT_SUPPORT' => [
                'issue_type', 'device_type', 'device_brand', 'symptoms',
                'location', 'urgency_level', 'error_message', 'when_occurred'
            ],
            'INTERNET' => [
                'request_type', 'location', 'device_count', 'bandwidth_required',
                'duration', 'purpose', 'existing_connection'
            ],
            'QR_CODE' => [
                'qr_type', 'target_url', 'display_text', 'logo_file',
                'color_primary', 'color_background', 'size', 'format', 'quantity', 'purpose'
            ],
            'PHOTOGRAPHY' => [
                'event_type', 'event_name', 'event_date', 'event_time',
                'location', 'duration_hours', 'participant_count', 'photo_type',
                'video_required', 'live_stream_required', 'special_requirements',
                'contact_person', 'contact_phone'
            ],
            'WEB_DESIGN' => [
                'website_type', 'website_purpose', 'target_audience', 'page_count',
                'features_required', 'design_style', 'color_scheme', 'reference_sites',
                'content_ready', 'domain_name', 'hosting_required', 'launch_date',
                'budget_range', 'maintenance_required'
            ],
            'PRINTER' => [
                'request_type', 'printer_brand', 'printer_model', 'location',
                'issue_description', 'error_code', 'print_quality_issue', 'paper_jam',
                'toner_level', 'connection_type', 'drivers_installed',
                'last_maintenance_date', 'urgency_level'
            ]
        ];

        return $fields[$this->serviceCode] ?? [];
    }

    /**
     * Get service code
     */
    public function getServiceCode()
    {
        return $this->serviceCode;
    }
}
