<?php

namespace App\Services;

use App\Core\Database;

/**
 * Sistema centralizado de notificacoes internas.
 */
class NotificationService
{
    protected Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function create(int $userId, string $type, string $title, string $body = '', ?string $link = null): int
    {
        return $this->db->insert(
            'INSERT INTO notifications (user_id, type, title, body, link, created_at) VALUES (?,?,?,?,?,?)',
            [$userId, $type, $title, $body, $link, now()]
        );
    }

    public function forUser(int $userId, bool $unreadOnly = false, int $limit = 20): array
    {
        $sql = 'SELECT * FROM notifications WHERE user_id = ?';
        if ($unreadOnly) {
            $sql .= ' AND read_at IS NULL';
        }
        $sql .= ' ORDER BY created_at DESC LIMIT ' . (int) $limit;
        return $this->db->select($sql, [$userId]);
    }

    public function unreadCount(int $userId): int
    {
        $row = $this->db->selectOne('SELECT COUNT(*) AS c FROM notifications WHERE user_id = ? AND read_at IS NULL', [$userId]);
        return (int) ($row['c'] ?? 0);
    }

    public function markRead(int $id, int $userId): void
    {
        $this->db->execute('UPDATE notifications SET read_at = ? WHERE id = ? AND user_id = ?', [now(), $id, $userId]);
    }

    public function markAllRead(int $userId): void
    {
        $this->db->execute('UPDATE notifications SET read_at = ? WHERE user_id = ? AND read_at IS NULL', [now(), $userId]);
    }
}
