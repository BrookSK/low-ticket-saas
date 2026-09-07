<?php

namespace App\Services;

use App\Core\Database;

/**
 * Rotinas agendadas executadas via cron (bin/cron.php).
 * Cada metodo e idempotente e seguro para rodar a cada minuto.
 */
class CronService
{
    protected Database $db;
    protected AutomationService $automations;
    protected SettingsService $settings;

    public function __construct(Database $db, AutomationService $automations, SettingsService $settings)
    {
        $this->db = $db;
        $this->automations = $automations;
        $this->settings = $settings;
    }

    public function runAll(): array
    {
        return [
            'automations' => $this->processScheduledAutomations(),
            'abandonment' => $this->processCheckoutRecovery(),
            'overdue' => $this->markOverdue(),
            'expired_quotes' => $this->expireQuotes(),
        ];
    }

    /**
     * Executa automacoes agendadas cujo horario chegou.
     */
    public function processScheduledAutomations(): int
    {
        $due = $this->db->select(
            "SELECT * FROM automation_logs WHERE status = 'queued' AND scheduled_at <= NOW() LIMIT 200"
        );
        $count = 0;
        foreach ($due as $log) {
            $rule = $this->db->selectOne('SELECT * FROM automation_rules WHERE id = ?', [$log['rule_id']]);
            if (!$rule || !$rule['is_active']) {
                $this->db->execute("UPDATE automation_logs SET status='failed', error='regra inativa' WHERE id=?", [$log['id']]);
                continue;
            }
            // Reconstroi payload minimo a partir do recipient.
            $payload = ['email' => $log['recipient'], 'recipient' => $log['recipient']];
            $ok = $this->automations->execute($rule, $payload);
            $this->db->execute(
                "UPDATE automation_logs SET status = ?, sent_at = ? WHERE id = ?",
                [$ok ? 'sent' : 'failed', now(), $log['id']]
            );
            $count++;
        }
        return $count;
    }

    /**
     * Recuperacao de checkout abandonado conforme janelas configuradas.
     * Envia e-mail/WhatsApp para checkouts iniciados e nao concluidos.
     */
    public function processCheckoutRecovery(): int
    {
        $email1Min = (int) $this->settings->get('automation.recovery_email_1_minutes', 30);
        $count = 0;

        // Considera abandonado quando 'started' ha mais de X minutos e sem pedido aprovado.
        $rows = $this->db->select(
            "SELECT ca.* FROM checkout_abandonment ca
             WHERE ca.status = 'started'
             AND ca.created_at <= (NOW() - INTERVAL ? MINUTE)
             AND ca.email IS NOT NULL
             LIMIT 100",
            [$email1Min]
        );

        foreach ($rows as $ca) {
            // Ja comprou depois? Marca recuperado.
            $order = $this->db->selectOne(
                "SELECT id FROM orders WHERE email = ? AND status = 'approved' AND created_at >= ? LIMIT 1",
                [$ca['email'], $ca['created_at']]
            );
            if ($order) {
                $this->db->execute("UPDATE checkout_abandonment SET status='recovered', recovered_order_id=?, updated_at=? WHERE id=?", [$order['id'], now(), $ca['id']]);
                continue;
            }

            // Dispara o evento de abandono (aciona automation_rules configuradas).
            app(EventService::class)->dispatch('checkout_abandoned', [
                'email' => $ca['email'],
                'user_id' => $ca['user_id'],
                'checkout_url' => url('/'),
            ]);
            // Marca como 'lost' para nao reenviar (fluxo simples; janelas adicionais
            // podem ser modeladas com mais estados/automation_rules).
            $this->db->execute("UPDATE checkout_abandonment SET status='lost', updated_at=? WHERE id=?", [now(), $ca['id']]);
            $count++;
        }
        return $count;
    }

    /**
     * Marca receitas/despesas pendentes vencidas como 'overdue'.
     */
    public function markOverdue(): int
    {
        $a = $this->db->execute("UPDATE revenues SET status='overdue' WHERE status='pending' AND due_date IS NOT NULL AND due_date < CURDATE()");
        $b = $this->db->execute("UPDATE expenses SET status='overdue' WHERE status='pending' AND due_date IS NOT NULL AND due_date < CURDATE()");
        return $a + $b;
    }

    /**
     * Expira orcamentos enviados/visualizados cuja validade passou.
     */
    public function expireQuotes(): int
    {
        return $this->db->execute(
            "UPDATE quotes SET status='expired', updated_at=NOW()
             WHERE status IN ('sent','viewed') AND valid_until IS NOT NULL AND valid_until < CURDATE()"
        );
    }
}
