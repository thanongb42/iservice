<?php

namespace App\Models;

use App\Core\Model;

/**
 * NavMenu Model
 * จัดการเมนูแบบ dynamic
 */
class NavMenu extends Model
{
    protected $table = 'nav_menu';
    protected $primaryKey = 'id';
    protected $fillable = [
        'parent_id', 'menu_name', 'menu_name_en', 'menu_url',
        'menu_icon', 'menu_order', 'is_active', 'target', 'description'
    ];

    /**
     * Get menu structure (nested)
     */
    public function getMenuStructure($parentId = null)
    {
        if ($parentId === null) {
            $sql = "SELECT * FROM nav_menu WHERE parent_id IS NULL AND is_active = 1 ORDER BY menu_order";
            $menus = $this->query($sql)->fetchAll();
        } else {
            $sql = "SELECT * FROM nav_menu WHERE parent_id = ? AND is_active = 1 ORDER BY menu_order";
            $menus = $this->query($sql, [$parentId])->fetchAll();
        }

        // Recursively get children
        foreach ($menus as &$menu) {
            $menu['children'] = $this->getMenuStructure($menu['id']);
        }

        return $menus;
    }

    /**
     * Get all menus (flat)
     */
    public function getAllFlat()
    {
        $sql = "SELECT * FROM nav_menu ORDER BY parent_id, menu_order";
        return $this->query($sql)->fetchAll();
    }

    /**
     * Get parent menus only
     */
    public function getParentMenus()
    {
        $sql = "SELECT * FROM nav_menu WHERE parent_id IS NULL AND is_active = 1 ORDER BY menu_order";
        return $this->query($sql)->fetchAll();
    }

    /**
     * Get children of menu
     */
    public function getChildren($menuId)
    {
        $sql = "SELECT * FROM nav_menu WHERE parent_id = ? AND is_active = 1 ORDER BY menu_order";
        return $this->query($sql, [$menuId])->fetchAll();
    }
}
