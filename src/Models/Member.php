<?php

namespace App\Models;

use Database\Database;

class Member
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    public function create($data)
    {
        // Code to insert a new member into the database
        $sql = "INSERT INTO members (name, email, phone, address) VALUES (:name, :email, :phone, :address)";
        $this->db->query($sql);
        $this->db->bind(':name', $data['name']);
        $this->db->bind(':email', $data['email']);
        $this->db->bind(':phone', $data['phone']);
        $this->db->bind(':address', $data['address']);
        return $this->db->execute();
    }

    public function getAll()
    {
        // Code to retrieve all members from the database
        $sql = "SELECT * FROM members";
        $this->db->query($sql);
        return $this->db->resultSet();
    }

    public function getById($id)
    {
        // Code to retrieve a member by ID
        $sql = "SELECT * FROM members WHERE id = :id";
        $this->db->query($sql);
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    public function update($id, $data)
    {
        // Code to update member details
        $sql = "UPDATE members SET name = :name, email = :email, phone = :phone, address = :address WHERE id = :id";
        $this->db->query($sql);
        $this->db->bind(':name', $data['name']);
        $this->db->bind(':email', $data['email']);
        $this->db->bind(':phone', $data['phone']);
        $this->db->bind(':address', $data['address']);
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    public function delete($id)
    {
        // Code to delete a member
        $sql = "DELETE FROM members WHERE id = :id";
        $this->db->query($sql);
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }
}