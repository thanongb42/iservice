<?php

namespace App\Models\ServiceDetails;

use App\Core\Model;

/**
 * NAS Service Detail Model
 * รายละเอียดคำขอพื้นที่เก็บข้อมูล
 */
class NasDetail extends Model
{
    protected $table = 'request_nas_details';
    protected $primaryKey = 'id';
    protected $fillable = [
        'request_id', 'folder_name', 'storage_size_gb',
        'permission_type', 'shared_with', 'purpose', 'backup_required'
    ];
}
