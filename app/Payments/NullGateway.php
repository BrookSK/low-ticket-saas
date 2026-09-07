<?php

namespace App\Payments;

/**
 * Gateway "nulo" usado quando nenhum provedor foi configurado.
 * NAO processa pagamentos reais nem simula aprovacao: apenas informa
 * claramente que a configuracao e necessaria. Isso evita "integracoes
 * simuladas" mascaradas de reais.
 */
class NullGateway implements PaymentGateway
{
    public function createCharge(array $order): array
    {
        return [
            'success' => false,
            'error' => 'Nenhum gateway de pagamento configurado. Configure em Admin > Configuracoes > Pagamentos.',
        ];
    }

    public function verifyWebhook(array $headers, string $rawBody): bool
    {
        return false;
    }

    public function parseWebhook(array $headers, string $rawBody): array
    {
        return ['event_id' => null, 'event_type' => null, 'transaction_id' => null, 'reference' => null, 'status' => 'pending'];
    }

    public function name(): string
    {
        return 'null';
    }
}
