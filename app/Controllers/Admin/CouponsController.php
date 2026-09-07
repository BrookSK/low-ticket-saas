<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Services\LogService;

/**
 * Gestao de cupons de desconto.
 */
class CouponsController extends Controller
{
    protected Database $db;
    protected LogService $log;

    public function __construct(Database $db, LogService $log)
    {
        $this->db = $db;
        $this->log = $log;
    }

    public function index(): Response
    {
        $coupons = $this->db->select('SELECT * FROM coupons ORDER BY id DESC');
        return $this->view('admin.coupons', ['title' => 'Cupons', 'coupons' => $coupons]);
    }

    public function store(Request $request): Response
    {
        $code = strtoupper(trim((string) $request->input('code')));
        $errors = $this->validate(['code' => $code], ['code' => 'required|max:64|unique:coupons,code']);
        if (!empty($errors)) {
            $this->withErrors($errors, $request->all());
            return $this->redirect('/admin/cupons');
        }
        $type = (string) $request->input('type', 'percent');
        $this->db->insert(
            'INSERT INTO coupons (code, type, percent, amount, valid_from, valid_until, max_uses, applicable_products, is_active, created_at, updated_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?)',
            [
                $code, $type,
                $type === 'percent' ? (float) str_replace(',', '.', (string) $request->input('percent')) : null,
                $type === 'fixed' ? (float) str_replace(',', '.', (string) $request->input('amount')) : null,
                $request->input('valid_from') ?: null,
                $request->input('valid_until') ?: null,
                $request->input('max_uses') ? (int) $request->input('max_uses') : null,
                trim((string) $request->input('applicable_products')) ?: null,
                $request->input('is_active') ? 1 : 0,
                now(), now(),
            ]
        );
        $this->log->admin('coupon_created', $this->auth()->id(), "Cupom criado: {$code}");
        $this->withFlash('success', 'Cupom criado.');
        return $this->redirect('/admin/cupons');
    }

    public function update(Request $request): Response
    {
        $id = (int) $request->param('id');
        $type = (string) $request->input('type', 'percent');
        $this->db->execute(
            'UPDATE coupons SET type=?, percent=?, amount=?, valid_from=?, valid_until=?, max_uses=?, applicable_products=?, is_active=?, updated_at=? WHERE id=?',
            [
                $type,
                $type === 'percent' ? (float) str_replace(',', '.', (string) $request->input('percent')) : null,
                $type === 'fixed' ? (float) str_replace(',', '.', (string) $request->input('amount')) : null,
                $request->input('valid_from') ?: null,
                $request->input('valid_until') ?: null,
                $request->input('max_uses') ? (int) $request->input('max_uses') : null,
                trim((string) $request->input('applicable_products')) ?: null,
                $request->input('is_active') ? 1 : 0,
                now(), $id,
            ]
        );
        $this->withFlash('success', 'Cupom atualizado.');
        return $this->redirect('/admin/cupons');
    }

    public function destroy(Request $request): Response
    {
        $this->db->execute('DELETE FROM coupons WHERE id = ?', [$request->param('id')]);
        $this->withFlash('success', 'Cupom removido.');
        return $this->redirect('/admin/cupons');
    }
}
