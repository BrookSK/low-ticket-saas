<?php

namespace App\Core;

/**
 * Model base minimalista (Active Record leve) sobre PDO.
 * Fornece operacoes CRUD comuns com prepared statements.
 */
abstract class Model
{
    protected static string $table = '';
    protected static string $primaryKey = 'id';

    protected static function db(): Database
    {
        return app(Database::class);
    }

    public static function table(): string
    {
        return static::$table;
    }

    public static function find($id): ?array
    {
        return static::db()->selectOne(
            'SELECT * FROM ' . static::$table . ' WHERE ' . static::$primaryKey . ' = ? LIMIT 1',
            [$id]
        );
    }

    public static function findBy(string $column, $value): ?array
    {
        return static::db()->selectOne(
            'SELECT * FROM ' . static::$table . ' WHERE `' . $column . '` = ? LIMIT 1',
            [$value]
        );
    }

    public static function where(string $column, $value): array
    {
        return static::db()->select(
            'SELECT * FROM ' . static::$table . ' WHERE `' . $column . '` = ?',
            [$value]
        );
    }

    public static function all(?string $orderBy = null): array
    {
        $sql = 'SELECT * FROM ' . static::$table;
        if ($orderBy) {
            $sql .= ' ORDER BY ' . $orderBy;
        }
        return static::db()->select($sql);
    }

    public static function create(array $data): int
    {
        $data['created_at'] = $data['created_at'] ?? now();
        $data['updated_at'] = $data['updated_at'] ?? now();

        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');
        $sql = 'INSERT INTO ' . static::$table
            . ' (`' . implode('`,`', $columns) . '`) VALUES (' . implode(',', $placeholders) . ')';

        return static::db()->insert($sql, array_values($data));
    }

    public static function update($id, array $data): int
    {
        $data['updated_at'] = now();
        $sets = [];
        foreach (array_keys($data) as $column) {
            $sets[] = '`' . $column . '` = ?';
        }
        $sql = 'UPDATE ' . static::$table . ' SET ' . implode(',', $sets)
            . ' WHERE ' . static::$primaryKey . ' = ?';

        $bindings = array_values($data);
        $bindings[] = $id;

        return static::db()->execute($sql, $bindings);
    }

    public static function delete($id): int
    {
        return static::db()->execute(
            'DELETE FROM ' . static::$table . ' WHERE ' . static::$primaryKey . ' = ?',
            [$id]
        );
    }

    public static function count(string $where = '', array $bindings = []): int
    {
        $sql = 'SELECT COUNT(*) AS c FROM ' . static::$table;
        if ($where !== '') {
            $sql .= ' WHERE ' . $where;
        }
        $row = static::db()->selectOne($sql, $bindings);
        return (int) ($row['c'] ?? 0);
    }
}
