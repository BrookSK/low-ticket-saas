<?php

namespace App\WhatsApp;

/**
 * Provedor "nulo" usado quando o WhatsApp ainda nao foi configurado.
 * Apenas registra a intencao de envio em log, deixando claro que a
 * integracao precisa ser configurada no painel. Nao simula sucesso real.
 */
class NullProvider implements WhatsAppProvider
{
    public function sendText(string $to, string $message): array
    {
        logger()->warning('whatsapp', 'WhatsApp nao configurado. Mensagem nao enviada.', [
            'to' => $to,
        ]);
        return [
            'success' => false,
            'error' => 'WhatsApp nao configurado. Configure o provedor no painel administrativo.',
        ];
    }

    public function name(): string
    {
        return 'null';
    }
}
