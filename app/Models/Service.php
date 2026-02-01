<?php

namespace App\Models;

use App\Core\Model;

/**
 * Service Model
 * จัดการบริการต่างๆ (my_service table)
 */
class Service extends Model
{
    protected $table = 'my_service';
    protected $primaryKey = 'id';
    protected $fillable = [
        'service_code', 'service_name', 'service_name_en',
        'description', 'icon', 'color_code', 'service_url',
        'is_active', 'display_order'
    ];

    /**
     * Get active services
     */
    public function getActiveServices()
    {
        $sql = "SELECT * FROM my_service WHERE is_active = 1 ORDER BY display_order ASC, service_name ASC";
        return $this->query($sql)->fetchAll();
    }

    /**
     * Find service by code
     */
    public function findByCode($code)
    {
        $sql = "SELECT * FROM my_service WHERE service_code = ? LIMIT 1";
        return $this->query($sql, [$code])->fetch();
    }

    /**
     * Check if service code exists
     */
    public function codeExists($code, $excludeId = null)
    {
        if ($excludeId) {
            $sql = "SELECT COUNT(*) as count FROM my_service WHERE service_code = ? AND id != ?";
            $result = $this->query($sql, [$code, $excludeId])->fetch();
        } else {
            $sql = "SELECT COUNT(*) as count FROM my_service WHERE service_code = ?";
            $result = $this->query($sql, [$code])->fetch();
        }

        return $result['count'] > 0;
    }

    /**
     * Update display order
     */
    public function updateDisplayOrder($id, $order)
    {
        $sql = "UPDATE my_service SET display_order = ? WHERE id = ?";
        return $this->query($sql, [$order, $id]);
    }

    /**
     * Toggle active status
     */
    public function toggleActive($id)
    {
        $sql = "UPDATE my_service SET is_active = NOT is_active WHERE id = ?";
        return $this->query($sql, [$id]);
    }
}
