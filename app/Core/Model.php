<?php

namespace App\Core;

use App\Config\Database;
use PDO;

/**
 * Base Model Class
 * คลาสพื้นฐานสำหรับ Models ทั้งหมด พร้อม CRUD operations
 */
class Model
{
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    protected $fillable = [];

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Find record by primary key
     */
    public function find($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Get all records
     */
    public function all()
    {
        $stmt = $this->db->query("SELECT * FROM {$this->table}");
        return $stmt->fetchAll();
    }

    /**
     * Get records with WHERE condition
     */
    public function where($column, $operator, $value = null)
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE {$column} {$operator} ?");
        $stmt->execute([$value]);
        return $stmt->fetchAll();
    }

    /**
     * Get first record matching condition
     */
    public function first($column, $operator, $value = null)
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE {$column} {$operator} ? LIMIT 1");
        $stmt->execute([$value]);
        return $stmt->fetch();
    }

    /**
     * Insert new record
     */
    public function create($data)
    {
        // Filter only fillable fields
        if (!empty($this->fillable)) {
            $data = array_intersect_key($data, array_flip($this->fillable));
        }

        $fields = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $stmt = $this->db->prepare("INSERT INTO {$this->table} ({$fields}) VALUES ({$placeholders})");
        $stmt->execute(array_values($data));

        return $this->db->lastInsertId();
    }

    /**
     * Update existing record
     */
    public function update($id, $data)
    {
        // Filter only fillable fields
        if (!empty($this->fillable)) {
            $data = array_intersect_key($data, array_flip($this->fillable));
        }

        $fields = implode(' = ?, ', array_keys($data)) . ' = ?';

        $stmt = $this->db->prepare("UPDATE {$this->table} SET {$fields} WHERE {$this->primaryKey} = ?");
        $values = array_values($data);
        $values[] = $id;

        return $stmt->execute($values);
    }

    /**
     * Delete record
     */
    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Execute custom query
     */
    protected function query($sql, $params = [])
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Begin transaction
     */
    protected function beginTransaction()
    {
        return $this->db->beginTransaction();
    }

    /**
     * Commit transaction
     */
    protected function commit()
    {
        return $this->db->commit();
    }

    /**
     * Rollback transaction
     */
    protected function rollback()
    {
        return $this->db->rollBack();
    }

    /**
     * Count records
     */
    public function count($column = null, $value = null)
    {
        if ($column && $value) {
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM {$this->table} WHERE {$column} = ?");
            $stmt->execute([$value]);
        } else {
            $stmt = $this->db->query("SELECT COUNT(*) as count FROM {$this->table}");
        }

        $result = $stmt->fetch();
        return $result['count'];
    }

    /**
     * Check if record exists
     */
    public function exists($column, $value)
    {
        return $this->count($column, $value) > 0;
    }
}
