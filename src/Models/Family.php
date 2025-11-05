<?php

namespace App\Models;

use PDO;

class Family
{
    private $db; // PDO

    public function __construct(?PDO $pdo = null)
    {
        if ($pdo instanceof PDO) {
            $this->db = $pdo;
            return;
        }

        if (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) {
            $this->db = $GLOBALS['pdo'];
            return;
        }

        $cfg = __DIR__ . '/../../config/database.php';
        if (file_exists($cfg)) {
            require_once $cfg;
            if (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) {
                $this->db = $GLOBALS['pdo'];
                return;
            }
        }

        throw new \RuntimeException('No PDO available for Family model');
    }

    public function createFamily(int $userId, array $familyData): bool
    {
        $query = "INSERT INTO families (family_name, created_by_user_id, address_street, address_city, address_state, address_zip) VALUES (:family_name, :created_by_user_id, :street, :city, :state, :zip)";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([
            ':family_name' => $familyData['family_name'] ?? $familyData['name'] ?? null,
            ':created_by_user_id' => $userId,
            ':street' => $familyData['address_street'] ?? null,
            ':city' => $familyData['address_city'] ?? null,
            ':state' => $familyData['address_state'] ?? null,
            ':zip' => $familyData['address_zip'] ?? null,
        ]);
    }

    public function getFamilyByUserId(int $userId): array
    {
        $query = "SELECT * FROM families WHERE created_by_user_id = :user_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateFamily(int $familyId, array $familyData): bool
    {
        $query = "UPDATE families SET family_name = :name, address_street = :street, address_city = :city, address_state = :state, address_zip = :zip WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $familyId, PDO::PARAM_INT);
        $stmt->bindValue(':name', $familyData['family_name'] ?? $familyData['name'] ?? null);
        $stmt->bindValue(':street', $familyData['address_street'] ?? null);
        $stmt->bindValue(':city', $familyData['address_city'] ?? null);
        $stmt->bindValue(':state', $familyData['address_state'] ?? null);
        $stmt->bindValue(':zip', $familyData['address_zip'] ?? null);
        return $stmt->execute();
    }

    public function deleteFamily(int $familyId): bool
    {
        $query = "DELETE FROM families WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $familyId, PDO::PARAM_INT);
        return $stmt->execute();
    }
}