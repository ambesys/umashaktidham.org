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
        // Fetch the primary user record first (including address fields)
        $sqlUser = "SELECT id, email, password, role_id, first_name, last_name, phone_e164, 
                    street_address, city, state, zip_code, country, created_at, updated_at 
                    FROM $this->table WHERE id = :id LIMIT 1";
        $stmtUser = $this->db->prepare($sqlUser);
        $stmtUser->bindParam(':id', $id, PDO::PARAM_INT);
        $stmtUser->execute();
        $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return null;
        }

        // Fetch the user's 'self' family member (if any) — single row
        $sqlFm = "SELECT first_name, last_name, birth_year, gender, phone_e164, occupation, business_info, village, mosal, relationship
                  FROM family_members WHERE user_id = :id AND LOWER(COALESCE(relationship, '')) = 'self' LIMIT 1";
        $stmtFm = $this->db->prepare($sqlFm);
        $stmtFm->bindParam(':id', $id, PDO::PARAM_INT);
        $stmtFm->execute();
        $fm = $stmtFm->fetch(PDO::FETCH_ASSOC);

        // Merge family-member fields into user result with fm_ prefix for backwards compatibility
        if ($fm && is_array($fm)) {
            foreach ($fm as $k => $v) {
                $user['fm_' . $k] = $v;
            }
        }

        // Compute display name from user first/last, else fallback to fm name or email
        $first = trim($user['first_name'] ?? '');
        $last = trim($user['last_name'] ?? '');
        $computedName = trim($first . ' ' . $last);

        if ($computedName !== '') {
            $user['name'] = $computedName;
        } else {
            $fmFirst = trim($user['fm_first_name'] ?? '');
            $fmLast = trim($user['fm_last_name'] ?? '');
            $fmName = trim($fmFirst . ' ' . $fmLast);
            if ($fmName !== '') {
                $user['name'] = $fmName;
            } else {
                $user['name'] = $user['email'] ?? 'User';
            }
        }

        return $user;
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

        // Build dynamic update statement based on provided fields
        $updates = [];
        $params = [':id' => $id];
        
        // Always update these if provided
        if (array_key_exists('name', $data)) {
            $updates[] = 'name = :name';
            $params[':name'] = $data['name'];
        }
        if (array_key_exists('email', $data)) {
            $updates[] = 'email = :email';
            $params[':email'] = $data['email'];
        }
        if (array_key_exists('first_name', $data)) {
            $updates[] = 'first_name = :first_name';
            $params[':first_name'] = $data['first_name'];
        }
        if (array_key_exists('last_name', $data)) {
            $updates[] = 'last_name = :last_name';
            $params[':last_name'] = $data['last_name'];
        }
        if (array_key_exists('phone_e164', $data)) {
            $updates[] = 'phone_e164 = :phone_e164';
            $params[':phone_e164'] = $data['phone_e164'];
        } elseif (array_key_exists('phone', $data)) {
            // Support phone field alias
            $updates[] = 'phone_e164 = :phone_e164';
            $params[':phone_e164'] = $data['phone'];
        }
        
        // Address fields
        if (array_key_exists('street_address', $data)) {
            $updates[] = 'street_address = :street_address';
            $params[':street_address'] = $data['street_address'];
        }
        if (array_key_exists('city', $data)) {
            $updates[] = 'city = :city';
            $params[':city'] = $data['city'];
        }
        if (array_key_exists('state', $data)) {
            $updates[] = 'state = :state';
            $params[':state'] = $data['state'];
        }
        if (array_key_exists('zip_code', $data)) {
            $updates[] = 'zip_code = :zip_code';
            $params[':zip_code'] = $data['zip_code'];
        }
        if (array_key_exists('country', $data)) {
            $updates[] = 'country = :country';
            $params[':country'] = $data['country'];
        }
        
        if ($roleId !== null) {
            $updates[] = 'role_id = :role_id';
            $params[':role_id'] = $roleId;
        }
        
        if (empty($updates)) {
            return false;  // Nothing to update
        }
        
        $sql = "UPDATE $this->table SET " . implode(', ', $updates) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        
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