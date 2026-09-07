<?php

namespace App\Services;

use App\Core\Database;
use App\Models\User;

/**
 * Ciclo de vida do pedido: criacao, aprovacao, liberacao de acesso e eventos.
 * Reutilizado pelo checkout e pelos webhooks (idempotente na aprovacao).
 */
class OrderService
{
    protected Database $db;
    protected EventService $events;
    protected AttributionService $attribution;

    public function __construct(Database $db, EventService $events, AttributionService $attribution)
    {
        $this->db = $db;
        $this->events = $events;
        $this->attribution = $attribution;
    }

    public function generateReference(): string
    {
        return 'PED-' . strtoupper(bin2hex(random_bytes(5)));
    }

    /**
     * Cria um pedido pendente com um item de produto.
     */
    public function createForProduct(array $product, ?int $userId, ?string $email, float $discount = 0, ?int $couponId = null, bool $isUpsell = false, ?int $parentOrderId = null): int
    {
        $price = $this->effectivePrice($product);
        $subtotal = $price;
        $total = max(0, round($subtotal - $discount, 2));

        return $this->db->transaction(function (Database $db) use ($product, $userId, $email, $subtotal, $discount, $total, $couponId, $isUpsell, $parentOrderId) {
            $orderId = $db->insert(
                'INSERT INTO orders (reference, user_id, email, subtotal, discount, total, coupon_id, gateway, status, is_upsell, parent_order_id, attribution_id, created_at, updated_at)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                [
                    $this->generateReference(), $userId, $email, $subtotal, $discount, $total, $couponId,
                    (string) setting('payment.provider', ''), 'pending', $isUpsell ? 1 : 0, $parentOrderId,
                    $this->attribution->currentAttributionId(), now(), now(),
                ]
            );
            $db->insert(
                'INSERT INTO order_items (order_id, product_id, name, price, quantity, created_at) VALUES (?,?,?,?,1,?)',
                [$orderId, $product['id'], $product['name'], $price, now()]
            );
            return $orderId;
        });
    }

    public function effectivePrice(array $product): float
    {
        $promo = $product['promo_price'] ?? null;
        return $promo !== null && $promo !== '' && (float) $promo > 0 ? (float) $promo : (float) $product['price'];
    }

    public function find(int $id): ?array
    {
        $order = $this->db->selectOne('SELECT * FROM orders WHERE id = ?', [$id]);
        if ($order) {
            $order['items'] = $this->db->select('SELECT * FROM order_items WHERE order_id = ?', [$id]);
        }
        return $order;
    }

    public function findByReference(string $reference): ?array
    {
        $order = $this->db->selectOne('SELECT * FROM orders WHERE reference = ?', [$reference]);
        if ($order) {
            $order['items'] = $this->db->select('SELECT * FROM order_items WHERE order_id = ?', [$order['id']]);
        }
        return $order;
    }

    /**
     * Marca o pedido com um status vindo do gateway. Idempotente para 'approved'.
     * Retorna true se houve transicao para aprovado (para disparar liberacao 1x).
     */
    public function updateStatus(int $orderId, string $status, ?string $transactionId = null, ?string $gateway = null, ?array $gatewayResponse = null): bool
    {
        $order = $this->db->selectOne('SELECT * FROM orders WHERE id = ?', [$orderId]);
        if (!$order) {
            return false;
        }

        // Idempotencia: se ja aprovado, nao reprocessa a liberacao.
        $wasApproved = $order['status'] === 'approved';

        $paidAt = $status === 'approved' ? now() : $order['paid_at'];
        $this->db->execute(
            'UPDATE orders SET status = ?, gateway = COALESCE(?, gateway), paid_at = ?, updated_at = ? WHERE id = ?',
            [$status, $gateway, $paidAt, now(), $orderId]
        );

        // Registra/atualiza pagamento.
        $this->db->insert(
            'INSERT INTO payments (order_id, gateway, transaction_id, amount, status, gateway_response, created_at, updated_at)
             VALUES (?,?,?,?,?,?,?,?)',
            [
                $orderId, $gateway ?: $order['gateway'], $transactionId, $order['total'], $status,
                $gatewayResponse ? json_encode($gatewayResponse, JSON_UNESCAPED_UNICODE) : null, now(), now(),
            ]
        );

        if ($status === 'approved' && !$wasApproved) {
            $this->grantAccess($order);
            $this->events->dispatch('PaymentApproved', [
                'user_id' => $order['user_id'],
                'email' => $order['email'],
                'order_id' => $orderId,
                'transaction_id' => $transactionId,
                'value' => (float) $order['total'],
                'currency' => 'BRL',
            ]);
            return true;
        }

        if (in_array($status, ['refused', 'canceled', 'expired'], true)) {
            $this->events->dispatch('PaymentFailed', [
                'user_id' => $order['user_id'], 'email' => $order['email'], 'order_id' => $orderId, 'status' => $status,
            ]);
        }

        return false;
    }

    /**
     * Libera os modulos do(s) produto(s) do pedido ao usuario.
     */
    public function grantAccess(array $order): void
    {
        if (!$order['user_id']) {
            return;
        }
        $items = $this->db->select('SELECT * FROM order_items WHERE order_id = ?', [$order['id']]);
        foreach ($items as $item) {
            if (!$item['product_id']) {
                continue;
            }
            $product = $this->db->selectOne('SELECT * FROM products WHERE id = ?', [$item['product_id']]);
            if (!$product || !$product['grants_access']) {
                continue;
            }
            $modules = array_filter(array_map('trim', explode(',', $product['grants_access'])));
            foreach ($modules as $module) {
                User::grantModule((int) $order['user_id'], $module, (int) $product['id'], 'purchase');
            }
        }
    }
}
