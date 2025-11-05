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
        // Insert into family_members table to match migrations
        $sql = "INSERT INTO family_members (family_id, user_id, first_name, last_name, birth_year, gender, email, phone_e164, relationship, relationship_other, occupation, business_info, created_at) VALUES (:family_id, :user_id, :first_name, :last_name, :birth_year, :gender, :email, :phone_e164, :relationship, :relationship_other, :occupation, :business_info, CURRENT_TIMESTAMP)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':family_id' => $data['family_id'] ?? null,
            ':user_id' => $data['user_id'] ?? null,
            ':first_name' => $data['first_name'] ?? $data['name'] ?? null,
            ':last_name' => $data['last_name'] ?? null,
            ':birth_year' => $data['birth_year'] ?? null,
            ':gender' => $data['gender'] ?? null,
            ':email' => $data['email'] ?? null,
            ':phone_e164' => $data['phone'] ?? $data['phone_e164'] ?? null,
            ':relationship' => $data['relationship'] ?? null,
            ':relationship_other' => $data['relationship_other'] ?? null,
            ':occupation' => $data['occupation'] ?? null,
            ':business_info' => $data['business_info'] ?? null,
        ]);
    }

    /**
     * Get all family members or members for a specific user
     * @param int|null $userId
     * @return array
     */
    public function getAll(?int $userId = null): array
    {
        if ($userId !== null) {
            $sql = "SELECT * FROM family_members WHERE user_id = :user_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $sql = "SELECT * FROM family_members";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id)
    {
        $sql = "SELECT * FROM family_members WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function update($id, array $data): bool
    {
        $sql = "UPDATE family_members SET family_id = :family_id, user_id = :user_id, first_name = :first_name, last_name = :last_name, birth_year = :birth_year, gender = :gender, email = :email, phone_e164 = :phone_e164, relationship = :relationship, relationship_other = :relationship_other, occupation = :occupation, business_info = :business_info WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':family_id', $data['family_id'] ?? null);
        $stmt->bindValue(':user_id', $data['user_id'] ?? null);
        $stmt->bindValue(':first_name', $data['first_name'] ?? $data['name'] ?? null);
        $stmt->bindValue(':last_name', $data['last_name'] ?? null);
        $stmt->bindValue(':birth_year', $data['birth_year'] ?? null);
        $stmt->bindValue(':gender', $data['gender'] ?? null);
        $stmt->bindValue(':email', $data['email'] ?? null);
        $stmt->bindValue(':phone_e164', $data['phone'] ?? $data['phone_e164'] ?? null);
        $stmt->bindValue(':relationship', $data['relationship'] ?? null);
        $stmt->bindValue(':relationship_other', $data['relationship_other'] ?? null);
        $stmt->bindValue(':occupation', $data['occupation'] ?? null);
        $stmt->bindValue(':business_info', $data['business_info'] ?? null);
        return $stmt->execute();
    }

    public function delete($id): bool
    {
        $sql = "DELETE FROM family_members WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}