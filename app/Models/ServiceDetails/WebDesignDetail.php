<?php

namespace App\Models\ServiceDetails;

use App\Core\Model;

/**
 * Web Design Service Detail Model
 * รายละเอียดคำขอออกแบบเว็บไซต์
 */
class WebDesignDetail extends Model
{
    protected $table = 'request_web_design_details';
    protected $primaryKey = 'id';
    protected $fillable = [
        'request_id', 'website_type', 'website_purpose', 'target_audience',
        'page_count', 'features_required', 'design_style', 'color_scheme',
        'reference_sites', 'content_ready', 'domain_name', 'hosting_required',
        'launch_date', 'budget_range', 'maintenance_required'
    ];
}
