<?php

namespace App\Models;

use PDO;

class Event
{
    private $db;
    private $table = 'events';

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
            throw new \RuntimeException('Event model requires a PDO instance (provide via constructor)');
        }
    }

    /**
     * Create an event and set up an uploads folder for it.
     * $data: title, slug (optional), description, start_at, end_at, location, capacity, price, sponsorable, created_by_user_id
     */
    public function create(array $data)
    {
        // generate slug if missing
        $slug = $data['slug'] ?? $this->slugify($data['title'] ?? 'event');

        // ensure slug uniqueness: if conflict append suffix
        $base = $slug;
        $i = 1;
        while ($this->slugExists($slug)) {
            $slug = $base . '-' . $i++;
        }

        $sql = "INSERT INTO $this->table (title, slug, description, start_at, end_at, location, capacity, price, sponsorable, created_by_user_id) VALUES (:title, :slug, :description, :start_at, :end_at, :location, :capacity, :price, :sponsorable, :created_by_user_id)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':title', $data['title']);
        $stmt->bindParam(':slug', $slug);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':start_at', $data['start_at']);
        $stmt->bindParam(':end_at', $data['end_at']);
        $stmt->bindParam(':location', $data['location']);
        $stmt->bindParam(':capacity', $data['capacity']);
        $stmt->bindParam(':price', $data['price']);
        $spons = !empty($data['sponsorable']) ? 1 : 0;
        $stmt->bindParam(':sponsorable', $spons, PDO::PARAM_INT);
        $stmt->bindParam(':created_by_user_id', $data['created_by_user_id']);

        if (!$stmt->execute()) {
            return false;
        }

        $eventId = (int)$this->db->lastInsertId();

        // create uploads folder for event
        $uploadsRoot = __DIR__ . '/../../public/assets/uploads/events';
        $eventFolder = $uploadsRoot . '/' . $slug;
        $this->ensureDirectory($eventFolder);
        // create common subfolders
        $this->ensureDirectory($eventFolder . '/photos');
        $this->ensureDirectory($eventFolder . '/videos');
        $this->ensureDirectory($eventFolder . '/docs');

        return $eventId;
    }

    private function slugExists(string $slug): bool
    {
        $stmt = $this->db->prepare("SELECT id FROM $this->table WHERE slug = :slug LIMIT 1");
        $stmt->bindParam(':slug', $slug);
        $stmt->execute();
        return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function slugify(string $text): string
    {
        $text = preg_replace('~[^\pL0-9]+~u', '-', $text);
        $text = trim($text, '-');
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = strtolower($text);
        $text = preg_replace('~[^-a-z0-9]+~', '', $text);
        if (empty($text)) return 'event-' . time();
        return $text;
    }

    private function ensureDirectory(string $path)
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }
}
