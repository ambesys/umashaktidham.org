<?php

namespace App\Services;

use PDO;

class RoleService
{
    private $db;
    private $table = 'roles';

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getRoleById(int $id)
    {
        $stmt = $this->db->prepare("SELECT * FROM $this->table WHERE id = :id LIMIT 1");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getRoleByName(string $name)
    {
        $stmt = $this->db->prepare("SELECT * FROM $this->table WHERE name = :name LIMIT 1");
        $stmt->bindParam(':name', $name);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Compare actor (name or id) against required (name or id).
     * Accepts role name or id for both params.
     * Returns true if actor_level >= required_level.
     */
    public function canAccess($actor, $required): bool
    {
        $actorRole = is_int($actor) ? $this->getRoleById($actor) : $this->getRoleByName((string)$actor);
        $requiredRole = is_int($required) ? $this->getRoleById($required) : $this->getRoleByName((string)$required);

        if (!$actorRole || !$requiredRole) {
            return false;
        }

        $actorLevel = (int)($actorRole['level'] ?? 0);
        $requiredLevel = (int)($requiredRole['level'] ?? 0);

        return $actorLevel >= $requiredLevel;
    }

    /**
     * Ensure roles exist with optional descriptions. Useful for seeding.
     * $roles is array of ['name' => 'admin', 'description' => '...']
     */
    public function ensureRoles(array $roles)
    {
        $insertStmt = $this->db->prepare("INSERT IGNORE INTO $this->table (name, description) VALUES (:name, :description)");
        foreach ($roles as $r) {
            $name = $r['name'];
            $description = $r['description'] ?? null;
            $insertStmt->bindParam(':name', $name);
            $insertStmt->bindParam(':description', $description);
            $insertStmt->execute();
        }
    }

    /**
     * Set role levels by name => level mapping.
     */
    public function setRoleLevels(array $map)
    {
        $stmt = $this->db->prepare("UPDATE $this->table SET level = :level WHERE name = :name");
        foreach ($map as $name => $level) {
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':level', $level, PDO::PARAM_INT);
            $stmt->execute();
        }
    }
}
