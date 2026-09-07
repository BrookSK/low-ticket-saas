<?php

namespace App\Services;

use App\Core\Database;

/**
 * Dispatcher central de eventos comerciais.
 *
 * Eventos (UserRegistered, CheckoutStarted, PaymentApproved, QuoteCreated...)
 * sao registrados como analytics_events e podem acionar automacoes
 * (e-mail, WhatsApp, notificacoes) via AutomationService.
 *
 * Este desenho permite adicionar novos listeners sem alterar quem dispara.
 */
class EventService
{
    protected Database $db;
    protected AttributionService $attribution;

    /** @var array<string, callable[]> */
    protected array $listeners = [];

    public function __construct(Database $db, AttributionService $attribution)
    {
        $this->db = $db;
        $this->attribution = $attribution;
    }

    public function listen(string $event, callable $listener): void
    {
        $this->listeners[$event][] = $listener;
    }

    /**
     * Dispara um evento: registra em analytics_events e chama listeners.
     */
    public function dispatch(string $event, array $payload = []): void
    {
        $this->record($event, $payload);

        foreach ($this->listeners[$event] ?? [] as $listener) {
            try {
                $listener($payload);
            } catch (\Throwable $e) {
                logger()->error('event', "Listener falhou para {$event}: " . $e->getMessage());
            }
        }

        // Aciona automacoes configuradas para este evento.
        try {
            app(AutomationService::class)->trigger($event, $payload);
        } catch (\Throwable $e) {
            // Automacoes nao devem interromper o fluxo.
        }
    }

    protected function record(string $event, array $payload): void
    {
        try {
            $this->db->insert(
                'INSERT INTO analytics_events
                 (event, user_id, visitor_id, attribution_id, value, currency, transaction_id, properties, url, created_at)
                 VALUES (?,?,?,?,?,?,?,?,?,?)',
                [
                    $event,
                    $payload['user_id'] ?? null,
                    $_COOKIE['lt_vid'] ?? null,
                    $this->attribution->currentAttributionId(),
                    $payload['value'] ?? null,
                    $payload['currency'] ?? null,
                    $payload['transaction_id'] ?? null,
                    !empty($payload) ? json_encode($payload, JSON_UNESCAPED_UNICODE) : null,
                    $_SERVER['REQUEST_URI'] ?? null,
                    now(),
                ]
            );
        } catch (\Throwable $e) {
            // Silencioso: analytics nao deve quebrar o fluxo.
        }
    }
}
