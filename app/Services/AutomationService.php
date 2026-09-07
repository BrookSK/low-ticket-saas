<?php

namespace App\Services;

use App\Core\Database;

/**
 * Executa automacoes configuradas (automation_rules) para um evento.
 *
 * Regras com delay_minutes = 0 sao executadas imediatamente; com delay > 0
 * sao agendadas (automation_logs.scheduled_at) para processamento via cron.
 *
 * A implementacao de envio efetivo (e-mail/whatsapp) e concluida na FASE 5/6.
 */
class AutomationService
{
    protected Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function trigger(string $event, array $payload = []): void
    {
        $rules = [];
        try {
            $rules = $this->db->select(
                'SELECT * FROM automation_rules WHERE event = ? AND is_active = 1',
                [$event]
            );
        } catch (\Throwable $e) {
            return;
        }

        foreach ($rules as $rule) {
            $scheduledAt = (int) $rule['delay_minutes'] > 0
                ? date('Y-m-d H:i:s', time() + (int) $rule['delay_minutes'] * 60)
                : now();

            $recipient = $payload['email'] ?? $payload['recipient'] ?? null;

            $this->db->insert(
                'INSERT INTO automation_logs (rule_id, event, channel, recipient, status, scheduled_at, created_at)
                 VALUES (?,?,?,?,?,?,?)',
                [$rule['id'], $event, $rule['channel'], $recipient, 'queued', $scheduledAt, now()]
            );

            // Execucao imediata quando nao ha atraso.
            if ((int) $rule['delay_minutes'] === 0) {
                $this->execute($rule, $payload);
            }
        }
    }

    /**
     * Executa uma regra de automacao. Retorna true em sucesso.
     */
    public function execute(array $rule, array $payload = []): bool
    {
        try {
            switch ($rule['channel']) {
                case 'email':
                    if (!empty($payload['email']) && !empty($rule['template_slug'])) {
                        app(MailService::class)->sendTemplate($rule['template_slug'], $payload['email'], $payload);
                    }
                    break;
                case 'whatsapp':
                    if (!empty($payload['whatsapp']) && !empty($rule['template_slug'])) {
                        app(WhatsAppService::class)->sendTemplate($rule['template_slug'], $payload['whatsapp'], $payload);
                    }
                    break;
                case 'internal':
                    if (!empty($payload['user_id'])) {
                        app(NotificationService::class)->create(
                            (int) $payload['user_id'],
                            $rule['event'],
                            $rule['name'],
                            $payload['message'] ?? ''
                        );
                    }
                    break;
            }
            return true;
        } catch (\Throwable $e) {
            logger()->error('automation', 'Falha ao executar regra: ' . $e->getMessage(), ['rule' => $rule['id'] ?? null]);
            return false;
        }
    }
}
