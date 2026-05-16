<?php

declare(strict_types=1);

namespace App\Models;

use PDO;
use App\Support\SchemaCompat;

final class Customer
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function listByCompany(int $companyId, int $limit = 200): array
    {
        try {
            $stmt = $this->pdo->prepare(
                'SELECT customer_id, customer_name,
                        email, phone, address, city, province, postal_code, country,
                        tax_number,
                        ' . (SchemaCompat::hasColumn('customers', 'id_number') ? 'id_number,' : "'' AS id_number,") . '
                        notes, status, created_at
                 FROM customers
                 WHERE company_id = :company_id
                 ORDER BY customer_id DESC
                 LIMIT :limit'
            );
            $stmt->bindValue(':company_id', $companyId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll() ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function findByIdForCompany(int $customerId, int $companyId): ?array
    {
        try {
            $stmt = $this->pdo->prepare(
                'SELECT customer_id, customer_name,
                        email, phone, address, city, province, postal_code, country,
                        tax_number,
                        ' . (SchemaCompat::hasColumn('customers', 'id_number') ? 'id_number,' : "'' AS id_number,") . '
                        notes, status, created_at
                 FROM customers
                 WHERE customer_id = :customer_id AND company_id = :company_id
                 LIMIT 1'
            );
            $stmt->execute(['customer_id' => $customerId, 'company_id' => $companyId]);
            $row = $stmt->fetch();

            return $row ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function findByNameForCompany(string $customerName, int $companyId): ?array
    {
        try {
            $stmt = $this->pdo->prepare(
                'SELECT customer_id, customer_name,
                        email, phone, address, city, province, postal_code, country,
                        tax_number,
                        ' . (SchemaCompat::hasColumn('customers', 'id_number') ? 'id_number,' : "'' AS id_number,") . '
                        notes, status, created_at
                 FROM customers
                 WHERE company_id = :company_id AND LOWER(customer_name) = LOWER(:customer_name)
                 LIMIT 1'
            );
            $stmt->execute(['company_id' => $companyId, 'customer_name' => $customerName]);
            $row = $stmt->fetch();

            return $row ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function createForCompany(int $companyId, string $name, ?string $taxNumber, ?string $email, ?string $phone, ?string $address, ?string $notes, ?string $city = null, ?string $province = null, ?string $postalCode = null, ?string $country = null, ?string $idNumber = null): int
    {
        try {
            $hasIdNumber = SchemaCompat::hasColumn('customers', 'id_number');
            $idCol = $hasIdNumber ? ', id_number' : '';
            $idVal = $hasIdNumber ? ', :id_number' : '';

            $stmt = $this->pdo->prepare(
                'INSERT INTO customers (company_id, customer_name, tax_number' . $idCol . ', email, phone, address, city, province, postal_code, country, notes, status, created_at, updated_at)
                 VALUES (:company_id, :customer_name, :tax_number' . $idVal . ', :email, :phone, :address, :city, :province, :postal_code, :country, :notes, :status, NOW(), NOW())'
            );
            $params = [
                'company_id' => $companyId,
                'customer_name' => $name,
                'tax_number' => $taxNumber,
                'email' => $email,
                'phone' => $phone,
                'address' => $address,
                'city' => $city,
                'province' => $province,
                'postal_code' => $postalCode,
                'country' => $country,
                'notes' => $notes,
                'status' => 'active',
            ];
            if ($hasIdNumber) {
                $params['id_number'] = $idNumber;
            }
            $stmt->execute($params);

            return (int) $this->pdo->lastInsertId();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    public function updateForCompany(int $customerId, int $companyId, string $name, ?string $taxNumber, ?string $email, ?string $phone, ?string $address, ?string $notes, string $status = 'active', ?string $city = null, ?string $province = null, ?string $postalCode = null, ?string $country = null, ?string $idNumber = null): bool
    {
        try {
            $hasIdNumber = SchemaCompat::hasColumn('customers', 'id_number');
            $idSet = $hasIdNumber ? ', id_number = :id_number' : '';

            $stmt = $this->pdo->prepare(
                'UPDATE customers
                 SET customer_name = :customer_name,
                     tax_number = :tax_number' . $idSet . ',
                     email = :email,
                     phone = :phone,
                     address = :address,
                     city = :city,
                     province = :province,
                     postal_code = :postal_code,
                     country = :country,
                     notes = :notes,
                     status = :status,
                     updated_at = NOW()
                 WHERE customer_id = :customer_id AND company_id = :company_id'
            );
            $params = [
                'customer_name' => $name,
                'tax_number' => $taxNumber,
                'email' => $email,
                'phone' => $phone,
                'address' => $address,
                'city' => $city,
                'province' => $province,
                'postal_code' => $postalCode,
                'country' => $country,
                'notes' => $notes,
                'status' => $status,
                'customer_id' => $customerId,
                'company_id' => $companyId,
            ];
            if ($hasIdNumber) {
                $params['id_number'] = $idNumber;
            }
            $stmt->execute($params);

            return $stmt->rowCount() > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function deleteForCompany(int $customerId, int $companyId): bool
    {
        try {
            $stmt = $this->pdo->prepare('DELETE FROM customers WHERE customer_id = :customer_id AND company_id = :company_id');
            $stmt->execute(['customer_id' => $customerId, 'company_id' => $companyId]);

            return $stmt->rowCount() > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
