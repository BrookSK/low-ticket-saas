<?php

namespace App\Services;

use App\Core\Database;

/**
 * Validacao e aplicacao de cupons de desconto.
 */
class CouponService
{
    protected Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Valida um cupom para um produto e valor. Retorna dados do desconto.
     *
     * @return array{valid: bool, discount: float, message?: string, coupon?: array}
     */
    public function validate(string $code, float $amount, ?int $productId = null): array
    {
        $code = strtoupper(trim($code));
        if ($code === '') {
            return ['valid' => false, 'discount' => 0, 'message' => 'Informe um cupom.'];
        }

        $coupon = $this->db->selectOne('SELECT * FROM coupons WHERE code = ? AND is_active = 1', [$code]);
        if (!$coupon) {
            return ['valid' => false, 'discount' => 0, 'message' => 'Cupom invalido.'];
        }

        $now = time();
        if ($coupon['valid_from'] && strtotime($coupon['valid_from']) > $now) {
            return ['valid' => false, 'discount' => 0, 'message' => 'Cupom ainda nao esta valido.'];
        }
        if ($coupon['valid_until'] && strtotime($coupon['valid_until']) < $now) {
            return ['valid' => false, 'discount' => 0, 'message' => 'Cupom expirado.'];
        }
        if ($coupon['max_uses'] !== null && (int) $coupon['used_count'] >= (int) $coupon['max_uses']) {
            return ['valid' => false, 'discount' => 0, 'message' => 'Cupom esgotado.'];
        }
        if (!empty($coupon['applicable_products']) && $productId !== null) {
            $allowed = array_map('intval', array_filter(explode(',', $coupon['applicable_products'])));
            if (!empty($allowed) && !in_array($productId, $allowed, true)) {
                return ['valid' => false, 'discount' => 0, 'message' => 'Cupom nao se aplica a este produto.'];
            }
        }

        if ($coupon['type'] === 'percent') {
            $discount = round($amount * ((float) $coupon['percent'] / 100), 2);
        } else {
            $discount = round((float) $coupon['amount'], 2);
        }
        $discount = min($discount, $amount);

        return ['valid' => true, 'discount' => $discount, 'coupon' => $coupon];
    }

    public function registerUse(int $couponId, ?int $userId, int $orderId, float $discount): void
    {
        $this->db->insert(
            'INSERT INTO coupon_usages (coupon_id, user_id, order_id, discount, created_at) VALUES (?,?,?,?,?)',
            [$couponId, $userId, $orderId, $discount, now()]
        );
        $this->db->execute('UPDATE coupons SET used_count = used_count + 1 WHERE id = ?', [$couponId]);
    }
}
