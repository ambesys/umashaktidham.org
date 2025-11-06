<?php

namespace App\Services;

use PDO;

class UserService
{
    private $pdo;

    public function __construct(PDO $pdo = null)
    {
        if ($pdo) {
            $this->pdo = $pdo;
            return;
        }
        $cfg = __DIR__ . '/../../config/database.php';
        if (file_exists($cfg)) {
            require $cfg; // expects $pdo
            if (isset($pdo) && $pdo instanceof PDO) {
                $this->pdo = $pdo;
            }
        }
        if (!$this->pdo) {
            throw new \RuntimeException('UserService requires a PDO instance');
        }
    }

    public function updateUser(array $data)
    {
        // Prepare fields and parameters for the update query
        $fields = [];
        $params = [':id' => $data['id']];

        if (isset($data['email'])) {
            $fields[] = 'email = :email';
            $params[':email'] = $data['email'];
        }
        if (isset($data['password'])) {
            $fields[] = 'password = :password';
            $params[':password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }
        if (isset($data['first_name'])) {
            $fields[] = 'first_name = :first_name';
            $params[':first_name'] = $data['first_name'];
        }
        if (isset($data['last_name'])) {
            $fields[] = 'last_name = :last_name';
            $params[':last_name'] = $data['last_name'];
        }
        if (isset($data['relation'])) {
            $fields[] = 'relation = :relation';
            $params[':relation'] = $data['relation'];
        }

        if (empty($fields)) {
            return false; // No fields to update
        }

        $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        return $stmt->execute();
    }
}
