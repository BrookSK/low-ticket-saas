<?php

namespace App\Services;

use App\Core\Database;
use App\Core\Encrypter;

/**
 * Gerencia as configuracoes da aplicacao armazenadas na tabela `settings`.
 *
 * Substitui completamente o uso de .env. Valores sensiveis (chaves de API,
 * tokens, senhas SMTP) sao armazenados criptografados e descriptografados
 * sob demanda. Mantem cache em memoria por requisicao.
 */
class SettingsService
{
    protected Database $db;
    protected Encrypter $encrypter;

    /** @var array<string, mixed>|null Cache carregado da tabela. */
    protected ?array $cache = null;

    public function __construct(Database $db, Encrypter $encrypter)
    {
        $this->db = $db;
        $this->encrypter = $encrypter;
    }

    /**
     * Carrega todas as settings para o cache (uma vez por requisicao).
     */
    protected function loadAll(): void
    {
        if ($this->cache !== null) {
            return;
        }
        $this->cache = [];

        try {
            $rows = $this->db->select('SELECT `key`, `value`, `type`, `encrypted` FROM settings');
        } catch (\Throwable $e) {
            // Banco ainda nao migrado; opera com defaults.
            $rows = [];
        }

        foreach ($rows as $row) {
            $this->cache[$row['key']] = $this->castValue($row);
        }
    }

    protected function castValue(array $row)
    {
        $value = $row['value'];

        if ((int) ($row['encrypted'] ?? 0) === 1 && $value !== null && $value !== '') {
            try {
                $value = $this->encrypter->decrypt($value);
            } catch (\Throwable $e) {
                $value = null;
            }
        }

        return match ($row['type'] ?? 'string') {
            'bool', 'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'int', 'integer' => $value === null ? null : (int) $value,
            'float', 'decimal' => $value === null ? null : (float) $value,
            'json', 'array' => $value ? json_decode($value, true) : [],
            default => $value,
        };
    }

    public function get(string $key, $default = null)
    {
        $this->loadAll();
        $value = $this->cache[$key] ?? null;
        if ($value === null || $value === '') {
            return $default;
        }
        return $value;
    }

    public function has(string $key): bool
    {
        $this->loadAll();
        return array_key_exists($key, $this->cache);
    }

    /**
     * Retorna todas as settings de um grupo (ex: 'mail', 'payment').
     * Chaves no formato 'grupo.chave'.
     */
    public function group(string $group): array
    {
        $this->loadAll();
        $result = [];
        $prefix = $group . '.';
        foreach ($this->cache as $key => $value) {
            if (str_starts_with($key, $prefix)) {
                $result[substr($key, strlen($prefix))] = $value;
            }
        }
        return $result;
    }

    /**
     * Grava uma configuracao. Detecta se deve criptografar conforme metadados
     * existentes ou o parametro $encrypt.
     */
    public function set(string $key, $value, string $type = 'string', ?bool $encrypt = null, ?string $group = null, bool $public = false): void
    {
        $existing = $this->db->selectOne('SELECT id, encrypted FROM settings WHERE `key` = ?', [$key]);

        $shouldEncrypt = $encrypt;
        if ($shouldEncrypt === null) {
            $shouldEncrypt = $existing ? ((int) $existing['encrypted'] === 1) : false;
        }

        $stored = $this->prepareForStorage($value, $type, $shouldEncrypt);
        $group = $group ?? explode('.', $key)[0];

        if ($existing) {
            $this->db->execute(
                'UPDATE settings SET `value` = ?, `type` = ?, `encrypted` = ?, `updated_at` = ? WHERE `key` = ?',
                [$stored, $type, $shouldEncrypt ? 1 : 0, now(), $key]
            );
        } else {
            $this->db->insert(
                'INSERT INTO settings (`group`, `key`, `value`, `type`, `encrypted`, `is_public`, `created_at`, `updated_at`)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                [$group, $key, $stored, $type, $shouldEncrypt ? 1 : 0, $public ? 1 : 0, now(), now()]
            );
        }

        // Atualiza o cache local.
        if ($this->cache !== null) {
            $this->cache[$key] = $this->castValue([
                'value' => $stored,
                'type' => $type,
                'encrypted' => $shouldEncrypt ? 1 : 0,
            ]);
        }
    }

    protected function prepareForStorage($value, string $type, bool $encrypt): ?string
    {
        if (in_array($type, ['json', 'array'], true)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
        } elseif (is_bool($value)) {
            $value = $value ? '1' : '0';
        } elseif ($value !== null) {
            $value = (string) $value;
        }

        if ($encrypt && $value !== null && $value !== '') {
            $value = $this->encrypter->encrypt($value);
        }

        return $value;
    }

    /**
     * Grava um lote de settings de uma so vez (usado pelo painel).
     * $items: [key => value] e $meta: [key => ['type'=>..,'encrypt'=>..]]
     */
    public function setMany(array $items, array $meta = []): void
    {
        foreach ($items as $key => $value) {
            $type = $meta[$key]['type'] ?? 'string';
            $encrypt = $meta[$key]['encrypt'] ?? null;
            $public = $meta[$key]['public'] ?? false;
            $this->set($key, $value, $type, $encrypt, null, $public);
        }
    }

    public function flush(): void
    {
        $this->cache = null;
    }
}
