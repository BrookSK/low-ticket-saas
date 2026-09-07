<?php

namespace App\Services;

use App\Payments\AsaasGateway;
use App\Payments\MercadoPagoGateway;
use App\Payments\NullGateway;
use App\Payments\PaymentGateway;
use App\Payments\StripeGateway;

/**
 * Fabrica de gateways de pagamento. Resolve o provedor conforme as settings.
 * Adicionar novo gateway = criar classe PaymentGateway + registrar aqui.
 */
class PaymentService
{
    protected SettingsService $settings;
    protected ?PaymentGateway $gateway = null;

    public function __construct(SettingsService $settings)
    {
        $this->settings = $settings;
    }

    public function gateway(?string $name = null): PaymentGateway
    {
        $name = $name ?: (string) $this->settings->get('payment.provider', '');
        $sandbox = $this->settings->get('payment.mode', 'sandbox') !== 'production';

        return match ($name) {
            'mercadopago' => ($token = (string) $this->settings->get('payment.mercadopago.access_token'))
                ? new MercadoPagoGateway($token, (string) $this->settings->get('payment.mercadopago.webhook_secret', ''), $sandbox)
                : new NullGateway(),
            'stripe' => ($key = (string) $this->settings->get('payment.stripe.secret_key'))
                ? new StripeGateway($key, (string) $this->settings->get('payment.stripe.webhook_secret', ''))
                : new NullGateway(),
            'asaas' => ($apiKey = (string) $this->settings->get('payment.asaas.api_key'))
                ? new AsaasGateway($apiKey, (string) $this->settings->get('payment.asaas.webhook_token', ''), $sandbox)
                : new NullGateway(),
            default => new NullGateway(),
        };
    }

    public function isConfigured(): bool
    {
        return $this->gateway()->name() !== 'null';
    }
}
