<?php

namespace App\Controllers\Checkout;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Services\CouponService;
use App\Services\EventService;
use App\Services\OrderService;
use App\Services\PaymentService;

/**
 * Checkout: exibe o produto, cria o pedido, aplica cupom e inicia o pagamento
 * no gateway configurado. Registra inicio de checkout (recuperacao/abandono).
 */
class CheckoutController extends Controller
{
    protected Database $db;
    protected OrderService $orders;
    protected PaymentService $payments;
    protected CouponService $coupons;
    protected EventService $events;

    public function __construct(Database $db, OrderService $orders, PaymentService $payments, CouponService $coupons, EventService $events)
    {
        $this->db = $db;
        $this->orders = $orders;
        $this->payments = $payments;
        $this->coupons = $coupons;
        $this->events = $events;
    }

    public function show(Request $request): Response
    {
        $product = $this->product((string) $request->param('slug'));
        $user = $this->auth()->user();

        // Registra inicio de checkout (para recuperacao de abandono).
        try {
            $this->db->insert(
                'INSERT INTO checkout_abandonment (user_id, email, product_id, amount, attribution_id, status, created_at, updated_at)
                 VALUES (?,?,?,?,?,?,?,?)',
                [
                    $user['id'] ?? null, $user['email'] ?? null, $product['id'],
                    $this->orders->effectivePrice($product),
                    app(\App\Services\AttributionService::class)->currentAttributionId(),
                    'started', now(), now(),
                ]
            );
        } catch (\Throwable $e) {}

        $this->events->dispatch('CheckoutStarted', [
            'user_id' => $user['id'] ?? null,
            'email' => $user['email'] ?? null,
            'value' => $this->orders->effectivePrice($product),
        ]);

        return $this->view('checkout.show', [
            'title' => 'Checkout',
            'product' => $product,
            'price' => $this->orders->effectivePrice($product),
            'user' => $user,
            'paymentConfigured' => $this->payments->isConfigured(),
        ]);
    }

    public function process(Request $request): Response
    {
        $product = $this->product((string) $request->param('slug'));
        $user = $this->auth()->user();
        $email = $user['email'] ?? trim((string) $request->input('email'));

        if (!$user && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->withErrors(['email' => 'Informe um e-mail valido.']);
            return $this->redirect('/checkout/' . $product['slug']);
        }

        $price = $this->orders->effectivePrice($product);

        // Cupom (opcional).
        $discount = 0.0;
        $couponId = null;
        $code = trim((string) $request->input('coupon'));
        if ($code !== '') {
            $result = $this->coupons->validate($code, $price, (int) $product['id']);
            if ($result['valid']) {
                $discount = $result['discount'];
                $couponId = (int) $result['coupon']['id'];
            } else {
                $this->withFlash('error', $result['message'] ?? 'Cupom invalido.');
                return $this->redirect('/checkout/' . $product['slug']);
            }
        }

        // Cria pedido pendente.
        $orderId = $this->orders->createForProduct($product, $user['id'] ?? null, $email, $discount, $couponId);
        if ($couponId) {
            $this->coupons->registerUse($couponId, $user['id'] ?? null, $orderId, $discount);
        }
        $order = $this->orders->find($orderId);

        // Gateway configurado?
        if (!$this->payments->isConfigured()) {
            $this->withFlash('warning', 'Pagamento ainda nao configurado pelo administrador.');
            return $this->redirect('/checkout/erro/' . $orderId);
        }

        $gateway = $this->payments->gateway();
        $charge = $gateway->createCharge([
            'reference' => $order['reference'],
            'total' => $order['total'],
            'email' => $email,
            'description' => $product['name'],
            'success_url' => url('/checkout/sucesso/' . $orderId),
            'error_url' => url('/checkout/erro/' . $orderId),
            'webhook_url' => url('/api/webhooks/' . $gateway->name()),
        ]);

        if (!$charge['success']) {
            $this->orders->updateStatus($orderId, 'refused', null, $gateway->name(), $charge);
            $this->withFlash('error', $charge['error'] ?? 'Falha ao iniciar pagamento.');
            return $this->redirect('/checkout/erro/' . $orderId);
        }

        // Guarda transaction_id inicial.
        $this->db->execute('UPDATE orders SET gateway = ?, meta = ?, updated_at = ? WHERE id = ?', [
            $gateway->name(),
            json_encode(['transaction_id' => $charge['transaction_id'] ?? null], JSON_UNESCAPED_UNICODE),
            now(), $orderId,
        ]);

        // Redireciona ao checkout do gateway.
        if (!empty($charge['redirect_url'])) {
            return $this->redirect($charge['redirect_url']);
        }
        return $this->redirect('/checkout/sucesso/' . $orderId);
    }

    public function success(Request $request): Response
    {
        $order = $this->orders->find((int) $request->param('order'));
        if (!$order) {
            $this->abort(404);
        }
        // Verifica se ha upsell para o produto comprado.
        $upsell = $this->firstUpsell($order);
        if ($upsell && in_array($order['status'], ['approved', 'pending'], true) && !$order['is_upsell']) {
            return $this->redirect('/upsell/' . $order['id']);
        }
        return $this->view('checkout.success', ['title' => 'Compra recebida', 'order' => $order]);
    }

    public function error(Request $request): Response
    {
        $order = $this->orders->find((int) $request->param('order'));
        return $this->view('checkout.error', ['title' => 'Pagamento', 'order' => $order]);
    }

    public function validateCoupon(Request $request): Response
    {
        $code = (string) $request->input('coupon');
        $slug = (string) $request->input('slug');
        $product = $this->db->selectOne('SELECT * FROM products WHERE slug = ?', [$slug]);
        if (!$product) {
            return $this->json(['valid' => false, 'message' => 'Produto invalido.']);
        }
        $price = $this->orders->effectivePrice($product);
        $result = $this->coupons->validate($code, $price, (int) $product['id']);
        return $this->json([
            'valid' => $result['valid'],
            'discount' => $result['discount'],
            'total' => round($price - $result['discount'], 2),
            'message' => $result['message'] ?? null,
        ]);
    }

    protected function firstUpsell(array $order): ?array
    {
        $items = $order['items'] ?? [];
        foreach ($items as $item) {
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

    protected function product(string $slug): array
    {
        $product = $this->db->selectOne('SELECT * FROM products WHERE slug = ? AND is_active = 1', [$slug]);
        if (!$product) {
            $this->abort(404, 'Produto nao encontrado.');
        }
        return $product;
    }
}
