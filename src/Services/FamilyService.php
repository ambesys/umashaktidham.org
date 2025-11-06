<?php

namespace App\Services;

use App\Models\User;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Services\LoggerService;

class FamilyService
{
    protected $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Create a new family member with proper error handling and logging.
     *
     * @param array $data Family member data
     * @return int|false Insert ID on success, false on failure
     * @throws \Exception On database errors
     */
    public function createFamilyMember($data)
    {
        try {
            if (!isset($data['user_id']) || !is_numeric($data['user_id'])) {
                throw new \InvalidArgumentException('user_id is required and must be numeric');
            }

            $familyMember = new FamilyMember($this->pdo);
            $result = $familyMember->create($data);

            if ($result) {
                LoggerService::info('Family member created - User ID: ' . $data['user_id'] . ', Relationship: ' . ($data['relationship'] ?? 'N/A'));
            }

            return $result;
        } catch (\Exception $e) {
            LoggerService::error('FamilyService::createFamilyMember failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update a family member and, if needed, the user/family record.
     * Supports atomic transactions for multi-table updates.
     *
     * @param int $familyMemberId Family member ID
     * @param array $data Updated data
     * @return bool True on success, false on failure
     * @throws \Exception On database errors
     */
    public function updateFamilyMember($familyMemberId, $data)
    {
        try {
            $this->pdo->beginTransaction();

            $familyMember = new FamilyMember($this->pdo);
            $user = new User($this->pdo);

            // If this is the main user, also update users table
            if (!empty($data['is_main_user']) && $data['is_main_user']) {
                $userId = $data['user_id'] ?? null;
                if ($userId) {
                    $userData = $this->extractUserFields($data);
                    if (!empty($userData)) {
                        $user->update($userId, $userData);
                        LoggerService::debug('Updated users table for user ' . $userId);
                    }
                }
            }

            $result = $familyMember->update($familyMemberId, $data);

            if ($result) {
                $this->pdo->commit();
                LoggerService::info('Family member updated - ID: ' . $familyMemberId . ', Fields: ' . implode(', ', array_keys($data)));
                return true;
            }

            $this->pdo->rollBack();
            return false;

        } catch (\Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            LoggerService::error('FamilyService::updateFamilyMember failed for family member ' . $familyMemberId . ': ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete a family member with proper error handling and logging.
     *
     * @param int $familyMemberId Family member ID
     * @param array $options Additional options (reserved for future use)
     * @return bool True on success, false on failure
     * @throws \Exception On database errors
     */
    public function deleteFamilyMember($familyMemberId, $options = [])
    {
        try {
            if (!is_numeric($familyMemberId)) {
                throw new \InvalidArgumentException('familyMemberId must be numeric');
            }

            $familyMember = new FamilyMember($this->pdo);
            $result = $familyMember->delete($familyMemberId);

            if ($result) {
                LoggerService::info('Family member deleted - ID: ' . $familyMemberId);
            } else {
                LoggerService::warning('Family member delete returned false - ID: ' . $familyMemberId);
            }

            return $result;

        } catch (\Exception $e) {
            LoggerService::error('FamilyService::deleteFamilyMember failed for family member ' . $familyMemberId . ': ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Helper to extract only user fields from data.
     * Maps family_members fields to users table fields where applicable.
     *
     * @param array $data Input data
     * @return array Extracted user fields
     */
    protected function extractUserFields($data)
    {
        $userFields = ['first_name', 'last_name', 'email', 'phone_e164', 'birth_year', 'gender'];
        $result = [];
        foreach ($userFields as $field) {
            if (isset($data[$field])) {
                $result[$field] = $data[$field];
            }
        }
        return $result;
    }
}

