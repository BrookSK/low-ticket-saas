<?php

/**
 * Funcoes auxiliares globais.
 * Carregadas pelo autoloader (Composer files ou fallback interno).
 */

use App\Core\Application;
use App\Core\Config;
use App\Core\Session;
use App\Services\SettingsService;

if (!function_exists('app')) {
    /**
     * Recupera a instancia da aplicacao ou resolve um servico do container.
     */
    function app(?string $abstract = null)
    {
        $app = Application::getInstance();
        if ($abstract === null) {
            return $app;
        }
        return $app->make($abstract);
    }
}

if (!function_exists('base_path')) {
    function base_path(string $path = ''): string
    {
        $root = dirname(__DIR__, 2);
        return $path === '' ? $root : $root . DIRECTORY_SEPARATOR . ltrim($path, '/\\');
    }
}

if (!function_exists('storage_path')) {
    function storage_path(string $path = ''): string
    {
        return base_path('storage' . ($path ? '/' . ltrim($path, '/\\') : ''));
    }
}

if (!function_exists('config')) {
    /**
     * Acesso as configuracoes de bootstrap (arquivo config/app.php).
     * Nao confundir com settings (tabela do banco, gerenciada pelo admin).
     */
    function config(string $key, $default = null)
    {
        return Config::get($key, $default);
    }
}

if (!function_exists('setting')) {
    /**
     * Le uma configuracao gerenciada pelo painel admin (tabela settings).
     */
    function setting(string $key, $default = null)
    {
        return app(SettingsService::class)->get($key, $default);
    }
}

if (!function_exists('e')) {
    /**
     * Escapa saida para prevenir XSS.
     */
    function e($value): string
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('old')) {
    function old(string $key, $default = '')
    {
        $old = Session::getFlash('_old_input', []);
        return $old[$key] ?? $default;
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return Session::csrfToken();
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        $base = rtrim((string) setting('app.url', config('app.url', '')), '/');
        if ($base === '') {
            // Deriva do ambiente quando ainda nao configurado.
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $base = $scheme . '://' . $host;
        }
        return $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('redirect')) {
    function redirect(string $path): void
    {
        $location = preg_match('#^https?://#', $path) ? $path : url($path);
        header('Location: ' . $location, true, 302);
        exit;
    }
}

if (!function_exists('money')) {
    /**
     * Formata valor monetario conforme moeda configurada (padrao BRL).
     */
    function money($value, ?string $currency = null): string
    {
        $currency = $currency ?: (string) setting('app.currency', 'BRL');
        $symbols = ['BRL' => 'R$', 'USD' => '$', 'EUR' => '€'];
        $symbol = $symbols[$currency] ?? '';
        return trim($symbol . ' ' . number_format((float) $value, 2, ',', '.'));
    }
}

if (!function_exists('now')) {
    function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}

if (!function_exists('str_random')) {
    function str_random(int $length = 32): string
    {
        return substr(bin2hex(random_bytes((int) ceil($length / 2))), 0, $length);
    }
}

if (!function_exists('view')) {
    function view(string $template, array $data = []): \App\Core\Response
    {
        return app(\App\Core\View::class)->render($template, $data);
    }
}

if (!function_exists('logger')) {
    function logger(): \App\Services\LogService
    {
        return app(\App\Services\LogService::class);
    }
}
