<?php

namespace App\Config;

use PDO;
use PDOException;

/**
 * Database Configuration Class
 * Singleton pattern สำหรับ PDO connection
 */
class Database
{
    private static $instance = null;
    private $connection;

    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct()
    {
        try {
            $dsn = sprintf(
                "mysql:host=%s;dbname=%s;charset=%s",
                $_ENV['DB_HOST'],
                $_ENV['DB_NAME'],
                $_ENV['DB_CHARSET']
            );

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ];

            $this->connection = new PDO(
                $dsn,
                $_ENV['DB_USER'],
                $_ENV['DB_PASS'],
                $options
            );

        } catch (PDOException $e) {
            throw new \Exception("Database Connection Error: " . $e->getMessage());
        }
    }

    /**
     * Get database connection instance (Singleton)
     */
    public static function getConnection()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance->connection;
    }

    /**
     * Prevent cloning of instance
     */
    private function __clone() {}

    /**
     * Prevent unserialization of instance
     */
    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize singleton");
    }
}
