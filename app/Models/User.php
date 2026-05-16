<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

final class User
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findByEmailAndCompany(string $email, int $companyId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id AS user_id,
                    company_id,
                    COALESCE(full_name, name) AS full_name,
                    email,
                    COALESCE(password_hash, password) AS password_hash,
                    role_key,
                    is_active
             FROM users
             WHERE email = :email AND company_id = :company_id
             LIMIT 1'
        );

        $stmt->execute([
            'email' => $email,
            'company_id' => $companyId,
        ]);

        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findById(int $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id AS user_id,
                    company_id,
                    COALESCE(full_name, name) AS full_name,
                    email,
                    COALESCE(password_hash, password) AS password_hash,
                    role_key,
                    is_active
             FROM users
             WHERE id = :id
             LIMIT 1'
        );

        $stmt->execute([
            'id' => $userId,
        ]);

        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findPrimaryByCompany(int $companyId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id AS user_id,
                    company_id,
                    COALESCE(full_name, name) AS full_name,
                    email,
                    COALESCE(password_hash, password) AS password_hash,
                    role_key,
                    is_active
             FROM users
             WHERE company_id = :company_id
             ORDER BY FIELD(role_key, "admin", "manager", "user", "viewer") DESC, id ASC
             LIMIT 1'
        );

        $stmt->execute([
            'company_id' => $companyId,
        ]);

        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id AS user_id,
                    company_id,
                    email
             FROM users
             WHERE email = :email
             LIMIT 1'
        );

        $stmt->execute([
            'email' => $email,
        ]);

        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function updateEmail(int $userId, string $email): bool
    {
        try {
            $stmt = $this->pdo->prepare('UPDATE users SET email = :email WHERE id = :id');
            return $stmt->execute([
                'email' => $email,
                'id' => $userId,
            ]);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function createAdmin(int $companyId, string $fullName, string $email, string $passwordHash): int
    {
        // Determine which columns exist (handles both pre and post rename migration)
        $columns = ['email', 'company_id', 'role_key', 'is_active'];
        $values  = [
            'email'         => $email,
            'company_id'    => $companyId,
            'role_key'      => 'admin',
            'is_active'     => 1,
        ];

        // Check for name/full_name columns
        try {
            $cols = $this->pdo->query("SHOW COLUMNS FROM users LIKE 'name'")->fetchAll();
            $hasName = count($cols) > 0;
        } catch (\Throwable $e) {
            $hasName = false;
        }
        try {
            $cols = $this->pdo->query("SHOW COLUMNS FROM users LIKE 'full_name'")->fetchAll();
            $hasFullName = count($cols) > 0;
        } catch (\Throwable $e) {
            $hasFullName = false;
        }

        if ($hasFullName) {
            $columns[] = 'full_name';
            $values['full_name'] = $fullName;
        }
        if ($hasName) {
            $columns[] = 'name';
            $values['name'] = $fullName;
        }

        // Check for password/password_hash columns
        try {
            $cols = $this->pdo->query("SHOW COLUMNS FROM users LIKE 'password'")->fetchAll();
            $hasPassword = count($cols) > 0;
        } catch (\Throwable $e) {
            $hasPassword = false;
        }
        try {
            $cols = $this->pdo->query("SHOW COLUMNS FROM users LIKE 'password_hash'")->fetchAll();
            $hasPasswordHash = count($cols) > 0;
        } catch (\Throwable $e) {
            $hasPasswordHash = false;
        }

        if ($hasPasswordHash) {
            $columns[] = 'password_hash';
            $values['password_hash'] = $passwordHash;
        }
        if ($hasPassword) {
            $columns[] = 'password';
            $values['password'] = $passwordHash;
        }

        $placeholders = array_map(fn($c) => ':' . $c, $columns);
        $sql = 'INSERT INTO users (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($values);

        return (int) $this->pdo->lastInsertId();
    }

    public function createWithPassword(int $companyId, string $fullName, string $email, string $password, string $role = 'admin'): int
    {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $columns = ['company_id', 'email', 'role_key', 'is_active'];
        $values = [
            'company_id' => $companyId,
            'email' => $email,
            'role_key' => $role,
            'is_active' => 1,
        ];

        try {
            $cols = $this->pdo->query("SHOW COLUMNS FROM users LIKE 'full_name'")->fetchAll();
            $hasFullName = count($cols) > 0;
        } catch (\Throwable $e) {
            $hasFullName = false;
        }

        try {
            $cols = $this->pdo->query("SHOW COLUMNS FROM users LIKE 'name'")->fetchAll();
            $hasName = count($cols) > 0;
        } catch (\Throwable $e) {
            $hasName = false;
        }

        if ($hasFullName) {
            $columns[] = 'full_name';
            $values['full_name'] = $fullName;
        }
        if ($hasName) {
            $columns[] = 'name';
            $values['name'] = $fullName;
        }

        try {
            $cols = $this->pdo->query("SHOW COLUMNS FROM users LIKE 'password_hash'")->fetchAll();
            $hasPasswordHash = count($cols) > 0;
        } catch (\Throwable $e) {
            $hasPasswordHash = false;
        }

        try {
            $cols = $this->pdo->query("SHOW COLUMNS FROM users LIKE 'password'")->fetchAll();
            $hasPassword = count($cols) > 0;
        } catch (\Throwable $e) {
            $hasPassword = false;
        }

        if ($hasPasswordHash) {
            $columns[] = 'password_hash';
            $values['password_hash'] = $hashedPassword;
        }
        if ($hasPassword) {
            $columns[] = 'password';
            $values['password'] = $hashedPassword;
        }

        $placeholders = array_map(fn(string $c) => ':' . $c, $columns);
        $sql = 'INSERT INTO users (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($values);

        return (int) $this->pdo->lastInsertId();
    }

    public function updatePassword(int $userId, string $password): bool
    {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        try {
            $updated = false;
            $cols = $this->pdo->query("SHOW COLUMNS FROM users LIKE 'password_hash'")->fetchAll();
            if (count($cols) > 0) {
                $stmt = $this->pdo->prepare('UPDATE users SET password_hash = :password_hash WHERE id = :id');
                $stmt->execute(['password_hash' => $hashedPassword, 'id' => $userId]);
                $updated = true;
            }

            $cols = $this->pdo->query("SHOW COLUMNS FROM users LIKE 'password'")->fetchAll();
            if (count($cols) > 0) {
                $stmt = $this->pdo->prepare('UPDATE users SET password = :password WHERE id = :id');
                $stmt->execute(['password' => $hashedPassword, 'id' => $userId]);
                $updated = true;
            }

            return $updated;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
