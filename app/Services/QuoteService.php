<?php

namespace App\Services;

use App\Core\Database;

/**
 * Regras de negocio dos orcamentos: calculo de totais, criacao/atualizacao
 * transacional com itens, transicoes de status e historico.
 */
class QuoteService
{
    protected Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Calcula subtotal, desconto, acrescimo e total a partir de itens e parametros.
     *
     * @param array<int, array{quantity: float, unit_price: float}> $items
     * @return array{subtotal: float, discount: float, surcharge: float, total: float, items: array}
     */
    public function calculate(array $items, string $discountType, float $discountValue, float $surcharge): array
    {
        $subtotal = 0.0;
        $normalized = [];
        foreach ($items as $item) {
            $qty = (float) ($item['quantity'] ?? 0);
            $price = (float) ($item['unit_price'] ?? 0);
            $lineTotal = round($qty * $price, 2);
            $subtotal += $lineTotal;
            $normalized[] = [
                'service_id' => $item['service_id'] ?? null,
                'description' => trim((string) ($item['description'] ?? '')),
                'quantity' => $qty,
                'unit_price' => $price,
                'total' => $lineTotal,
            ];
        }
        $subtotal = round($subtotal, 2);

        if ($discountType === 'percent') {
            $discount = round($subtotal * ($discountValue / 100), 2);
        } else {
            $discount = round($discountValue, 2);
        }
        $discount = min($discount, $subtotal); // nao permite desconto maior que o subtotal
        $surcharge = round(max(0, $surcharge), 2);

        $total = round($subtotal - $discount + $surcharge, 2);
        $total = (float) max(0, $total);

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'surcharge' => $surcharge,
            'total' => $total,
            'items' => $normalized,
        ];
    }

    /**
     * Gera o proximo numero sequencial de orcamento para o usuario.
     */
    public function nextNumber(int $userId): string
    {
        $row = $this->db->selectOne('SELECT COUNT(*) c FROM quotes WHERE user_id = ?', [$userId]);
        $seq = (int) ($row['c'] ?? 0) + 1;
        return 'ORC-' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Cria um orcamento com seus itens (transacional).
     */
    public function create(int $userId, array $data, array $items): int
    {
        $calc = $this->calculate($items, $data['discount_type'], (float) $data['discount_value'], (float) $data['surcharge']);

        return $this->db->transaction(function (Database $db) use ($userId, $data, $calc) {
            $quoteId = $db->insert(
                'INSERT INTO quotes (user_id, customer_id, number, public_token, title, status, subtotal,
                    discount_type, discount_value, surcharge, total, notes, payment_terms, execution_deadline, valid_until, created_at, updated_at)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                [
                    $userId, $data['customer_id'] ?: null, $this->nextNumber($userId), bin2hex(random_bytes(16)),
                    $data['title'] ?: null, 'draft', $calc['subtotal'], $data['discount_type'],
                    (float) $data['discount_value'], $calc['surcharge'], $calc['total'],
                    $data['notes'] ?: null, $data['payment_terms'] ?: null, $data['execution_deadline'] ?: null,
                    $data['valid_until'] ?: null, now(), now(),
                ]
            );
            $this->insertItems($db, $quoteId, $calc['items']);
            $this->recordStatus($db, $quoteId, null, 'draft', 'user');
            return $quoteId;
        });
    }

    /**
     * Atualiza um orcamento e seus itens (recria itens).
     */
    public function update(int $quoteId, array $data, array $items): void
    {
        $calc = $this->calculate($items, $data['discount_type'], (float) $data['discount_value'], (float) $data['surcharge']);

        $this->db->transaction(function (Database $db) use ($quoteId, $data, $calc) {
            $db->execute(
                'UPDATE quotes SET customer_id=?, title=?, subtotal=?, discount_type=?, discount_value=?, surcharge=?,
                    total=?, notes=?, payment_terms=?, execution_deadline=?, valid_until=?, updated_at=? WHERE id=?',
                [
                    $data['customer_id'] ?: null, $data['title'] ?: null, $calc['subtotal'], $data['discount_type'],
                    (float) $data['discount_value'], $calc['surcharge'], $calc['total'], $data['notes'] ?: null,
                    $data['payment_terms'] ?: null, $data['execution_deadline'] ?: null, $data['valid_until'] ?: null,
                    now(), $quoteId,
                ]
            );
            $db->execute('DELETE FROM quote_items WHERE quote_id = ?', [$quoteId]);
            $this->insertItems($db, $quoteId, $calc['items']);
        });
    }

    protected function insertItems(Database $db, int $quoteId, array $items): void
    {
        $order = 0;
        foreach ($items as $item) {
            if ($item['description'] === '' && $item['total'] == 0) {
                continue;
            }
            $db->insert(
                'INSERT INTO quote_items (quote_id, service_id, description, quantity, unit_price, total, sort_order)
                 VALUES (?,?,?,?,?,?,?)',
                [$quoteId, $item['service_id'] ?: null, $item['description'], $item['quantity'], $item['unit_price'], $item['total'], $order++]
            );
        }
    }

    /**
     * Muda o status registrando historico. Retorna true se mudou.
     */
    public function changeStatus(int $quoteId, string $to, string $actor = 'user', ?string $ip = null, ?string $note = null): bool
    {
        $quote = $this->db->selectOne('SELECT status FROM quotes WHERE id = ?', [$quoteId]);
        if (!$quote) {
            return false;
        }
        $from = $quote['status'];
        if ($from === $to) {
            return false;
        }

        $extra = '';
        $params = [$to, now(), $quoteId];
        if ($to === 'sent') {
            $extra = ', sent_at = ?';
            array_splice($params, 2, 0, [now()]);
        } elseif ($to === 'viewed') {
            $extra = ', viewed_at = COALESCE(viewed_at, ?)';
            array_splice($params, 2, 0, [now()]);
        } elseif (in_array($to, ['approved', 'refused'], true)) {
            $extra = ', responded_at = ?, response_ip = ?';
            array_splice($params, 2, 0, [now(), $ip]);
        }

        $this->db->execute("UPDATE quotes SET status = ?, updated_at = ?{$extra} WHERE id = ?", $params);
        $this->recordStatus($this->db, $quoteId, $from, $to, $actor, $ip, $note);
        return true;
    }

    protected function recordStatus(Database $db, int $quoteId, ?string $from, string $to, string $actor, ?string $ip = null, ?string $note = null): void
    {
        $db->insert(
            'INSERT INTO quote_status_history (quote_id, from_status, to_status, note, actor, ip_address, created_at)
             VALUES (?,?,?,?,?,?,?)',
            [$quoteId, $from, $to, $note, $actor, $ip, now()]
        );
    }

    public function findWithItems(int $quoteId): ?array
    {
        $quote = $this->db->selectOne('SELECT * FROM quotes WHERE id = ?', [$quoteId]);
        if (!$quote) {
            return null;
        }
        $quote['items'] = $this->db->select('SELECT * FROM quote_items WHERE quote_id = ? ORDER BY sort_order', [$quoteId]);
        return $quote;
    }

    public function findByToken(string $token): ?array
    {
        $quote = $this->db->selectOne('SELECT * FROM quotes WHERE public_token = ?', [$token]);
        if (!$quote) {
            return null;
        }
        $quote['items'] = $this->db->select('SELECT * FROM quote_items WHERE quote_id = ? ORDER BY sort_order', [$quote['id']]);
        return $quote;
    }
}
