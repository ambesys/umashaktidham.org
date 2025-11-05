<?php

namespace App\Models;

use PDO;

class FamilyMember
{
    private $db;
    private $table = 'family_members';

    public function __construct(PDO $pdo = null)
    {
        if ($pdo) {
            $this->db = $pdo;
        } else {
            $cfg = __DIR__ . '/../../config/database.php';
            if (file_exists($cfg)) {
                require $cfg;
                $this->db = $pdo ?? ($pdo ?? ($GLOBALS['pdo'] ?? null));
            }
        }
        if (!$this->db) {
            throw new \RuntimeException('FamilyMember model requires a PDO instance (provide via constructor)');
        }
    }

    public function findById(int $id)
    {
        $stmt = $this->db->prepare("SELECT * FROM $this->table WHERE id = :id LIMIT 1");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function listByUserId(int $userId)
    {
        $stmt = $this->db->prepare("SELECT * FROM $this->table WHERE user_id = :user_id ORDER BY created_at DESC");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data)
    {
        $sql = "INSERT INTO $this->table (user_id, first_name, last_name, birth_year, gender, email, phone_e164, relationship, relationship_other, occupation, business_info) VALUES (:user_id, :first_name, :last_name, :birth_year, :gender, :email, :phone_e164, :relationship, :relationship_other, :occupation, :business_info)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $data['user_id'], PDO::PARAM_INT);
        $stmt->bindParam(':first_name', $data['first_name']);
        $stmt->bindParam(':last_name', $data['last_name']);
        $stmt->bindParam(':birth_year', $data['birth_year']);
        $stmt->bindParam(':gender', $data['gender']);
        $stmt->bindParam(':email', $data['email']);
        $stmt->bindParam(':phone_e164', $data['phone_e164']);
        $stmt->bindParam(':relationship', $data['relationship']);
        $stmt->bindParam(':relationship_other', $data['relationship_other']);
        $stmt->bindParam(':occupation', $data['occupation']);
        $stmt->bindParam(':business_info', $data['business_info']);
        return $stmt->execute();
    }

    public function update(int $id, array $data)
    {
        $fields = [];
        $params = [':id' => $id];
        $allowed = ['first_name','last_name','birth_year','gender','email','phone_e164','relationship','relationship_other','occupation','business_info'];
        foreach ($allowed as $f) {
            if (array_key_exists($f, $data)) {
                $fields[] = "$f = :$f";
                $params[":$f"] = $data[$f];
            }
        }
        if (empty($fields)) return false;
        $sql = "UPDATE $this->table SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        return $stmt->execute();
    }

    public function delete(int $id)
    {
        $stmt = $this->db->prepare("DELETE FROM $this->table WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
