<?php

namespace App\WhatsApp;

/**
 * Provedor WhatsApp Cloud API (Meta). Envia mensagens via Graph API.
 * Requer, nas settings: whatsapp.token, whatsapp.phone_number_id e
 * opcionalmente whatsapp.api_url (default para a Graph API v19.0).
 */
class CloudApiProvider implements WhatsAppProvider
{
    protected string $token;
    protected string $phoneNumberId;
    protected string $apiUrl;

    public function __construct(string $token, string $phoneNumberId, string $apiUrl = '')
    {
        $this->token = $token;
        $this->phoneNumberId = $phoneNumberId;
        $this->apiUrl = $apiUrl !== '' ? rtrim($apiUrl, '/') : 'https://graph.facebook.com/v19.0';
    }

    public function sendText(string $to, string $message): array
    {
        $endpoint = "{$this->apiUrl}/{$this->phoneNumberId}/messages";
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => preg_replace('/\D+/', '', $to),
            'type' => 'text',
            'text' => ['body' => $message],
        ];

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->token,
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT => 20,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            return ['success' => false, 'error' => $error ?: 'Falha na conexao com a API do WhatsApp.'];
        }

        $data = json_decode((string) $response, true);
        if ($httpCode >= 200 && $httpCode < 300) {
            return ['success' => true, 'message_id' => $data['messages'][0]['id'] ?? null];
        }

        return ['success' => false, 'error' => $data['error']['message'] ?? 'Erro ao enviar mensagem.'];
    }

    public function name(): string
    {
        return 'cloud_api';
    }
}
