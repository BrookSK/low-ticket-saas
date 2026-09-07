<?php

namespace App\Payments;

/**
 * Integracao com Stripe via Checkout Sessions.
 * Credenciais vem das settings (nao hardcoded).
 */
class StripeGateway implements PaymentGateway
{
    protected string $secretKey;
    protected string $webhookSecret;

    public function __construct(string $secretKey, string $webhookSecret = '')
    {
        $this->secretKey = $secretKey;
        $this->webhookSecret = $webhookSecret;
    }

    public function createCharge(array $order): array
    {
        // Stripe Checkout Session usa form-encoded.
        $amount = (int) round(((float) $order['total']) * 100); // centavos
        $fields = [
            'mode' => 'payment',
            'success_url' => ($order['success_url'] ?? '') ,
            'cancel_url' => ($order['error_url'] ?? ''),
            'client_reference_id' => $order['reference'],
            'line_items[0][quantity]' => 1,
            'line_items[0][price_data][currency]' => 'brl',
            'line_items[0][price_data][unit_amount]' => $amount,
            'line_items[0][price_data][product_data][name]' => $order['description'] ?? ('Pedido ' . $order['reference']),
        ];
        if (!empty($order['email'])) {
            $fields['customer_email'] = $order['email'];
        }

        $res = $this->request('POST', 'https://api.stripe.com/v1/checkout/sessions', $fields);
        if (!$res['ok']) {
            return ['success' => false, 'error' => $res['error'] ?? 'Falha ao criar sessao Stripe.'];
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
        if ($this->webhookSecret === '') {
            return true;
        }
        $sig = $headers['stripe-signature'] ?? $headers['Stripe-Signature'] ?? '';
        if ($sig === '') {
            return false;
        }
        $parts = [];
        foreach (explode(',', $sig) as $seg) {
            $kv = explode('=', trim($seg), 2);
            if (count($kv) === 2) {
                $parts[$kv[0]] = $kv[1];
            }
        }
        $t = $parts['t'] ?? '';
        $v1 = $parts['v1'] ?? '';
        $signedPayload = $t . '.' . $rawBody;
        $calc = hash_hmac('sha256', $signedPayload, $this->webhookSecret);
        return hash_equals($calc, $v1);
    }

    public function parseWebhook(array $headers, string $rawBody): array
    {
        $data = json_decode($rawBody, true) ?: [];
        $type = $data['type'] ?? null;
        $object = $data['data']['object'] ?? [];

        $status = match ($type) {
            'checkout.session.completed', 'payment_intent.succeeded' => 'approved',
            'checkout.session.expired' => 'expired',
            'payment_intent.payment_failed' => 'refused',
            'charge.refunded' => 'refunded',
            default => 'pending',
        };

        return [
            'event_id' => $data['id'] ?? null,
            'event_type' => $type,
            'transaction_id' => $object['id'] ?? null,
            'reference' => $object['client_reference_id'] ?? null,
            'status' => $status,
        ];
    }

    protected function request(string $method, string $url, array $fields): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => http_build_query($fields),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->secretKey,
                'Content-Type: application/x-www-form-urlencoded',
            ],
            CURLOPT_TIMEOUT => 25,
        ]);
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
        return ['ok' => false, 'error' => $data['error']['message'] ?? ('HTTP ' . $code)];
    }

    public function name(): string
    {
        return 'stripe';
    }
}
