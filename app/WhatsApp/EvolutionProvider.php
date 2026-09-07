<?php

namespace App\WhatsApp;

/**
 * Provedor WhatsApp via Evolution API (auto-hospedada).
 *
 * Envia mensagens de texto atraves do endpoint:
 *   POST {baseUrl}/message/sendText/{instance}
 *   Header: apikey: {apiKey}
 *   Body:   { "number": "5511999999999", "text": "..." }
 *
 * Credenciais/parametros vem das settings (nao hardcoded):
 *   whatsapp.api_url          -> base URL da Evolution (ex: https://evo.seudominio.com)
 *   whatsapp.token            -> apikey
 *   whatsapp.phone_number_id  -> nome da instancia (instance)
 */
class EvolutionProvider implements WhatsAppProvider
{
    protected string $baseUrl;
    protected string $apiKey;
    protected string $instance;

    public function __construct(string $baseUrl, string $apiKey, string $instance)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->apiKey = $apiKey;
        $this->instance = $instance;
    }

    public function sendText(string $to, string $message): array
    {
        if ($this->baseUrl === '' || $this->apiKey === '' || $this->instance === '') {
            return ['success' => false, 'error' => 'Evolution API incompleta (URL, apikey e instancia sao obrigatorios).'];
        }

        $endpoint = "{$this->baseUrl}/message/sendText/" . rawurlencode($this->instance);
        $payload = [
            'number' => preg_replace('/\D+/', '', $to),
            'text' => $message,
        ];

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'apikey: ' . $this->apiKey,
            ],
            CURLOPT_TIMEOUT => 20,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            return ['success' => false, 'error' => $error ?: 'Falha na conexao com a Evolution API.'];
        }

        $data = json_decode((string) $response, true);
        if ($httpCode >= 200 && $httpCode < 300) {
            return [
                'success' => true,
                'message_id' => $data['key']['id'] ?? ($data['messageId'] ?? null),
            ];
        }

        $msg = $data['message'] ?? ($data['error'] ?? ('HTTP ' . $httpCode));
        if (is_array($msg)) {
            $msg = implode('; ', $msg);
        }
        return ['success' => false, 'error' => (string) $msg];
    }

    public function name(): string
    {
        return 'evolution';
    }
}
