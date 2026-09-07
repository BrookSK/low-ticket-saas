<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Services\LogService;

/**
 * Visualizacao de pedidos e pagamentos.
 */
class OrdersController extends Controller
{
    protected Database $db;
    protected LogService $log;

    public function __construct(Database $db, LogService $log)
    {
        $this->db = $db;
        $this->log = $log;
    }

    public function index(Request $request): Response
    {
        $status = (string) $request->query('status', '');
        $page = max(1, (int) $request->query('page', 1));
        $perPage = 25;
        $offset = ($page - 1) * $perPage;

        $where = '1=1';
        $bindings = [];
        if ($status !== '') {
            $where .= ' AND o.status = ?';
            $bindings[] = $status;
        }

        $orders = $this->db->select(
            "SELECT o.*, u.name AS user_name FROM orders o
             LEFT JOIN users u ON u.id = o.user_id
             WHERE {$where} ORDER BY o.created_at DESC LIMIT ? OFFSET ?",
            array_merge($bindings, [$perPage, $offset])
        );
        $total = (int) ($this->db->selectOne("SELECT COUNT(*) c FROM orders o WHERE {$where}", $bindings)['c'] ?? 0);

        return $this->view('admin.orders.index', [
            'title' => 'Pedidos',
            'orders' => $orders,
            'status' => $status,
            'page' => $page,
            'pages' => (int) ceil($total / $perPage),
        ]);
    }

    public function show(Request $request): Response
    {
        $id = (int) $request->param('id');
        $order = $this->db->selectOne('SELECT o.*, u.name AS user_name, u.email AS user_email FROM orders o LEFT JOIN users u ON u.id=o.user_id WHERE o.id = ?', [$id]);
        if (!$order) {
            $this->abort(404);
        }
        $items = $this->db->select('SELECT * FROM order_items WHERE order_id = ?', [$id]);
        $payments = $this->db->select('SELECT * FROM payments WHERE order_id = ? ORDER BY id DESC', [$id]);
        $webhooks = $this->db->select('SELECT * FROM payment_webhooks WHERE order_id = ? ORDER BY id DESC', [$id]);

        return $this->view('admin.orders.show', [
            'title' => 'Pedido ' . $order['reference'],
            'order' => $order,
            'items' => $items,
            'payments' => $payments,
            'webhooks' => $webhooks,
        ]);
    }

    public function refund(Request $request): Response
    {
        $id = (int) $request->param('id');
        // Marca como estornado (o estorno real no gateway depende de integracao configurada).
        $this->db->execute('UPDATE orders SET status = ?, updated_at = ? WHERE id = ?', ['refunded', now(), $id]);
        $this->log->admin('order_refunded', $this->auth()->id(), "Pedido #{$id} marcado como estornado", [], 'warning');
        $this->withFlash('success', 'Pedido marcado como estornado. Realize o estorno no gateway se necessario.');
        return $this->redirect('/admin/pedidos/' . $id);
    }
}
