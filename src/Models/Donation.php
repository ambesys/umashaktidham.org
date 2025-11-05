<?php

namespace App\Models;

use PDO;

class Donation
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

        throw new \RuntimeException('No PDO available for Donation model');
    }

    public function createDonation(array $data): bool
    {
        $query = "INSERT INTO donations (user_id, amount, message, created_at) VALUES (:user_id, :amount, :message, CURRENT_TIMESTAMP)";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([
            ':user_id' => $data['user_id'] ?? null,
            ':amount' => $data['amount'] ?? 0,
            ':message' => $data['message'] ?? null,
        ]);
    }

    public function getDonationsByUserId(int $userId): array
    {
        $query = "SELECT * FROM donations WHERE user_id = :user_id ORDER BY created_at DESC";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllDonations(): array
    {
        $query = "SELECT * FROM donations ORDER BY created_at DESC";
        $stmt = $this->db->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function deleteDonation(int $donationId): bool
    {
        $query = "DELETE FROM donations WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $donationId, PDO::PARAM_INT);
        return $stmt->execute();
    }
}