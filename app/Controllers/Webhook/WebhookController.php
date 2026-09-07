<?php

namespace App\Controllers\Webhook;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Services\LogService;
use App\Services\OrderService;
use App\Services\PaymentService;

/**
 * Recebe webhooks dos gateways de pagamento.
 * - Valida a assinatura/autenticidade
 * - Evita processamento duplicado (idempotencia via payment_webhooks)
 * - Registra logs
 * - Atualiza o pedido e libera o produto
 */
class WebhookController extends Controller
{
    protected Database $db;
    protected PaymentService $payments;
    protected OrderService $orders;
    protected LogService $log;

    public function __construct(Database $db, PaymentService $payments, OrderService $orders, LogService $log)
    {
        $this->db = $db;
        $this->payments = $payments;
        $this->orders = $orders;
        $this->log = $log;
    }

    public function handle(Request $request): Response
    {
        $gatewayName = (string) $request->param('gateway');
        $rawBody = file_get_contents('php://input') ?: '';
        $headers = $this->headers();

        $gateway = $this->payments->gateway($gatewayName);
        if ($gateway->name() === 'null') {
            $this->log->warning('webhook', "Webhook recebido para gateway nao configurado: {$gatewayName}");
            return Response::json(['received' => true], 200);
        }

        $valid = $gateway->verifyWebhook($headers, $rawBody);
        $parsed = $gateway->parseWebhook($headers, $rawBody);

        // Idempotencia: se ja processamos este event_id, ignora.
        $eventId = $parsed['event_id'] ?: sha1($rawBody);
        $existing = $this->db->selectOne(
            'SELECT id, processed FROM payment_webhooks WHERE gateway = ? AND event_id = ?',
            [$gatewayName, $eventId]
        );

        // Localiza o pedido pela referencia.
        $order = $parsed['reference'] ? $this->orders->findByReference($parsed['reference']) : null;

        // Registra o webhook (ou reaproveita).
        if ($existing) {
            if ((int) $existing['processed'] === 1) {
                return Response::json(['received' => true, 'duplicated' => true], 200);
            }
            $webhookId = (int) $existing['id'];
        } else {
            $webhookId = $this->db->insert(
                'INSERT INTO payment_webhooks (gateway, event_id, event_type, order_id, payload, headers, signature_valid, processed, created_at)
                 VALUES (?,?,?,?,?,?,?,0,?)',
                [
                    $gatewayName, $eventId, $parsed['event_type'], $order['id'] ?? null,
                    $rawBody, json_encode($headers, JSON_UNESCAPED_UNICODE), $valid ? 1 : 0, now(),
                ]
            );
        }

        if (!$valid) {
            $this->log->warning('webhook', "Assinatura invalida ({$gatewayName})", ['event' => $parsed['event_type']]);
            // Responde 200 para evitar retries infinitos, mas nao processa.
            return Response::json(['received' => true, 'valid' => false], 200);
        }

        if ($order) {
            $this->orders->updateStatus(
                (int) $order['id'], $parsed['status'], $parsed['transaction_id'], $gatewayName, ['webhook' => $parsed]
            );
        }

        $this->db->execute('UPDATE payment_webhooks SET processed = 1, processed_at = ?, order_id = COALESCE(order_id, ?) WHERE id = ?', [
            now(), $order['id'] ?? null, $webhookId,
        ]);

        $this->log->info('webhook', "Webhook processado ({$gatewayName})", [
            'event' => $parsed['event_type'], 'status' => $parsed['status'], 'reference' => $parsed['reference'],
        ]);

        return Response::json(['received' => true], 200);
    }

    protected function headers(): array
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = strtolower(str_replace('_', '-', substr($key, 5)));
                $headers[$name] = $value;
            }
        }
        return $headers;
    }
}
