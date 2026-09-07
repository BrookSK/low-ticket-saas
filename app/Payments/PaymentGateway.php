<?php

namespace App\Payments;

/**
 * Contrato de gateway de pagamento. Permite adicionar novos provedores
 * (Mercado Pago, Stripe, Asaas, etc.) sem alterar o restante do sistema.
 */
interface PaymentGateway
{
    /**
     * Cria uma cobranca/checkout para o pedido e retorna dados para redirecionar
     * ou renderizar o pagamento.
     *
     * @param array $order  Dados do pedido (reference, total, email, etc.)
     * @return array{
     *   success: bool,
     *   redirect_url?: string,
     *   transaction_id?: string,
     *   raw?: array,
     *   error?: string
     * }
     */
    public function createCharge(array $order): array;

    /**
     * Valida a assinatura/autenticidade de um webhook recebido.
     */
    public function verifyWebhook(array $headers, string $rawBody): bool;

    /**
     * Normaliza o payload de um webhook para um formato interno comum.
     *
     * @return array{
     *   event_id: ?string,
     *   event_type: ?string,
     *   transaction_id: ?string,
     *   reference: ?string,
     *   status: string   // pending|approved|refused|canceled|refunded|expired
     * }
     */
    public function parseWebhook(array $headers, string $rawBody): array;

    public function name(): string;
}
