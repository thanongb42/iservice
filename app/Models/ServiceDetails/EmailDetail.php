<?php

namespace App\Models\ServiceDetails;

use App\Core\Model;

/**
 * Email Service Detail Model
 * รายละเอียดคำขอบริการอีเมล
 */
class EmailDetail extends Model
{
    protected $table = 'request_email_details';
    protected $primaryKey = 'id';
    protected $fillable = [
        'request_id', 'requested_username', 'email_format',
        'quota_mb', 'purpose', 'is_new_account', 'existing_email'
    ];
}
