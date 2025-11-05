<?php

namespace App\Models;

use PDO;

class Member
{
    private $db; // PDO instance

    public function __construct(?PDO $pdo = null)
    {
        if ($pdo instanceof PDO) {
            $this->db = $pdo;
            return;
        }

        // Fallback: try global $pdo from config
        if (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) {
            $this->db = $GLOBALS['pdo'];
            return;
        }

        // Last fallback: try to require config/database.php (non-throwing)
        $cfg = __DIR__ . '/../../config/database.php';
        if (file_exists($cfg)) {
            require_once $cfg;
            if (isset($pdo) && $pdo instanceof PDO) {
                $this->db = $pdo;
                return;
            }
            if (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) {
                $this->db = $GLOBALS['pdo'];
                return;
            }
        }

        throw new \RuntimeException('No PDO available for Member model');
    }

    public function create(array $data): bool
    {
        $sql = "INSERT INTO members (first_name, last_name, email, phone, address) VALUES (:first_name, :last_name, :email, :phone, :address)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':first_name' => $data['first_name'] ?? $data['name'] ?? null,
            ':last_name' => $data['last_name'] ?? null,
            ':email' => $data['email'] ?? null,
            ':phone' => $data['phone'] ?? null,
            ':address' => $data['address'] ?? null,
        ]);
    }

    public function getAll(): array
    {
        $sql = "SELECT * FROM members";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id)
    {
        $sql = "SELECT * FROM members WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function update($id, array $data): bool
    {
        $sql = "UPDATE members SET first_name = :first_name, last_name = :last_name, email = :email, phone = :phone, address = :address WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->bindValue(':first_name', $data['first_name'] ?? $data['name'] ?? null);
        $stmt->bindValue(':last_name', $data['last_name'] ?? null);
        $stmt->bindValue(':email', $data['email'] ?? null);
        $stmt->bindValue(':phone', $data['phone'] ?? null);
        $stmt->bindValue(':address', $data['address'] ?? null);
        return $stmt->execute();
    }

    public function delete($id): bool
    {
        $sql = "DELETE FROM members WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}