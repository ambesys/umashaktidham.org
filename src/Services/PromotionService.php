<?php

namespace App\Services;

use PDO;

class PromotionService
{
    private $db;

    public function __construct(PDO $pdo)
    {
        $this->db = $pdo;
    }

    /**
     * Promote a family member to a site user.
     * - $familyMember is array data from family_members table
     * - $credentials: ['username'=>'', 'email'=>'', 'password'=>'']
     * Returns new user id on success or false.
     */
    public function promote(array $familyMember, array $credentials)
    {
        if (empty($familyMember) || empty($credentials['email']) || empty($credentials['password'])) {
            return false;
        }

        // create user
        $sql = "INSERT INTO users (username, name, email, password, role_id) VALUES (:username, :name, :email, :password, :role_id)";
        $stmt = $this->db->prepare($sql);
        $username = $credentials['username'] ?? strtolower(preg_replace('/[^a-z0-9]/', '', $familyMember['first_name'] . substr($familyMember['last_name'] ?? '',0,1))) . rand(10,99);
        $name = trim(($familyMember['first_name'] ?? '') . ' ' . ($familyMember['last_name'] ?? ''));
        $email = $credentials['email'];
        $hashed = password_hash($credentials['password'], PASSWORD_DEFAULT);

        // default role for promoted family member is 'user'
        $roleStmt = $this->db->prepare("SELECT id FROM roles WHERE name = 'user' LIMIT 1");
        $roleStmt->execute();
        $roleRow = $roleStmt->fetch(PDO::FETCH_ASSOC);
        $roleId = $roleRow['id'] ?? null;

        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $hashed);
        $stmt->bindParam(':role_id', $roleId, PDO::PARAM_INT);

        if (!$stmt->execute()) {
            return false;
        }

        $newUserId = (int)$this->db->lastInsertId();

        // create profile from family member data
        $profSql = "INSERT INTO profiles (user_id, first_name, last_name, birth_year, gender, phone_e164, email) VALUES (:user_id, :first_name, :last_name, :birth_year, :gender, :phone_e164, :email)";
        $pstmt = $this->db->prepare($profSql);
        $pstmt->bindParam(':user_id', $newUserId, PDO::PARAM_INT);
        $pstmt->bindParam(':first_name', $familyMember['first_name']);
        $pstmt->bindParam(':last_name', $familyMember['last_name']);
        $pstmt->bindParam(':birth_year', $familyMember['birth_year']);
        $pstmt->bindParam(':gender', $familyMember['gender']);
        $pstmt->bindParam(':phone_e164', $familyMember['phone_e164']);
        $pstmt->bindParam(':email', $familyMember['email']);
        $pstmt->execute();

        return $newUserId;
    }
}
