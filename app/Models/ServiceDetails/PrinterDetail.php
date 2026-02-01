<?php

namespace App\Models\ServiceDetails;

use App\Core\Model;

/**
 * Printer Service Detail Model
 * รายละเอียดคำขอบริการเครื่องพิมพ์
 */
class PrinterDetail extends Model
{
    protected $table = 'request_printer_details';
    protected $primaryKey = 'id';
    protected $fillable = [
        'request_id', 'request_type', 'printer_brand', 'printer_model',
        'location', 'issue_description', 'error_code', 'print_quality_issue',
        'paper_jam', 'toner_level', 'connection_type', 'drivers_installed',
        'last_maintenance_date', 'urgency_level'
    ];
}
