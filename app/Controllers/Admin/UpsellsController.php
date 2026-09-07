<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

/**
 * Gestao de regras de upsell configuraveis.
 */
class UpsellsController extends Controller
{
    protected Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function index(): Response
    {
        $upsells = $this->db->select(
            'SELECT u.*, tp.name AS trigger_name, op.name AS offer_name
             FROM upsells u
             JOIN products tp ON tp.id = u.trigger_product_id
             JOIN products op ON op.id = u.offer_product_id
             ORDER BY u.sort_order, u.id'
        );
        $products = $this->db->select('SELECT id, name FROM products WHERE is_active = 1 ORDER BY sort_order');
        return $this->view('admin.upsells', ['title' => 'Upsells', 'upsells' => $upsells, 'products' => $products]);
    }

    public function store(Request $request): Response
    {
        $this->db->insert(
            'INSERT INTO upsells (trigger_product_id, offer_product_id, title, description, price, discount, sort_order, is_active, created_at, updated_at)
             VALUES (?,?,?,?,?,?,?,?,?,?)',
            [
                (int) $request->input('trigger_product_id'),
                (int) $request->input('offer_product_id'),
                trim((string) $request->input('title')),
                (string) $request->input('description'),
                $request->input('price') ? (float) str_replace(',', '.', (string) $request->input('price')) : null,
                $request->input('discount') ? (float) str_replace(',', '.', (string) $request->input('discount')) : null,
                (int) $request->input('sort_order', 0),
                $request->input('is_active') ? 1 : 0,
                now(), now(),
            ]
        );
        $this->withFlash('success', 'Upsell criado.');
        return $this->redirect('/admin/upsells');
    }

    public function update(Request $request): Response
    {
        $id = (int) $request->param('id');
        $this->db->execute(
            'UPDATE upsells SET title=?, description=?, price=?, discount=?, sort_order=?, is_active=?, updated_at=? WHERE id=?',
            [
                trim((string) $request->input('title')),
                (string) $request->input('description'),
                $request->input('price') ? (float) str_replace(',', '.', (string) $request->input('price')) : null,
                $request->input('discount') ? (float) str_replace(',', '.', (string) $request->input('discount')) : null,
                (int) $request->input('sort_order', 0),
                $request->input('is_active') ? 1 : 0,
                now(), $id,
            ]
        );
        $this->withFlash('success', 'Upsell atualizado.');
        return $this->redirect('/admin/upsells');
    }

    public function destroy(Request $request): Response
    {
        $this->db->execute('DELETE FROM upsells WHERE id = ?', [$request->param('id')]);
        $this->withFlash('success', 'Upsell removido.');
        return $this->redirect('/admin/upsells');
    }
}
