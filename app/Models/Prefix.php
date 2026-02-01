<?php

namespace App\Models;

use App\Core\Model;

/**
 * Prefix Model
 * จัดการคำนำหน้าชื่อ
 */
class Prefix extends Model
{
    protected $table = 'prefixes';
    protected $primaryKey = 'prefix_id';
    protected $fillable = [
        'prefix_name', 'prefix_type', 'display_order', 'is_active'
    ];

    /**
     * Get active prefixes
     */
    public function getActive()
    {
        $sql = "SELECT * FROM prefixes WHERE is_active = 1 ORDER BY display_order, prefix_name";
        return $this->query($sql)->fetchAll();
    }

    /**
     * Get by type
     */
    public function getByType($type)
    {
        $sql = "SELECT * FROM prefixes WHERE is_active = 1 AND prefix_type = ? ORDER BY display_order, prefix_name";
        return $this->query($sql, [$type])->fetchAll();
    }
}
