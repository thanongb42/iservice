<?php

namespace App\Models\ServiceDetails;

use App\Core\Model;

/**
 * Internet Service Detail Model
 * รายละเอียดคำขอบริการอินเทอร์เน็ต/WiFi
 */
class InternetDetail extends Model
{
    protected $table = 'request_internet_details';
    protected $primaryKey = 'id';
    protected $fillable = [
        'request_id', 'request_type', 'location', 'device_count',
        'bandwidth_required', 'duration', 'purpose', 'existing_connection'
    ];
}
