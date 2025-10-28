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

    public function create($data)
    {
        $stmt = $this->db->prepare("INSERT INTO $this->table (name, email, password, role) VALUES (:name, :email, :password, :role)");
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':email', $data['email']);
        $stmt->bindParam(':password', password_hash($data['password'], PASSWORD_DEFAULT));
        $stmt->bindParam(':role', $data['role']);
        return $stmt->execute();
    }

    public function findByEmail($email)
    {
        $stmt = $this->db->prepare("SELECT * FROM $this->table WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function update($id, $data)
    {
        $stmt = $this->db->prepare("UPDATE $this->table SET name = :name, email = :email, role = :role WHERE id = :id");
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':email', $data['email']);
        $stmt->bindParam(':role', $data['role']);
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
        $stmt = $this->db->query("SELECT * FROM $this->table");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}