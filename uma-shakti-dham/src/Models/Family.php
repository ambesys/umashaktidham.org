<?php

namespace App\Models;

use Database\Database;

class Family
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    public function createFamily($userId, $familyData)
    {
        // Code to insert family data into the database
        $query = "INSERT INTO families (user_id, family_member_name, relationship, age) VALUES (:user_id, :name, :relationship, :age)";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':name', $familyData['name']);
        $stmt->bindParam(':relationship', $familyData['relationship']);
        $stmt->bindParam(':age', $familyData['age']);
        return $stmt->execute();
    }

    public function getFamilyByUserId($userId)
    {
        // Code to retrieve family details by user ID
        $query = "SELECT * FROM families WHERE user_id = :user_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function updateFamily($familyId, $familyData)
    {
        // Code to update family details
        $query = "UPDATE families SET family_member_name = :name, relationship = :relationship, age = :age WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $familyId);
        $stmt->bindParam(':name', $familyData['name']);
        $stmt->bindParam(':relationship', $familyData['relationship']);
        $stmt->bindParam(':age', $familyData['age']);
        return $stmt->execute();
    }

    public function deleteFamily($familyId)
    {
        // Code to delete a family member
        $query = "DELETE FROM families WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $familyId);
        return $stmt->execute();
    }
}