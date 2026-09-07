<?php

namespace App\Controllers\App;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Response;
use App\Models\User;

/**
 * Dashboard do usuario com metricas, checklist de primeiro acesso e atalhos.
 */
class DashboardController extends Controller
{
    protected Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function index(): Response
    {
        $user = $this->user();
        $userId = (int) $user['id'];
        $modules = $this->auth()->isAdmin()
            ? ['orcamentos', 'financeiro', 'precificador', 'clientes', 'servicos', 'relatorios']
            : User::modules($userId);
        $has = fn($m) => in_array($m, $modules, true);

        $stats = [
            'quotes' => $this->count('SELECT COUNT(*) c FROM quotes WHERE user_id = ?', [$userId]),
            'quotes_approved' => $this->count('SELECT COUNT(*) c FROM quotes WHERE user_id = ? AND status = ?', [$userId, 'approved']),
            'quotes_pending' => $this->count('SELECT COUNT(*) c FROM quotes WHERE user_id = ? AND status IN (?,?)', [$userId, 'sent', 'viewed']),
            'customers' => $this->count('SELECT COUNT(*) c FROM customers WHERE user_id = ?', [$userId]),
            'revenue' => $this->sum('SELECT COALESCE(SUM(amount),0) c FROM revenues WHERE user_id = ? AND status = ?', [$userId, 'received']),
            'expenses' => $this->sum('SELECT COALESCE(SUM(amount),0) c FROM expenses WHERE user_id = ? AND status = ?', [$userId, 'paid']),
        ];
        $stats['profit'] = $stats['revenue'] - $stats['expenses'];

        // Checklist de onboarding (primeiro acesso).
        $checklist = [
            'profile' => !empty($user['phone']),
            'customer' => $stats['customers'] > 0,
            'quote' => $stats['quotes'] > 0,
            'pricing' => $this->count('SELECT COUNT(*) c FROM pricing_calculations WHERE user_id = ?', [$userId]) > 0,
            'revenue' => $this->count('SELECT COUNT(*) c FROM revenues WHERE user_id = ?', [$userId]) > 0,
        ];
        $done = count(array_filter($checklist));
        $progress = (int) round($done / count($checklist) * 100);

        $recentQuotes = $has('orcamentos')
            ? $this->db->select(
                'SELECT q.*, c.name AS customer_name FROM quotes q LEFT JOIN customers c ON c.id = q.customer_id
                 WHERE q.user_id = ? ORDER BY q.created_at DESC LIMIT 5',
                [$userId]
            )
            : [];

        return $this->view('app.dashboard', [
            'title' => 'Dashboard',
            'user' => $user,
            'stats' => $stats,
            'checklist' => $checklist,
            'progress' => $progress,
            'has' => $has,
            'recentQuotes' => $recentQuotes,
        ]);
    }

    protected function count(string $sql, array $b): int
    {
        return (int) ($this->db->selectOne($sql, $b)['c'] ?? 0);
    }

    protected function sum(string $sql, array $b): float
    {
        return (float) ($this->db->selectOne($sql, $b)['c'] ?? 0);
    }
}
