<?php

namespace App\Services;

use App\Core\Database;
use App\WhatsApp\CloudApiProvider;
use App\WhatsApp\EvolutionProvider;
use App\WhatsApp\NullProvider;
use App\WhatsApp\WhatsAppProvider;

/**
 * Camada de abstracao do WhatsApp. Resolve o provedor conforme as settings
 * e envia mensagens/templates. Trocar de provedor exige apenas adicionar
 * uma classe que implemente WhatsAppProvider e mape-la aqui.
 */
class WhatsAppService
{
    protected Database $db;
    protected SettingsService $settings;
    protected ?WhatsAppProvider $provider = null;

    public function __construct(Database $db, SettingsService $settings)
    {
        $this->db = $db;
        $this->settings = $settings;
    }

    public function provider(): WhatsAppProvider
    {
        if ($this->provider !== null) {
            return $this->provider;
        }

        $name = (string) $this->settings->get('whatsapp.provider', '');
        $token = (string) $this->settings->get('whatsapp.token', '');

        $this->provider = match ($name) {
            'cloud_api' => $token
                ? new CloudApiProvider(
                    $token,
                    (string) $this->settings->get('whatsapp.phone_number_id', ''),
                    (string) $this->settings->get('whatsapp.api_url', '')
                )
                : new NullProvider(),
            'evolution' => ($token && $this->settings->get('whatsapp.api_url'))
                ? new EvolutionProvider(
                    (string) $this->settings->get('whatsapp.api_url', ''),
                    $token,
                    (string) $this->settings->get('whatsapp.phone_number_id', '')
                )
                : new NullProvider(),
            default => new NullProvider(),
        };

        return $this->provider;
    }

    public function isConfigured(): bool
    {
        return $this->provider()->name() !== 'null';
    }

    public function send(string $to, string $message): array
    {
        return $this->provider()->sendText($to, $message);
    }

    public function sendTemplate(string $slug, string $to, array $vars = []): array
    {
        $template = $this->db->selectOne('SELECT * FROM whatsapp_templates WHERE slug = ? AND is_active = 1', [$slug]);
        if (!$template) {
            return ['success' => false, 'error' => "Template nao encontrado: {$slug}"];
        }
        $message = $this->interpolate($template['body'], $vars);
        return $this->send($to, $message);
    }

    protected function interpolate(string $text, array $vars): string
    {
        return preg_replace_callback('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', function ($m) use ($vars) {
            return (string) ($vars[$m[1]] ?? '');
        }, $text);
    }
}
