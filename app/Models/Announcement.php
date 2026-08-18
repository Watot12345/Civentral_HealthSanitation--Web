<?php
// app/Models/Announcement.php

require_once __DIR__ . '/../../config/database.php';

class Announcement
{
    private Database $db;
    private string $table = 'announcements';
    private string $fallbackFile;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::getInstance();
        $this->fallbackFile = __DIR__ . '/../../storage/announcements.json';
        $storageDir = dirname($this->fallbackFile);
        if (!is_dir($storageDir)) {
            @mkdir($storageDir, 0755, true);
        }
        $this->ensureFallbackSeed();
    }

    private function ensureFallbackSeed(): void
    {
        if (!file_exists($this->fallbackFile)) {
            @file_put_contents($this->fallbackFile, json_encode([], JSON_PRETTY_PRINT));
        }
    }

    private function getFallbackData(): array
    {
        $this->ensureFallbackSeed();
        $raw = @file_get_contents($this->fallbackFile);
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    private function saveFallbackData(array $data): void
    {
        @file_put_contents($this->fallbackFile, json_encode(array_values($data), JSON_PRETTY_PRINT));
    }

    public function all(array $options = []): array
    {
        try {
            $opts = array_merge(['order' => 'id.desc'], $options);
            $results = $this->db->select($this->table, ['is_active' => 'true'], $opts);
            if (!empty($results)) {
                return $results;
            }
        } catch (Throwable $e) {
            error_log("Announcement Supabase fallback to file: " . $e->getMessage());
        }

        $all = $this->getFallbackData();
        $active = array_filter($all, fn($a) => !isset($a['is_active']) || $a['is_active']);
        usort($active, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));
        return array_values($active);
    }

    public function create(array $data): array
    {
        $payload = [
            'title'      => trim($data['title'] ?? 'Untitled Announcement'),
            'category'   => trim($data['category'] ?? 'General Announcement'),
            'audience'   => trim($data['audience'] ?? 'All Staff'),
            'body'       => trim($data['body'] ?? ''),
            'author'     => trim($data['author'] ?? 'System Admin'),
            'file_url'   => $data['file_url'] ?? null,
            'is_active'  => true,
            'created_at' => date('Y-m-d H:i:s')
        ];

        try {
            $res = $this->db->insert($this->table, $payload, true);
            if (!empty($res[0])) {
                return $res[0];
            }
        } catch (Throwable $e) {
            error_log("Announcement DB Insert Exception: " . $e->getMessage());
        }

        // Persistent file fallback
        $existing = $this->getFallbackData();
        $maxId = 0;
        foreach ($existing as $item) {
            if (isset($item['id']) && $item['id'] > $maxId) {
                $maxId = (int)$item['id'];
            }
        }
        $payload['id'] = $maxId + 1;
        array_unshift($existing, $payload);
        $this->saveFallbackData($existing);

        return $payload;
    }

    public function delete($id): bool
    {
        try {
            $this->db->delete($this->table, ['id' => $id], true);
        } catch (Throwable $e) {
            error_log("Announcement DB Delete Exception: " . $e->getMessage());
        }

        $existing = $this->getFallbackData();
        $filtered = array_filter($existing, fn($a) => (int)($a['id'] ?? 0) !== (int)$id);
        $this->saveFallbackData($filtered);
        return true;
    }
}
