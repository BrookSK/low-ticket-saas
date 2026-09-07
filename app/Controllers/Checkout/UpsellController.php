<?php

namespace App\Controllers\Checkout;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Services\EventService;
use App\Services\OrderService;
use App\Services\PaymentService;

/**
 * Fluxo de upsell pos-compra. O usuario pode aceitar, recusar ou continuar
 * apenas com o produto inicial (nunca bloqueia o produto ja comprado).
 */
class UpsellController extends Controller
{
    protected Database $db;
    protected OrderService $orders;
    protected PaymentService $payments;
    protected EventService $events;

    public function __construct(Database $db, OrderService $orders, PaymentService $payments, EventService $events)
    {
        $this->db = $db;
        $this->orders = $orders;
        $this->payments = $payments;
        $this->events = $events;
    }

    public function show(Request $request): Response
    {
        $order = $this->orders->find((int) $request->param('order'));
        if (!$order) {
            $this->abort(404);
        }
        $upsell = $this->upsellFor($order);
        if (!$upsell) {
            return $this->redirect('/checkout/sucesso/' . $order['id']);
        }
        $offer = $this->db->selectOne('SELECT * FROM products WHERE id = ?', [$upsell['offer_product_id']]);
        $price = $upsell['price'] !== null ? (float) $upsell['price'] : $this->orders->effectivePrice($offer);

        $this->events->dispatch('UpsellShown', ['user_id' => $order['user_id'], 'order_id' => $order['id']]);

        return $this->view('checkout.upsell', [
            'title' => 'Oferta especial',
            'order' => $order,
            'upsell' => $upsell,
            'offer' => $offer,
            'price' => $price,
        ]);
    }

    public function accept(Request $request): Response
    {
        $order = $this->orders->find((int) $request->param('order'));
        if (!$order) {
            $this->abort(404);
        }
        $upsell = $this->upsellFor($order);
        if (!$upsell) {
            return $this->redirect('/checkout/sucesso/' . $order['id']);
        }
        $offer = $this->db->selectOne('SELECT * FROM products WHERE id = ?', [$upsell['offer_product_id']]);

        // Aplica preco especial do upsell (se definido) usando promo_price temporario.
        $product = $offer;
        if ($upsell['price'] !== null) {
            $product['promo_price'] = $upsell['price'];
        }

        $newOrderId = $this->orders->createForProduct(
            $product, $order['user_id'], $order['email'], 0, null, true, (int) $order['id']
        );

        $this->events->dispatch('UpsellAccepted', ['user_id' => $order['user_id'], 'order_id' => $newOrderId]);

        if (!$this->payments->isConfigured()) {
            return $this->redirect('/checkout/erro/' . $newOrderId);
        }

        $gateway = $this->payments->gateway();
        $newOrder = $this->orders->find($newOrderId);
        $charge = $gateway->createCharge([
            'reference' => $newOrder['reference'],
            'total' => $newOrder['total'],
            'email' => $order['email'],
            'description' => $offer['name'] . ' (upsell)',
            'success_url' => url('/checkout/sucesso/' . $newOrderId),
            'error_url' => url('/checkout/erro/' . $newOrderId),
            'webhook_url' => url('/api/webhooks/' . $gateway->name()),
        ]);

        if ($charge['success'] && !empty($charge['redirect_url'])) {
            $this->db->execute('UPDATE orders SET gateway = ?, updated_at = ? WHERE id = ?', [$gateway->name(), now(), $newOrderId]);
            return $this->redirect($charge['redirect_url']);
        }

        return $this->redirect('/checkout/sucesso/' . $newOrderId);
    }

    public function decline(Request $request): Response
    {
        $order = $this->orders->find((int) $request->param('order'));
        if ($order) {
            $this->events->dispatch('UpsellRejected', ['user_id' => $order['user_id'], 'order_id' => $order['id']]);
        }
        // Nao bloqueia nada: segue para o sucesso do produto inicial.
        return $this->redirect('/checkout/sucesso/' . ($order['id'] ?? ''));
    }

    protected function upsellFor(array $order): ?array
    {
        foreach (($order['items'] ?? []) as $item) {
            if (!$item['product_id']) {
                continue;
            }
            $upsell = $this->db->selectOne(
                'SELECT * FROM upsells WHERE trigger_product_id = ? AND is_active = 1 ORDER BY sort_order LIMIT 1',
                [$item['product_id']]
            );
            if ($upsell) {
                return $upsell;
            }
        }
        return null;
    }
}
