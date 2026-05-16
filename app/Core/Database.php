<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

final class Database
{
    private PDO $pdo;

    public function __construct()
    {
        // Get Laravel database config
        $config = config('database.connections.mysql');

        if (!is_array($config) || empty($config['host'])) {
            throw new \RuntimeException(
                'MySQL database configuration is missing. Ensure config/database.php exists and DB_* variables are set in .env.'
            );
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4;connect_timeout=5',
            $config['host'],
            (int) $config['port'],
            $config['database']
        );

        try {
            $this->pdo = new PDO(
                $dsn,
                $config['username'],
                $config['password'],
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        } catch (PDOException $e) {
            throw new \RuntimeException(
                'Database connection failed: ' . $e->getMessage(),
                (int) $e->getCode(),
                $e
            );
        }
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }
}