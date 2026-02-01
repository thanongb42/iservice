<?php

namespace App\Models\ServiceDetails;

use App\Core\Model;

/**
 * QR Code Service Detail Model
 * รายละเอียดคำขอสร้าง QR Code
 */
class QrCodeDetail extends Model
{
    protected $table = 'request_qr_code_details';
    protected $primaryKey = 'id';
    protected $fillable = [
        'request_id', 'qr_type', 'target_url', 'display_text',
        'logo_file', 'color_primary', 'color_background',
        'size', 'format', 'quantity', 'purpose'
    ];
}
