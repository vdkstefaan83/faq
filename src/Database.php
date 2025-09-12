<?php

namespace App;

use PDO;
use PDOException;

class Database
{
    private PDO $connection;
    
    public function __construct(
        private ?string $host = null,
        private ?string $db_name = null,
        private ?string $username = null,
        private ?string $password = null
    ) {
        $this->host = $host ?? $_ENV['DB_HOST'] ?? 'localhost';
        $this->db_name = $db_name ?? $_ENV['DB_NAME'] ?? 'faq_system';
        $this->username = $username ?? $_ENV['DB_USERNAME'] ?? 'root';
        $this->password = $password ?? $_ENV['DB_PASSWORD'] ?? '';
        
        $this->connect();
    }
    
    private function connect(): void
    {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset=utf8mb4";
            $this->connection = new PDO($dsn, $this->username, $this->password);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            throw new PDOException("Connection failed: " . $e->getMessage());
        }
    }
    
    public function get_connection(): PDO
    {
        return $this->connection;
    }
}