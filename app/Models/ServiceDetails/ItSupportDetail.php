<?php

namespace App\Models\ServiceDetails;

use App\Core\Model;

/**
 * IT Support Service Detail Model
 * รายละเอียดคำขอแจ้งซ่อม IT
 */
class ItSupportDetail extends Model
{
    protected $table = 'request_it_support_details';
    protected $primaryKey = 'id';
    protected $fillable = [
        'request_id', 'issue_type', 'device_type', 'device_brand',
        'symptoms', 'location', 'urgency_level', 'error_message', 'when_occurred'
    ];
}
