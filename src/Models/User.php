<?php

namespace App\Models;

use PDO;

class User
{
    private $db;
    private $table = 'users';

    public function __construct($database)
    {
        $this->db = $database;
    }

    /**
     * Create a new user. $data may contain 'role' (role name) or 'role_id'.
     */
    public function create($data)
    {
        // resolve role_id if role name provided
        $roleId = null;
        if (!empty($data['role_id'])) {
            $roleId = (int)$data['role_id'];
        } elseif (!empty($data['role'])) {
            $roleId = $this->getRoleIdByName($data['role']);
        }

        $stmt = $this->db->prepare("INSERT INTO $this->table (name, email, password, role_id) VALUES (:name, :email, :password, :role_id)");
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':email', $data['email']);
        $hashed = password_hash($data['password'], PASSWORD_DEFAULT);
        $stmt->bindParam(':password', $hashed);
        $stmt->bindParam(':role_id', $roleId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function findByEmail($email)
    {
        $stmt = $this->db->prepare("SELECT * FROM $this->table WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function find($id)
    {
        // Join with family_members to get complete user profile
        // The user's main record is the one with relationship='Self'
        $sql = "SELECT 
                    u.id,
                    u.email,
                    u.password,
                    u.role_id,
                    u.first_name,
                    u.last_name,
                    u.created_at,
                    u.updated_at,
                    CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) AS name,
                    fm.birth_year,
                    fm.gender,
                    fm.phone_e164,
                    fm.occupation,
                    fm.business_info,
                    fm.village,
                    fm.mosal,
                    fm.relationship
                FROM $this->table u
                LEFT JOIN family_members fm ON u.id = fm.user_id AND (fm.relationship = 'Self' OR fm.relationship = 'self')
                WHERE u.id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Trim whitespace from name field
        if ($result && isset($result['name'])) {
            $result['name'] = trim($result['name']);
        }
        
        return $result;
    }

    public function update($id, $data)
    {
        // resolve role_id if provided
        $roleId = null;
        if (!empty($data['role_id'])) {
            $roleId = (int)$data['role_id'];
        } elseif (!empty($data['role'])) {
            $roleId = $this->getRoleIdByName($data['role']);
        }

        if ($roleId !== null) {
            $stmt = $this->db->prepare("UPDATE $this->table SET name = :name, email = :email, role_id = :role_id WHERE id = :id");
            $stmt->bindParam(':role_id', $roleId, PDO::PARAM_INT);
        } else {
            $stmt = $this->db->prepare("UPDATE $this->table SET name = :name, email = :email WHERE id = :id");
        }

        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':email', $data['email']);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM $this->table WHERE id = :id");
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function getAllUsers()
    {
        // join roles to include role name
        $sql = "SELECT u.*, r.name AS role_name FROM $this->table u LEFT JOIN roles r ON u.role_id = r.id";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Resolve role id by role name. Returns null if not found.
     */
    private function getRoleIdByName($roleName)
    {
        $stmt = $this->db->prepare("SELECT id FROM roles WHERE name = :name LIMIT 1");
        $stmt->bindParam(':name', $roleName);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['id'] ?? null;
    }
}