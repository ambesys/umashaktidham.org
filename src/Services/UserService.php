<?php

namespace App\Services;

use PDO;
use App\Services\LoggerService;

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

    /**
     * Validate birth year format and range.
     * Birth year must be a 4-digit number between (current year - 120) and current year.
     *
     * @param mixed $birthYear The birth year to validate
     * @return bool True if valid, false otherwise
     */
    private function isValidBirthYear($birthYear)
    {
        // Must be exactly 4 digits
        if (!is_numeric($birthYear) || !preg_match('/^\d{4}$/', (string)$birthYear)) {
            return false;
        }

        $year = (int)$birthYear;
        $currentYear = (int)date('Y');
        $minYear = $currentYear - 120;
        $maxYear = $currentYear;

        return $year >= $minYear && $year <= $maxYear;
    }

    /**
     * Update user and their main family_members record atomically.
     * Ensures consistency across both tables.
     *
     * @param array $data User data to update (must include 'id')
     * @return bool True on success, false on failure
     * @throws \Exception On database errors
     */
    public function updateUser(array $data)
    {
        // Validate required ID field
        if (!isset($data['id']) || !is_numeric($data['id'])) {
            LoggerService::warning('UserService::updateUser - Invalid user ID: ' . json_encode($data));
            throw new \InvalidArgumentException('User ID is required and must be numeric');
        }

        // Validate birth_year if provided
        if (isset($data['birth_year']) && $data['birth_year'] !== null && $data['birth_year'] !== '') {
            if (!$this->isValidBirthYear($data['birth_year'])) {
                LoggerService::warning('UserService::updateUser - Invalid birth year: ' . $data['birth_year']);
                throw new \InvalidArgumentException('Birth year must be a 4-digit year between ' . (date('Y') - 120) . ' and ' . date('Y'));
            }
        }

        $userId = (int)$data['id'];

        // Prepare fields and parameters for the update query
        $fields = [];
        $params = [':id' => $userId];

        // List of all allowed fields for users table
        $allowedUserFields = [
            'email','password','first_name','last_name',
            'occupation','business_info','village','mosal',
            'street_address','address_line_2','zip_code','city','state'
        ];
        foreach ($allowedUserFields as $f) {
            if (isset($data[$f])) {
                if ($f === 'password') {
                    $fields[] = "$f = :$f";
                    $params[":$f"] = password_hash($data[$f], PASSWORD_BCRYPT);
                } else {
                    $fields[] = "$f = :$f";
                    $params[":$f"] = $data[$f];
                }
            }
        }

        // Remove 'relationship' from users table update, but keep for family_members
        // This ensures 'relationship' is only updated in family_members, not users
        if (isset($data['relationship'])) {
            // Save value for later, but do not update in users table
            $relationship_for_family = $data['relationship'];
            unset($data['relationship']);
        }

        try {
            // Start transaction for atomicity
            $this->pdo->beginTransaction();

            // Update users table (only if there are fields to update)
            $userResult = true; // Default: no users update needed
            if (!empty($fields)) {
                $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = :id';
                $stmt = $this->pdo->prepare($sql);

                if (!$stmt) {
                    throw new \RuntimeException('Failed to prepare update statement for users table');
                }

                foreach ($params as $key => $value) {
                    $stmt->bindValue($key, $value);
                }

                $userResult = $stmt->execute();

                if (!$userResult) {
                    throw new \RuntimeException('Failed to update users table for user ' . $userId);
                }
            }

            // --- Update main user's family_members record for consistency ---
            // Find the main family_members record (relationship = 'Self')
            $familyMemberId = null;
            $findSql = "SELECT id FROM family_members WHERE user_id = :user_id AND (relationship = 'Self' OR relationship = 'self') LIMIT 1";
            $findStmt = $this->pdo->prepare($findSql);
            
            if (!$findStmt) {
                throw new \RuntimeException('Failed to prepare find statement for family_members');
            }

            $findStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            
            if (!$findStmt->execute()) {
                throw new \RuntimeException('Failed to find family_members record for user ' . $userId);
            }

            $row = $findStmt->fetch(PDO::FETCH_ASSOC);
            if ($row && isset($row['id'])) {
                $familyMemberId = $row['id'];
            }

            // Only update fields that exist in family_members
            $allowedFm = [
                'first_name','last_name','birth_year','gender','email','phone_e164','relationship','relationship_other',
                'occupation','business_info','village','mosal',
                'street_address','address_line_2','zip_code','city','state'
            ];
            $updateData = [];
            foreach ($allowedFm as $f) {
                if ($f === 'relationship' && isset($relationship_for_family)) {
                    $updateData[$f] = $relationship_for_family;
                } elseif (array_key_exists($f, $data)) {
                    $updateData[$f] = $data[$f];
                }
            }

            if (!empty($updateData)) {
                if ($familyMemberId) {
                    // UPDATE existing family_members record
                    $fieldsFm = [];
                    $paramsFm = [':id' => $familyMemberId];
                    foreach ($updateData as $f => $v) {
                        $fieldsFm[] = "$f = :$f";
                        $paramsFm[":$f"] = $v;
                    }
                    $sqlFm = "UPDATE family_members SET " . implode(', ', $fieldsFm) . " WHERE id = :id";
                    $stmtFm = $this->pdo->prepare($sqlFm);
                    
                    if (!$stmtFm) {
                        throw new \RuntimeException('Failed to prepare update statement for family_members');
                    }

                    foreach ($paramsFm as $k => $v) {
                        $stmtFm->bindValue($k, $v);
                    }
                    
                    if (!$stmtFm->execute()) {
                        throw new \RuntimeException('Failed to update family_members table for user ' . $userId);
                    }
                } else {
                    // CREATE new family_members record if one doesn't exist
                    // This handles the case where a user has never updated their profile before
                    $createData = $updateData;
                    $createData['user_id'] = $userId;
                    $createData['relationship'] = 'Self';
                    // Set defaults for required fields
                    if (!isset($createData['first_name'])) $createData['first_name'] = '';
                    if (!isset($createData['last_name'])) $createData['last_name'] = '';
                    if (!isset($createData['email'])) $createData['email'] = '';
                    if (!isset($createData['phone_e164'])) $createData['phone_e164'] = '';
                    if (!isset($createData['birth_year'])) $createData['birth_year'] = null;
                    if (!isset($createData['gender'])) $createData['gender'] = null;
                    if (!isset($createData['occupation'])) $createData['occupation'] = '';
                    if (!isset($createData['business_info'])) $createData['business_info'] = '';
                    if (!isset($createData['village'])) $createData['village'] = '';
                    if (!isset($createData['mosal'])) $createData['mosal'] = '';
                    if (!isset($createData['relationship_other'])) $createData['relationship_other'] = '';
                    if (!isset($createData['street_address'])) $createData['street_address'] = '';
                    if (!isset($createData['address_line_2'])) $createData['address_line_2'] = '';
                    if (!isset($createData['zip_code'])) $createData['zip_code'] = '';
                    if (!isset($createData['city'])) $createData['city'] = '';
                    if (!isset($createData['state'])) $createData['state'] = '';

                    $columnsCols = array_keys($createData);
                    $placeholders = array_fill(0, count($columnsCols), '?');
                    $sqlFmCreate = "INSERT INTO family_members (" . implode(',', $columnsCols) . ") VALUES (" . implode(',', $placeholders) . ")";
                    $stmtFmCreate = $this->pdo->prepare($sqlFmCreate);
                    
                    if (!$stmtFmCreate) {
                        throw new \RuntimeException('Failed to prepare create statement for family_members');
                    }
                    
                    if (!$stmtFmCreate->execute(array_values($createData))) {
                        throw new \RuntimeException('Failed to create family_members record for user ' . $userId);
                    }
                }
            }

            // Commit transaction
            $this->pdo->commit();
            
            LoggerService::info('User updated successfully - User ID: ' . $userId . ', Fields: ' . implode(', ', array_keys($updateData ?? [])));

            return true;

        } catch (\Exception $e) {
            // Rollback on any error
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            LoggerService::error('UserService::updateUser failed for user ' . $userId . ': ' . $e->getMessage());
            throw $e;
        }
    }
}
