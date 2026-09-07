<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

/**
 * Dashboard de vendas com metricas e filtros de periodo.
 */
class SalesController extends Controller
{
    protected Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function index(Request $request): Response
    {
        $range = (string) $request->query('range', '30d');
        [$start, $end] = $this->resolveRange($range);

        $metrics = $this->metrics($start, $end);
        $byProduct = $this->db->select(
            'SELECT oi.name, COUNT(*) qty, COALESCE(SUM(oi.price*oi.quantity),0) revenue
             FROM order_items oi JOIN orders o ON o.id = oi.order_id
             WHERE o.status = ? AND o.paid_at BETWEEN ? AND ?
             GROUP BY oi.name ORDER BY revenue DESC',
            ['approved', $start, $end]
        );
        $bySource = $this->db->select(
            'SELECT COALESCE(ma.first_source, ?) source, COUNT(*) qty, COALESCE(SUM(o.total),0) revenue
             FROM orders o LEFT JOIN marketing_attribution ma ON ma.id = o.attribution_id
             WHERE o.status = ? AND o.paid_at BETWEEN ? AND ?
             GROUP BY source ORDER BY revenue DESC',
            ['direct', 'approved', $start, $end]
        );

        return $this->view('admin.sales', [
            'title' => 'Vendas',
            'range' => $range,
            'metrics' => $metrics,
            'byProduct' => $byProduct,
            'bySource' => $bySource,
        ]);
    }

    public function data(Request $request): Response
    {
        // Serie diaria para os graficos (ultimos 30 dias por padrao).
        $range = (string) $request->query('range', '30d');
        [$start, $end] = $this->resolveRange($range);
        $rows = $this->db->select(
            'SELECT DATE(paid_at) d, COALESCE(SUM(total),0) revenue, COUNT(*) sales
             FROM orders WHERE status = ? AND paid_at BETWEEN ? AND ?
             GROUP BY DATE(paid_at) ORDER BY d',
            ['approved', $start, $end]
        );
        return $this->json(['series' => $rows]);
    }

    protected function metrics(string $start, string $end): array
    {
        $approved = $this->db->selectOne(
            'SELECT COUNT(*) sales, COALESCE(SUM(total),0) revenue, COALESCE(AVG(total),0) aov
             FROM orders WHERE status = ? AND paid_at BETWEEN ? AND ?',
            ['approved', $start, $end]
        );
        $upsells = $this->db->selectOne(
            'SELECT COUNT(*) qty, COALESCE(SUM(total),0) revenue
             FROM orders WHERE status = ? AND is_upsell = 1 AND paid_at BETWEEN ? AND ?',
            ['approved', $start, $end]
        );
        $customers = (int) ($this->db->selectOne(
            'SELECT COUNT(DISTINCT user_id) c FROM orders WHERE status = ? AND paid_at BETWEEN ? AND ?',
            ['approved', $start, $end]
        )['c'] ?? 0);
        $checkouts = (int) ($this->db->selectOne(
            'SELECT COUNT(*) c FROM checkout_abandonment WHERE created_at BETWEEN ? AND ?',
            [$start, $end]
        )['c'] ?? 0);

        $sales = (int) ($approved['sales'] ?? 0);
        $conversion = $checkouts > 0 ? round($sales / $checkouts * 100, 1) : 0.0;

        return [
            'sales' => $sales,
            'revenue' => (float) ($approved['revenue'] ?? 0),
            'aov' => (float) ($approved['aov'] ?? 0),
            'upsells' => (int) ($upsells['qty'] ?? 0),
            'upsell_revenue' => (float) ($upsells['revenue'] ?? 0),
            'customers' => $customers,
            'conversion' => $conversion,
        ];
    }

    protected function resolveRange(string $range): array
    {
        $end = date('Y-m-d 23:59:59');
        $start = match ($range) {
            'today' => date('Y-m-d 00:00:00'),
            'yesterday' => date('Y-m-d 00:00:00', strtotime('-1 day')),
            '7d' => date('Y-m-d 00:00:00', strtotime('-7 days')),
            '30d' => date('Y-m-d 00:00:00', strtotime('-30 days')),
            'this_month' => date('Y-m-01 00:00:00'),
            'last_month' => date('Y-m-01 00:00:00', strtotime('first day of last month')),
            default => date('Y-m-d 00:00:00', strtotime('-30 days')),
        };
        if ($range === 'yesterday') {
            $end = date('Y-m-d 23:59:59', strtotime('-1 day'));
        }
        if ($range === 'last_month') {
            $end = date('Y-m-t 23:59:59', strtotime('last day of last month'));
        }
        return [$start, $end];
    }
}
