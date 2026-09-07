<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

/**
 * Relatorios administrativos (vendas, financeiro, marketing, usuarios)
 * com exportacao CSV e PDF.
 */
class ReportsController extends Controller
{
    protected Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function index(): Response
    {
        return $this->view('admin.reports.index', ['title' => 'Relatorios']);
    }

    public function sales(Request $request): Response
    {
        $rows = $this->salesData($this->range($request));
        return $this->view('admin.reports.table', [
            'title' => 'Relatorio de Vendas',
            'type' => 'sales',
            'headers' => ['Referencia', 'Cliente', 'Produto(s)', 'Total', 'Gateway', 'Status', 'Data'],
            'rows' => $rows,
        ]);
    }

    public function finance(Request $request): Response
    {
        $rows = $this->financeData();
        return $this->view('admin.reports.table', [
            'title' => 'Relatorio Financeiro (plataforma)',
            'type' => 'finance',
            'headers' => ['Mes', 'Faturamento', 'Pedidos aprovados'],
            'rows' => $rows,
        ]);
    }

    public function marketing(Request $request): Response
    {
        $rows = $this->marketingData();
        return $this->view('admin.reports.table', [
            'title' => 'Relatorio de Marketing',
            'type' => 'marketing',
            'headers' => ['Origem', 'Meio', 'Campanha', 'Visitantes', 'Vendas', 'Receita'],
            'rows' => $rows,
        ]);
    }

    public function users(Request $request): Response
    {
        $rows = $this->usersData();
        return $this->view('admin.reports.table', [
            'title' => 'Relatorio de Usuarios',
            'type' => 'users',
            'headers' => ['Nome', 'E-mail', 'Status', 'Pagante', 'Cadastro'],
            'rows' => $rows,
        ]);
    }

    public function export(Request $request): Response
    {
        $type = (string) $request->param('type');
        $format = (string) $request->param('format');

        $rows = match ($type) {
            'sales' => $this->salesData($this->range($request)),
            'finance' => $this->financeData(),
            'marketing' => $this->marketingData(),
            'users' => $this->usersData(),
            default => [],
        };
        $headers = match ($type) {
            'sales' => ['Referencia', 'Cliente', 'Produtos', 'Total', 'Gateway', 'Status', 'Data'],
            'finance' => ['Mes', 'Faturamento', 'Pedidos'],
            'marketing' => ['Origem', 'Meio', 'Campanha', 'Visitantes', 'Vendas', 'Receita'],
            'users' => ['Nome', 'E-mail', 'Status', 'Pagante', 'Cadastro'],
            default => [],
        };

        if ($format === 'csv') {
            return $this->csv($type, $headers, $rows);
        }
        // PDF via servico de PDF (HTML -> PDF).
        return app(\App\Services\PdfService::class)->fromView('admin.reports.pdf', [
            'title' => 'Relatorio ' . ucfirst($type),
            'headers' => $headers,
            'rows' => $rows,
        ], "relatorio-{$type}.pdf");
    }

    protected function csv(string $type, array $headers, array $rows): Response
    {
        $fh = fopen('php://temp', 'r+');
        fputcsv($fh, $headers);
        foreach ($rows as $row) {
            fputcsv($fh, array_values($row));
        }
        rewind($fh);
        $content = stream_get_contents($fh);
        fclose($fh);

        return Response::make("\xEF\xBB\xBF" . $content, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="relatorio-' . $type . '-' . date('Y-m-d') . '.csv"',
        ]);
    }

    // ---- Consultas ----

    protected function salesData(array $range): array
    {
        $orders = $this->db->select(
            'SELECT o.reference, COALESCE(u.name, o.email) client, o.total, o.gateway, o.status, o.created_at,
                    (SELECT GROUP_CONCAT(name SEPARATOR ", ") FROM order_items WHERE order_id = o.id) products
             FROM orders o LEFT JOIN users u ON u.id = o.user_id
             WHERE o.created_at BETWEEN ? AND ? ORDER BY o.created_at DESC',
            [$range[0], $range[1]]
        );
        return array_map(fn($o) => [
            'ref' => $o['reference'],
            'client' => $o['client'] ?? '-',
            'products' => $o['products'] ?? '-',
            'total' => number_format((float) $o['total'], 2, ',', '.'),
            'gateway' => $o['gateway'] ?? '-',
            'status' => $o['status'],
            'date' => date('d/m/Y H:i', strtotime($o['created_at'])),
        ], $orders);
    }

    protected function financeData(): array
    {
        $rows = $this->db->select(
            "SELECT DATE_FORMAT(paid_at, '%Y-%m') ym, COALESCE(SUM(total),0) revenue, COUNT(*) orders
             FROM orders WHERE status = 'approved' AND paid_at IS NOT NULL
             GROUP BY ym ORDER BY ym DESC"
        );
        return array_map(fn($r) => [
            'month' => $r['ym'],
            'revenue' => number_format((float) $r['revenue'], 2, ',', '.'),
            'orders' => $r['orders'],
        ], $rows);
    }

    protected function marketingData(): array
    {
        $rows = $this->db->select(
            "SELECT COALESCE(ma.first_source,'direct') source, COALESCE(ma.first_medium,'none') medium,
                    COALESCE(ma.first_campaign,'-') campaign, COUNT(DISTINCT ma.id) visitors,
                    COUNT(DISTINCT CASE WHEN o.status='approved' THEN o.id END) sales,
                    COALESCE(SUM(CASE WHEN o.status='approved' THEN o.total ELSE 0 END),0) revenue
             FROM marketing_attribution ma
             LEFT JOIN orders o ON o.attribution_id = ma.id
             GROUP BY source, medium, campaign ORDER BY revenue DESC LIMIT 200"
        );
        return array_map(fn($r) => [
            'source' => $r['source'],
            'medium' => $r['medium'],
            'campaign' => $r['campaign'],
            'visitors' => $r['visitors'],
            'sales' => $r['sales'],
            'revenue' => number_format((float) $r['revenue'], 2, ',', '.'),
        ], $rows);
    }

    protected function usersData(): array
    {
        $rows = $this->db->select(
            "SELECT u.name, u.email, u.status, u.created_at,
                    (SELECT COUNT(*) FROM orders WHERE user_id = u.id AND status='approved') paid
             FROM users u ORDER BY u.created_at DESC LIMIT 1000"
        );
        return array_map(fn($r) => [
            'name' => $r['name'],
            'email' => $r['email'],
            'status' => $r['status'],
            'paying' => $r['paid'] > 0 ? 'Sim' : 'Nao',
            'created' => date('d/m/Y', strtotime($r['created_at'])),
        ], $rows);
    }

    protected function range(Request $request): array
    {
        $start = $request->query('start') ? $request->query('start') . ' 00:00:00' : date('Y-m-01 00:00:00');
        $end = $request->query('end') ? $request->query('end') . ' 23:59:59' : date('Y-m-d 23:59:59');
        return [$start, $end];
    }
}
