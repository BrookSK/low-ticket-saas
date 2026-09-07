<?php

namespace App\Services;

/**
 * Registra os listeners dos eventos comerciais no EventService.
 * Centraliza o "o que acontece quando" para facilitar manutencao e evolucao.
 *
 * Observacao: o EventService ja aciona automacoes configuradas (automation_rules)
 * para cada evento. Aqui ficam as acoes fixas do produto (e-mails transacionais).
 */
class EventListeners
{
    public static function register(EventService $events): void
    {
        // Boas-vindas apos cadastro.
        $events->listen('UserRegistered', function (array $p) {
            if (!empty($p['email'])) {
                app(MailService::class)->sendTemplate('welcome', $p['email'], [
                    'name' => $p['name'] ?? '',
                    'dashboard_url' => url('/dashboard'),
                ]);
            }
        });

        // Compra aprovada: e-mail de confirmacao + notificacao interna.
        $events->listen('PaymentApproved', function (array $p) {
            if (!empty($p['email'])) {
                app(MailService::class)->sendTemplate('purchase_approved', $p['email'], [
                    'name' => $p['name'] ?? '',
                    'dashboard_url' => url('/dashboard'),
                ]);
            }
            if (!empty($p['user_id'])) {
                app(NotificationService::class)->create(
                    (int) $p['user_id'], 'PaymentApproved', 'Pagamento aprovado',
                    'Seu acesso foi liberado. Aproveite!', url('/dashboard')
                );
            }
        });

        // Pagamento recusado: e-mail com link para tentar novamente.
        $events->listen('PaymentFailed', function (array $p) {
            if (!empty($p['email'])) {
                app(MailService::class)->sendTemplate('payment_failed', $p['email'], [
                    'name' => $p['name'] ?? '',
                    'checkout_url' => url('/dashboard'),
                ]);
            }
        });
    }
}
