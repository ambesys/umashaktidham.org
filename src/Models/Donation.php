<?php

namespace App\Models;

use Database\Database;

class Donation
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    public function createDonation($data)
    {
        $query = "INSERT INTO donations (user_id, amount, message, created_at) VALUES (:user_id, :amount, :message, NOW())";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $data['user_id']);
        $stmt->bindParam(':amount', $data['amount']);
        $stmt->bindParam(':message', $data['message']);
        return $stmt->execute();
    }

    public function getDonationsByUserId($userId)
    {
        $query = "SELECT * FROM donations WHERE user_id = :user_id ORDER BY created_at DESC";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getAllDonations()
    {
        $query = "SELECT * FROM donations ORDER BY created_at DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function deleteDonation($donationId)
    {
        $query = "DELETE FROM donations WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $donationId);
        return $stmt->execute();
    }
}