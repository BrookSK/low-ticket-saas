<?php

namespace App\Core;

/**
 * Configuracoes de bootstrap (arquivos em /config).
 * ATENCAO: aqui NAO ficam credenciais. Somente o minimo necessario para
 * conectar ao banco e subir a aplicacao. Todo o resto vem da tabela settings.
 */
class Config
{
    /** @var array<string, mixed> */
    protected static array $items = [];

    public static function load(string $configPath): void
    {
        foreach (glob($configPath . '/*.php') as $file) {
            $key = basename($file, '.php');
            static::$items[$key] = require $file;
        }
    }

    /**
     * Acesso por notacao de ponto: config('app.url').
     */
    public static function get(string $key, $default = null)
    {
        $segments = explode('.', $key);
        $value = static::$items;

        foreach ($segments as $segment) {
            if (is_array($value) && array_key_exists($segment, $value)) {
                $value = $value[$segment];
            } else {
                return $default;
            }
        }

        return $value;
    }

    public static function set(string $key, $value): void
    {
        $segments = explode('.', $key);
        $ref = &static::$items;
        foreach ($segments as $i => $segment) {
            if ($i === count($segments) - 1) {
                $ref[$segment] = $value;
            } else {
                if (!isset($ref[$segment]) || !is_array($ref[$segment])) {
                    $ref[$segment] = [];
                }
                $ref = &$ref[$segment];
            }
        }
    }

    public static function all(): array
    {
        return static::$items;
    }
}
