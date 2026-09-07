<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class User extends Model
{
    protected static string $table = 'users';

    /**
     * Retorna os slugs de roles do usuario.
     */
    public static function roles(int $userId): array
    {
        $rows = app(Database::class)->select(
            'SELECT r.slug FROM roles r
             INNER JOIN user_roles ur ON ur.role_id = r.id
             WHERE ur.user_id = ?',
            [$userId]
        );
        return array_column($rows, 'slug');
    }

    public static function hasRole(int $userId, string $slug): bool
    {
        return in_array($slug, self::roles($userId), true);
    }

    public static function isAdmin(int $userId): bool
    {
        $roles = self::roles($userId);
        return in_array('admin', $roles, true) || in_array('super_admin', $roles, true);
    }

    public static function assignRole(int $userId, string $slug): void
    {
        $role = app(Database::class)->selectOne('SELECT id FROM roles WHERE slug = ?', [$slug]);
        if ($role) {
            app(Database::class)->execute(
                'INSERT IGNORE INTO user_roles (user_id, role_id) VALUES (?, ?)',
                [$userId, $role['id']]
            );
        }
    }

    /**
     * Modulos que o usuario tem acesso (a partir de user_product_access).
     */
    public static function modules(int $userId): array
    {
        $rows = app(Database::class)->select(
            'SELECT module FROM user_product_access
             WHERE user_id = ? AND (expires_at IS NULL OR expires_at > NOW())',
            [$userId]
        );
        return array_column($rows, 'module');
    }

    public static function hasModule(int $userId, string $module): bool
    {
        // Admins tem acesso a tudo.
        if (self::isAdmin($userId)) {
            return true;
        }
        return in_array($module, self::modules($userId), true);
    }

    public static function grantModule(int $userId, string $module, ?int $productId = null, string $source = 'purchase'): void
    {
        app(Database::class)->execute(
            'INSERT INTO user_product_access (user_id, product_id, module, source, granted_at)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE product_id = VALUES(product_id), source = VALUES(source), granted_at = VALUES(granted_at)',
            [$userId, $productId, $module, $source, now()]
        );
    }
}
