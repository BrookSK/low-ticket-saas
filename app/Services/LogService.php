<?php

namespace App\Services;

use App\Core\Database;

/**
 * Sistema centralizado de logs. Grava em arquivo (storage/logs) e,
 * quando aplicavel, na tabela admin_logs para exibicao no painel.
 */
class LogService
{
    protected Database $db;
    protected string $logDir;

    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->logDir = storage_path('logs');
        if (!is_dir($this->logDir)) {
            @mkdir($this->logDir, 0775, true);
        }
    }

    public function log(string $level, string $channel, string $message, array $context = []): void
    {
        $line = sprintf(
            "[%s] %s.%s: %s %s\n",
            now(),
            strtoupper($level),
            $channel,
            $message,
            $context ? json_encode($context, JSON_UNESCAPED_UNICODE) : ''
        );
        @file_put_contents(
            $this->logDir . '/app-' . date('Y-m-d') . '.log',
            $line,
            FILE_APPEND | LOCK_EX
        );
    }

    public function error(string $channel, string $message, array $context = []): void
    {
        $this->log('error', $channel, $message, $context);
    }

    public function info(string $channel, string $message, array $context = []): void
    {
        $this->log('info', $channel, $message, $context);
    }

    public function warning(string $channel, string $message, array $context = []): void
    {
        $this->log('warning', $channel, $message, $context);
    }

    /**
     * Registra uma acao administrativa/auditavel na tabela admin_logs.
     */
    public function admin(string $action, ?int $userId, string $description, array $meta = [], string $severity = 'info'): void
    {
        $this->log($severity, 'admin', $action . ': ' . $description, $meta);

        try {
            $this->db->insert(
                'INSERT INTO admin_logs (user_id, action, severity, description, meta, ip_address, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?)',
                [
                    $userId,
                    $action,
                    $severity,
                    $description,
                    $meta ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null,
                    $_SERVER['REMOTE_ADDR'] ?? null,
                    now(),
                ]
            );
        } catch (\Throwable $e) {
            // Nao interrompe o fluxo se a tabela ainda nao existir.
        }
    }
}
