<?php

namespace App\Models;

use PDO;

class Profile
{
    private $db;
    private $table = 'profiles';

    public function __construct(PDO $pdo = null)
    {
        if ($pdo) {
            $this->db = $pdo;
        } else {
            // fallback to config DB
            $cfg = __DIR__ . '/../../config/database.php';
            if (file_exists($cfg)) {
                require $cfg;
                $this->db = $pdo ?? ($pdo ?? ($GLOBALS['pdo'] ?? null));
            }
        }
        if (!$this->db) {
            throw new \RuntimeException('Profile model requires a PDO instance (provide via constructor)');
        }
    }

    public function findByUserId(int $userId)
    {
        $stmt = $this->db->prepare("SELECT * FROM $this->table WHERE user_id = :user_id LIMIT 1");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create(array $data)
    {
        $sql = "INSERT INTO $this->table (user_id, first_name, last_name, birth_year, gender, phone_e164, address_street, address_city, address_state, address_zip, occupation, business_name, business_phone_e164) VALUES (:user_id, :first_name, :last_name, :birth_year, :gender, :phone_e164, :address_street, :address_city, :address_state, :address_zip, :occupation, :business_name, :business_phone_e164)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $data['user_id'], PDO::PARAM_INT);
        $stmt->bindParam(':first_name', $data['first_name']);
        $stmt->bindParam(':last_name', $data['last_name']);
        $stmt->bindParam(':birth_year', $data['birth_year']);
        $stmt->bindParam(':gender', $data['gender']);
        $stmt->bindParam(':phone_e164', $data['phone_e164']);
        $stmt->bindParam(':address_street', $data['address_street']);
        $stmt->bindParam(':address_city', $data['address_city']);
        $stmt->bindParam(':address_state', $data['address_state']);
        $stmt->bindParam(':address_zip', $data['address_zip']);
        $stmt->bindParam(':occupation', $data['occupation']);
        $stmt->bindParam(':business_name', $data['business_name']);
        $stmt->bindParam(':business_phone_e164', $data['business_phone_e164']);
        return $stmt->execute();
    }

    public function updateByUserId(int $userId, array $data)
    {
        $fields = [];
        $params = [':user_id' => $userId];
        $allowed = ['first_name','last_name','birth_year','gender','phone_e164','address_street','address_city','address_state','address_zip','occupation','business_name','business_phone_e164'];
        foreach ($allowed as $f) {
            if (array_key_exists($f, $data)) {
                $fields[] = "$f = :$f";
                $params[":$f"] = $data[$f];
            }
        }
        if (empty($fields)) return false;
        $sql = "UPDATE $this->table SET " . implode(', ', $fields) . " WHERE user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        return $stmt->execute();
    }
}
