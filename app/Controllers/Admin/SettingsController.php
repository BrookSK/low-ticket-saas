<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Services\LogService;
use App\Services\SettingsService;

/**
 * Configuracoes gerenciaveis pelo painel (substitui .env).
 *
 * O schema abaixo descreve cada grupo de configuracao e seus campos,
 * incluindo tipo, se e sensivel (criptografado) e ajuda. As views usam
 * este schema para renderizar os formularios dinamicamente.
 */
class SettingsController extends Controller
{
    protected SettingsService $settings;
    protected LogService $log;

    public function __construct(SettingsService $settings, LogService $log)
    {
        $this->settings = $settings;
        $this->log = $log;
    }

    /**
     * Schema declarativo das configuracoes por grupo.
     * campo: [label, tipo(text|password|number|select|toggle|textarea|email), sensivel, options?, hint?]
     */
    public static function schema(): array
    {
        return [
            'general' => [
                'label' => 'Geral',
                'fields' => [
                    'app.name' => ['Nome da aplicacao', 'text', false],
                    'app.url' => ['URL base (ex: https://app.seudominio.com)', 'text', false],
                    'app.support_email' => ['E-mail de suporte', 'email', false],
                    'app.logo' => ['URL do logo', 'text', false],
                    'app.favicon' => ['URL do favicon', 'text', false],
                    'app.currency' => ['Moeda', 'select', false, ['BRL' => 'Real (BRL)', 'USD' => 'Dolar (USD)', 'EUR' => 'Euro (EUR)']],
                    'app.timezone' => ['Fuso horario', 'text', false, null, 'Ex: America/Sao_Paulo'],
                ],
            ],
            'payment' => [
                'label' => 'Pagamentos',
                'fields' => [
                    'payment.provider' => ['Gateway ativo', 'select', false, ['' => 'Nenhum', 'mercadopago' => 'Mercado Pago', 'stripe' => 'Stripe', 'asaas' => 'Asaas']],
                    'payment.mode' => ['Ambiente', 'select', false, ['sandbox' => 'Sandbox (teste)', 'production' => 'Producao']],
                    'payment.mercadopago.access_token' => ['Mercado Pago - Access Token', 'password', true],
                    'payment.mercadopago.public_key' => ['Mercado Pago - Public Key', 'text', false],
                    'payment.mercadopago.webhook_secret' => ['Mercado Pago - Webhook Secret', 'password', true],
                    'payment.stripe.secret_key' => ['Stripe - Secret Key', 'password', true],
                    'payment.stripe.publishable_key' => ['Stripe - Publishable Key', 'text', false],
                    'payment.stripe.webhook_secret' => ['Stripe - Webhook Secret', 'password', true],
                    'payment.asaas.api_key' => ['Asaas - API Key', 'password', true],
                    'payment.asaas.webhook_token' => ['Asaas - Webhook Token', 'password', true],
                ],
            ],
            'mail' => [
                'label' => 'E-mail (SMTP)',
                'fields' => [
                    'mail.host' => ['Host SMTP', 'text', false],
                    'mail.port' => ['Porta', 'number', false],
                    'mail.username' => ['Usuario', 'text', false],
                    'mail.password' => ['Senha', 'password', true],
                    'mail.encryption' => ['Criptografia', 'select', false, ['tls' => 'TLS', 'ssl' => 'SSL', 'none' => 'Nenhuma']],
                    'mail.from_address' => ['Remetente (e-mail)', 'email', false],
                    'mail.from_name' => ['Remetente (nome)', 'text', false],
                ],
            ],
            'whatsapp' => [
                'label' => 'WhatsApp',
                'fields' => [
                    'whatsapp.provider' => ['Provedor', 'select', false, ['' => 'Nenhum', 'evolution' => 'Evolution API', 'cloud_api' => 'WhatsApp Cloud API (Meta)']],
                    'whatsapp.api_url' => ['API URL (base) — ex: https://evo.seudominio.com', 'text', false, null, 'Evolution: URL base da sua instancia. Cloud API: opcional.'],
                    'whatsapp.token' => ['Token / apikey', 'password', true, null, 'Evolution: apikey. Cloud API: token de acesso.'],
                    'whatsapp.phone_number_id' => ['Instancia / Phone Number ID', 'text', false, null, 'Evolution: nome da instancia. Cloud API: Phone Number ID.'],
                    'whatsapp.phone_number' => ['Numero do remetente (E.164)', 'text', false],
                    'whatsapp.webhook_verify_token' => ['Webhook Verify Token', 'password', true],
                ],
            ],
            'google' => [
                'label' => 'Google',
                'fields' => [
                    'google.analytics_enabled' => ['Ativar Google Analytics', 'toggle', false],
                    'google.analytics_id' => ['Measurement ID (GA4)', 'text', false, null, 'Ex: G-XXXXXXX'],
                    'google.ads_enabled' => ['Ativar Google Ads', 'toggle', false],
                    'google.ads_conversion_id' => ['Ads Conversion ID', 'text', false],
                    'google.ads_conversion_label' => ['Ads Conversion Label', 'text', false],
                    'google.gtm_enabled' => ['Ativar Tag Manager', 'toggle', false],
                    'google.tag_manager_id' => ['GTM ID', 'text', false, null, 'Ex: GTM-XXXXXX'],
                ],
            ],
            'meta' => [
                'label' => 'Meta (Facebook)',
                'fields' => [
                    'meta.pixel_enabled' => ['Ativar Meta Pixel', 'toggle', false],
                    'meta.pixel_id' => ['Pixel ID', 'text', false],
                ],
            ],
            'security' => [
                'label' => 'Seguranca',
                'fields' => [
                    'security.max_login_attempts' => ['Max. tentativas de login', 'number', false],
                    'security.lockout_minutes' => ['Bloqueio (minutos)', 'number', false],
                    'security.session_lifetime' => ['Sessao (minutos)', 'number', false],
                ],
            ],
            'marketing' => [
                'label' => 'Marketing',
                'fields' => [
                    'marketing.cookie_consent_enabled' => ['Banner de cookies', 'toggle', false],
                    'marketing.attribution_window_days' => ['Janela de atribuicao (dias)', 'number', false],
                ],
            ],
            'automation' => [
                'label' => 'Recuperacao',
                'fields' => [
                    'automation.recovery_email_1_minutes' => ['1o e-mail apos (min)', 'number', false],
                    'automation.recovery_whatsapp_hours' => ['WhatsApp apos (horas)', 'number', false],
                    'automation.recovery_email_2_hours' => ['2o e-mail apos (horas)', 'number', false],
                ],
            ],
        ];
    }

    public function index(): Response
    {
        return $this->redirect('/admin/configuracoes/general');
    }

    public function group(Request $request): Response
    {
        $group = (string) $request->param('group');
        $schema = self::schema();
        if (!isset($schema[$group])) {
            $this->abort(404, 'Grupo de configuracao nao encontrado.');
        }

        // Monta valores atuais (mascara sensiveis).
        $values = [];
        foreach ($schema[$group]['fields'] as $key => $meta) {
            $isSensitive = $meta[2] ?? false;
            $current = $this->settings->get($key, '');
            if ($isSensitive && $current !== '' && $current !== null) {
                // Nao expoe o valor real; indica que esta preenchido.
                $values[$key] = '__SET__';
            } else {
                $values[$key] = $current;
            }
        }

        return $this->view('admin.settings', [
            'title' => 'Configuracoes',
            'schema' => $schema,
            'group' => $group,
            'groupData' => $schema[$group],
            'values' => $values,
        ]);
    }

    public function save(Request $request): Response
    {
        $group = (string) $request->param('group');
        $schema = self::schema();
        if (!isset($schema[$group])) {
            $this->abort(404);
        }

        $items = [];
        $meta = [];
        foreach ($schema[$group]['fields'] as $key => $field) {
            [$label, $type, $sensitive] = [$field[0], $field[1], $field[2] ?? false];
            $inputName = str_replace('.', '__', $key);
            $value = $request->input($inputName);

            if ($type === 'toggle') {
                $value = $value ? '1' : '0';
                $items[$key] = $value;
                $meta[$key] = ['type' => 'bool', 'encrypt' => false];
                continue;
            }

            // Campo sensivel: se veio o placeholder OU em branco, mantem o valor existente
            // (o input de campos sensiveis sempre chega vazio por seguranca). Para limpar
            // um segredo, o admin deve remove-lo por outro fluxo.
            if ($sensitive && ($value === '__SET__' || $value === '' || $value === null)) {
                continue;
            }

            $storeType = in_array($type, ['number'], true) ? 'int' : 'string';
            $items[$key] = $value;
            $meta[$key] = ['type' => $storeType, 'encrypt' => (bool) $sensitive];
        }

        $this->settings->setMany($items, $meta);
        $this->settings->flush();

        $this->log->admin('settings_updated', $this->auth()->id(), "Configuracoes do grupo '{$group}' atualizadas");
        $this->withFlash('success', 'Configuracoes salvas com sucesso.');
        return $this->redirect('/admin/configuracoes/' . $group);
    }
}
