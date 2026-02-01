<?php

namespace App\Models\ServiceDetails;

use App\Core\Model;

/**
 * Photography Service Detail Model
 * รายละเอียดคำขอบริการถ่ายภาพ
 */
class PhotographyDetail extends Model
{
    protected $table = 'request_photography_details';
    protected $primaryKey = 'id';
    protected $fillable = [
        'request_id', 'event_type', 'event_name', 'event_date',
        'event_time', 'location', 'duration_hours', 'participant_count',
        'photo_type', 'video_required', 'live_stream_required',
        'special_requirements', 'contact_person', 'contact_phone'
    ];
}
