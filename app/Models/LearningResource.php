<?php

namespace App\Models;

use App\Core\Model;

/**
 * LearningResource Model
 * จัดการทรัพยากรการเรียนรู้
 */
class LearningResource extends Model
{
    protected $table = 'learning_resources';
    protected $primaryKey = 'id';
    protected $fillable = [
        'title', 'description', 'content', 'category',
        'tags', 'cover_image', 'attachment_file', 'file_type',
        'file_size', 'author', 'is_published', 'is_featured',
        'view_count', 'download_count'
    ];

    /**
     * Get published resources
     */
    public function getPublished($limit = null)
    {
        $sql = "SELECT * FROM learning_resources WHERE is_published = 1 ORDER BY created_at DESC";

        if ($limit) {
            $sql .= " LIMIT $limit";
        }

        return $this->query($sql)->fetchAll();
    }

    /**
     * Get featured resources
     */
    public function getFeatured($limit = 6)
    {
        $sql = "SELECT * FROM learning_resources WHERE is_published = 1 AND is_featured = 1 ORDER BY created_at DESC LIMIT $limit";
        return $this->query($sql)->fetchAll();
    }

    /**
     * Get by category
     */
    public function getByCategory($category)
    {
        $sql = "SELECT * FROM learning_resources WHERE is_published = 1 AND category = ? ORDER BY created_at DESC";
        return $this->query($sql, [$category])->fetchAll();
    }

    /**
     * Increment view count
     */
    public function incrementView($id)
    {
        $sql = "UPDATE learning_resources SET view_count = view_count + 1 WHERE id = ?";
        return $this->query($sql, [$id]);
    }

    /**
     * Increment download count
     */
    public function incrementDownload($id)
    {
        $sql = "UPDATE learning_resources SET download_count = download_count + 1 WHERE id = ?";
        return $this->query($sql, [$id]);
    }

    /**
     * Search resources
     */
    public function search($keyword)
    {
        $sql = "SELECT * FROM learning_resources
                WHERE is_published = 1 AND (title LIKE ? OR description LIKE ? OR tags LIKE ?)
                ORDER BY created_at DESC";

        $keyword = "%$keyword%";
        return $this->query($sql, [$keyword, $keyword, $keyword])->fetchAll();
    }
}
