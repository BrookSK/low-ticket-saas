<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Response;

/**
 * Dashboard administrativo com visao geral do negocio.
 */
class AdminDashboardController extends Controller
{
    protected Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function index(): Response
    {
        $stats = [
            'users' => $this->scalar('SELECT COUNT(*) c FROM users'),
            'users_paying' => $this->scalar('SELECT COUNT(DISTINCT user_id) c FROM orders WHERE status = ?', ['approved']),
            'orders' => $this->scalar('SELECT COUNT(*) c FROM orders'),
            'revenue_total' => $this->scalarFloat('SELECT COALESCE(SUM(total),0) c FROM orders WHERE status = ?', ['approved']),
            'revenue_month' => $this->scalarFloat(
                'SELECT COALESCE(SUM(total),0) c FROM orders WHERE status = ? AND paid_at >= ?',
                ['approved', date('Y-m-01 00:00:00')]
            ),
            'sales_today' => $this->scalar('SELECT COUNT(*) c FROM orders WHERE status = ? AND DATE(paid_at) = ?', ['approved', date('Y-m-d')]),
            'upsells' => $this->scalar('SELECT COUNT(*) c FROM orders WHERE is_upsell = 1 AND status = ?', ['approved']),
            'quotes' => $this->scalar('SELECT COUNT(*) c FROM quotes'),
        ];

        $recentOrders = $this->db->select(
            'SELECT o.*, u.name AS user_name FROM orders o
             LEFT JOIN users u ON u.id = o.user_id
             ORDER BY o.created_at DESC LIMIT 8'
        );

        return $this->view('admin.dashboard', [
            'title' => 'Dashboard',
            'stats' => $stats,
            'recentOrders' => $recentOrders,
        ]);
    }

    protected function scalar(string $sql, array $b = []): int
    {
        $row = $this->db->selectOne($sql, $b);
        return (int) ($row['c'] ?? 0);
    }

    protected function scalarFloat(string $sql, array $b = []): float
    {
        $row = $this->db->selectOne($sql, $b);
        return (float) ($row['c'] ?? 0);
    }
}
