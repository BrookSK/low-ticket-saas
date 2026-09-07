<?php

namespace App\Payments;

/**
 * Integracao com Asaas (checkout/cobranca). Credenciais vem das settings.
 * Usa a API de cobrancas com link de pagamento.
 */
class AsaasGateway implements PaymentGateway
{
    protected string $apiKey;
    protected string $webhookToken;
    protected bool $sandbox;

    public function __construct(string $apiKey, string $webhookToken = '', bool $sandbox = true)
    {
        $this->apiKey = $apiKey;
        $this->webhookToken = $webhookToken;
        $this->sandbox = $sandbox;
    }

    protected function baseUrl(): string
    {
        return $this->sandbox ? 'https://sandbox.asaas.com/api/v3' : 'https://api.asaas.com/v3';
    }

    public function createCharge(array $order): array
    {
        // Cria um link de pagamento (checkout simples).
        $payload = [
            'name' => $order['description'] ?? ('Pedido ' . $order['reference']),
            'billingType' => 'UNDEFINED', // deixa o cliente escolher (pix/cartao/boleto)
            'chargeType' => 'DETACHED',
            'value' => (float) $order['total'],
            'externalReference' => $order['reference'],
            'callback' => [
                'successUrl' => $order['success_url'] ?? '',
                'autoRedirect' => true,
            ],
        ];

        $res = $this->request('POST', '/paymentLinks', $payload);
        if (!$res['ok']) {
            return ['success' => false, 'error' => $res['error'] ?? 'Falha ao criar cobranca Asaas.'];
        }
        return [
            'success' => true,
            'redirect_url' => $res['data']['url'] ?? '',
            'transaction_id' => $res['data']['id'] ?? null,
            'raw' => $res['data'],
        ];
    }

    public function verifyWebhook(array $headers, string $rawBody): bool
    {
        if ($this->webhookToken === '') {
            return true;
        }
        $token = $headers['asaas-access-token'] ?? $headers['Asaas-Access-Token'] ?? '';
        return hash_equals($this->webhookToken, (string) $token);
    }

    public function parseWebhook(array $headers, string $rawBody): array
    {
        $data = json_decode($rawBody, true) ?: [];
        $event = $data['event'] ?? null;
        $payment = $data['payment'] ?? [];

        $status = match ($event) {
            'PAYMENT_CONFIRMED', 'PAYMENT_RECEIVED' => 'approved',
            'PAYMENT_OVERDUE' => 'expired',
            'PAYMENT_REFUNDED' => 'refunded',
            'PAYMENT_DELETED' => 'canceled',
            default => 'pending',
        };

        return [
            'event_id' => $payment['id'] ?? ($data['id'] ?? null),
            'event_type' => $event,
            'transaction_id' => $payment['id'] ?? null,
            'reference' => $payment['externalReference'] ?? null,
            'status' => $status,
        ];
    }

    protected function request(string $method, string $path, ?array $body = null): array
    {
        $ch = curl_init($this->baseUrl() . $path);
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'access_token: ' . $this->apiKey],
            CURLOPT_TIMEOUT => 25,
        ];
        if ($body !== null) {
            $opts[CURLOPT_POSTFIELDS] = json_encode($body, JSON_UNESCAPED_UNICODE);
        }
        curl_setopt_array($ch, $opts);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($resp === false) {
            return ['ok' => false, 'error' => $err ?: 'Falha de conexao.'];
        }
        $data = json_decode((string) $resp, true);
        if ($code >= 200 && $code < 300) {
            return ['ok' => true, 'data' => $data];
        }
        $msg = $data['errors'][0]['description'] ?? ('HTTP ' . $code);
        return ['ok' => false, 'error' => $msg];
    }

    public function name(): string
    {
        return 'asaas';
    }
}
