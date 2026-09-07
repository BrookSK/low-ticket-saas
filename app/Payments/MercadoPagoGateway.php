<?php

namespace App\Payments;

/**
 * Integracao com Mercado Pago via Checkout Pro (Preferences API).
 * Credenciais vem das settings (nao hardcoded).
 */
class MercadoPagoGateway implements PaymentGateway
{
    protected string $accessToken;
    protected string $webhookSecret;
    protected bool $sandbox;

    public function __construct(string $accessToken, string $webhookSecret = '', bool $sandbox = true)
    {
        $this->accessToken = $accessToken;
        $this->webhookSecret = $webhookSecret;
        $this->sandbox = $sandbox;
    }

    public function createCharge(array $order): array
    {
        $payload = [
            'items' => [[
                'title' => $order['description'] ?? ('Pedido ' . $order['reference']),
                'quantity' => 1,
                'currency_id' => 'BRL',
                'unit_price' => (float) $order['total'],
            ]],
            'external_reference' => $order['reference'],
            'payer' => ['email' => $order['email'] ?? null],
            'back_urls' => [
                'success' => $order['success_url'] ?? '',
                'failure' => $order['error_url'] ?? '',
                'pending' => $order['success_url'] ?? '',
            ],
            'auto_return' => 'approved',
            'notification_url' => $order['webhook_url'] ?? '',
        ];

        $res = $this->request('POST', 'https://api.mercadopago.com/checkout/preferences', $payload);
        if (!$res['ok']) {
            return ['success' => false, 'error' => $res['error'] ?? 'Falha ao criar preferencia.'];
        }

        $data = $res['data'];
        $redirect = $this->sandbox ? ($data['sandbox_init_point'] ?? $data['init_point'] ?? '') : ($data['init_point'] ?? '');

        return [
            'success' => true,
            'redirect_url' => $redirect,
            'transaction_id' => $data['id'] ?? null,
            'raw' => $data,
        ];
    }

    public function verifyWebhook(array $headers, string $rawBody): bool
    {
        // Mercado Pago envia x-signature (v1) e x-request-id. Se houver secret,
        // valida o HMAC; caso contrario, aceita e depende da consulta a API.
        if ($this->webhookSecret === '') {
            return true;
        }
        $signature = $headers['x-signature'] ?? $headers['X-Signature'] ?? '';
        if ($signature === '') {
            return false;
        }
        // Formato: "ts=...,v1=..." — validacao simplificada do v1.
        $parts = [];
        foreach (explode(',', $signature) as $seg) {
            $kv = explode('=', trim($seg), 2);
            if (count($kv) === 2) {
                $parts[$kv[0]] = $kv[1];
            }
        }
        $ts = $parts['ts'] ?? '';
        $v1 = $parts['v1'] ?? '';
        $requestId = $headers['x-request-id'] ?? $headers['X-Request-Id'] ?? '';
        $data = json_decode($rawBody, true);
        $dataId = $data['data']['id'] ?? '';
        $manifest = "id:{$dataId};request-id:{$requestId};ts:{$ts};";
        $calc = hash_hmac('sha256', $manifest, $this->webhookSecret);
        return hash_equals($calc, $v1);
    }

    public function parseWebhook(array $headers, string $rawBody): array
    {
        $data = json_decode($rawBody, true) ?: [];
        $type = $data['type'] ?? $data['topic'] ?? null;
        $paymentId = $data['data']['id'] ?? ($data['resource'] ?? null);

        $status = 'pending';
        $reference = null;
        $transactionId = $paymentId;

        // Consulta o pagamento para obter status e external_reference reais.
        if ($paymentId && ($type === 'payment' || $type === null)) {
            $res = $this->request('GET', 'https://api.mercadopago.com/v1/payments/' . rawurlencode((string) $paymentId));
            if ($res['ok']) {
                $p = $res['data'];
                $reference = $p['external_reference'] ?? null;
                $status = $this->mapStatus($p['status'] ?? 'pending');
                $transactionId = (string) ($p['id'] ?? $paymentId);
            }
        }

        return [
            'event_id' => (string) ($paymentId ?? ''),
            'event_type' => $type,
            'transaction_id' => $transactionId,
            'reference' => $reference,
            'status' => $status,
        ];
    }

    protected function mapStatus(string $mpStatus): string
    {
        return match ($mpStatus) {
            'approved' => 'approved',
            'rejected' => 'refused',
            'cancelled' => 'canceled',
            'refunded', 'charged_back' => 'refunded',
            'in_process', 'pending', 'authorized' => 'pending',
            default => 'pending',
        };
    }

    protected function request(string $method, string $url, ?array $body = null): array
    {
        $ch = curl_init($url);
        $headers = ['Authorization: Bearer ' . $this->accessToken, 'Content-Type: application/json'];
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
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
        return ['ok' => false, 'error' => $data['message'] ?? ('HTTP ' . $code), 'data' => $data];
    }

    public function name(): string
    {
        return 'mercadopago';
    }
}
