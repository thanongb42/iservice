<?php

namespace App\Models;

use App\Core\Model;

/**
 * TechNews Model
 * จัดการข่าวเทคโนโลยี
 */
class TechNews extends Model
{
    protected $table = 'tech_news';
    protected $primaryKey = 'id';
    protected $fillable = [
        'title', 'content', 'summary', 'category',
        'tags', 'cover_image', 'author', 'source_url',
        'is_published', 'is_pinned', 'is_featured',
        'view_count', 'published_at'
    ];

    /**
     * Get published news
     */
    public function getPublished($limit = null)
    {
        $sql = "SELECT * FROM tech_news WHERE is_published = 1 ORDER BY published_at DESC, created_at DESC";

        if ($limit) {
            $sql .= " LIMIT $limit";
        }

        return $this->query($sql)->fetchAll();
    }

    /**
     * Get pinned news
     */
    public function getPinned($limit = 3)
    {
        $sql = "SELECT * FROM tech_news WHERE is_published = 1 AND is_pinned = 1 ORDER BY published_at DESC LIMIT $limit";
        return $this->query($sql)->fetchAll();
    }

    /**
     * Get featured news
     */
    public function getFeatured($limit = 6)
    {
        $sql = "SELECT * FROM tech_news WHERE is_published = 1 AND is_featured = 1 ORDER BY published_at DESC LIMIT $limit";
        return $this->query($sql)->fetchAll();
    }

    /**
     * Get by category
     */
    public function getByCategory($category)
    {
        $sql = "SELECT * FROM tech_news WHERE is_published = 1 AND category = ? ORDER BY published_at DESC";
        return $this->query($sql, [$category])->fetchAll();
    }

    /**
     * Increment view count
     */
    public function incrementView($id)
    {
        $sql = "UPDATE tech_news SET view_count = view_count + 1 WHERE id = ?";
        return $this->query($sql, [$id]);
    }

    /**
     * Search news
     */
    public function search($keyword)
    {
        $sql = "SELECT * FROM tech_news
                WHERE is_published = 1 AND (title LIKE ? OR summary LIKE ? OR tags LIKE ?)
                ORDER BY published_at DESC";

        $keyword = "%$keyword%";
        return $this->query($sql, [$keyword, $keyword, $keyword])->fetchAll();
    }
}
