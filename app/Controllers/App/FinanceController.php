<?php

namespace App\Controllers\App;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

/**
 * Kit Financeiro: receitas, despesas e dashboard com metricas e filtros.
 */
class FinanceController extends Controller
{
    protected Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    // -------- Dashboard --------

    public function dashboard(Request $request): Response
    {
        $userId = $this->auth()->id();
        [$start, $end] = $this->period($request);

        $revReceived = $this->sum('revenues', 'received', $userId, $start, $end);
        $revPending = $this->sum('revenues', 'pending', $userId, $start, $end);
        $expPaid = $this->sum('expenses', 'paid', $userId, $start, $end);
        $expPending = $this->sum('expenses', 'pending', $userId, $start, $end);

        // Contas vencidas / a vencer.
        $overdueRev = (float) ($this->db->selectOne(
            "SELECT COALESCE(SUM(amount),0) c FROM revenues WHERE user_id=? AND status='pending' AND due_date < CURDATE()", [$userId]
        )['c'] ?? 0);
        $upcomingRev = (float) ($this->db->selectOne(
            "SELECT COALESCE(SUM(amount),0) c FROM revenues WHERE user_id=? AND status='pending' AND due_date >= CURDATE()", [$userId]
        )['c'] ?? 0);

        $metrics = [
            'billing' => $revReceived + $revPending,
            'received' => $revReceived,
            'pending_rev' => $revPending,
            'expenses' => $expPaid,
            'pending_exp' => $expPending,
            'profit' => $revReceived - $expPaid,
            'balance' => ($revReceived + $revPending) - ($expPaid + $expPending),
            'overdue' => $overdueRev,
            'upcoming' => $upcomingRev,
        ];

        // Comparativo mensal (ultimos 6 meses).
        $monthly = $this->db->select(
            "SELECT DATE_FORMAT(date, '%Y-%m') ym,
                    COALESCE(SUM(CASE WHEN status='received' THEN amount ELSE 0 END),0) received
             FROM revenues WHERE user_id=? AND date >= ? GROUP BY ym ORDER BY ym",
            [$userId, date('Y-m-01', strtotime('-5 months'))]
        );
        $monthlyExp = $this->db->select(
            "SELECT DATE_FORMAT(date, '%Y-%m') ym,
                    COALESCE(SUM(CASE WHEN status='paid' THEN amount ELSE 0 END),0) paid
             FROM expenses WHERE user_id=? AND date >= ? GROUP BY ym ORDER BY ym",
            [$userId, date('Y-m-01', strtotime('-5 months'))]
        );

        return $this->view('app.finance.dashboard', [
            'title' => 'Dashboard financeiro',
            'metrics' => $metrics,
            'monthly' => $monthly,
            'monthlyExp' => $monthlyExp,
            'start' => substr($start, 0, 10),
            'end' => substr($end, 0, 10),
        ]);
    }

    // -------- Receitas --------

    public function revenues(Request $request): Response
    {
        $userId = $this->auth()->id();
        $this->refreshOverdue($userId);
        $revenues = $this->db->select(
            'SELECT r.*, c.name AS customer_name FROM revenues r LEFT JOIN customers c ON c.id=r.customer_id
             WHERE r.user_id = ? ORDER BY r.date DESC, r.id DESC',
            [$userId]
        );
        return $this->view('app.finance.revenues', [
            'title' => 'Receitas',
            'revenues' => $revenues,
            'customers' => $this->customers(),
            'categories' => $this->categories('revenue'),
        ]);
    }

    public function storeRevenue(Request $request): Response
    {
        $data = $this->revenuePayload($request);
        $errors = $this->validate($data, ['description' => 'required|max:191', 'amount' => 'required|numeric']);
        if (!empty($errors)) {
            $this->withErrors($errors, $data);
            return $this->redirect('/receitas');
        }
        $this->db->insert(
            'INSERT INTO revenues (user_id, customer_id, category_id, description, amount, date, due_date, received_at, payment_method, status, created_at, updated_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
            [
                $this->auth()->id(), $data['customer_id'], $data['category_id'], $data['description'], $data['amount'],
                $data['date'], $data['due_date'], $data['received_at'], $data['payment_method'], $data['status'], now(), now(),
            ]
        );
        $this->withFlash('success', 'Receita registrada.');
        return $this->redirect('/receitas');
    }

    public function updateRevenue(Request $request): Response
    {
        $id = (int) $request->param('id');
        $this->ownedRow('revenues', $id);
        $data = $this->revenuePayload($request);
        $this->db->execute(
            'UPDATE revenues SET customer_id=?, category_id=?, description=?, amount=?, date=?, due_date=?, received_at=?, payment_method=?, status=?, updated_at=? WHERE id=?',
            [$data['customer_id'], $data['category_id'], $data['description'], $data['amount'], $data['date'], $data['due_date'], $data['received_at'], $data['payment_method'], $data['status'], now(), $id]
        );
        $this->withFlash('success', 'Receita atualizada.');
        return $this->redirect('/receitas');
    }

    public function destroyRevenue(Request $request): Response
    {
        $id = (int) $request->param('id');
        $this->ownedRow('revenues', $id);
        $this->db->execute('DELETE FROM revenues WHERE id = ?', [$id]);
        $this->withFlash('success', 'Receita removida.');
        return $this->redirect('/receitas');
    }

    // -------- Despesas --------

    public function expenses(Request $request): Response
    {
        $userId = $this->auth()->id();
        $this->refreshOverdue($userId);
        $expenses = $this->db->select('SELECT * FROM expenses WHERE user_id = ? ORDER BY date DESC, id DESC', [$userId]);
        return $this->view('app.finance.expenses', [
            'title' => 'Despesas',
            'expenses' => $expenses,
            'categories' => $this->categories('expense'),
        ]);
    }

    public function storeExpense(Request $request): Response
    {
        $data = $this->expensePayload($request);
        $errors = $this->validate($data, ['description' => 'required|max:191', 'amount' => 'required|numeric']);
        if (!empty($errors)) {
            $this->withErrors($errors, $data);
            return $this->redirect('/despesas');
        }
        $this->db->insert(
            'INSERT INTO expenses (user_id, supplier, category_id, description, amount, date, due_date, paid_at, payment_method, status, created_at, updated_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
            [
                $this->auth()->id(), $data['supplier'], $data['category_id'], $data['description'], $data['amount'],
                $data['date'], $data['due_date'], $data['paid_at'], $data['payment_method'], $data['status'], now(), now(),
            ]
        );
        $this->withFlash('success', 'Despesa registrada.');
        return $this->redirect('/despesas');
    }

    public function updateExpense(Request $request): Response
    {
        $id = (int) $request->param('id');
        $this->ownedRow('expenses', $id);
        $data = $this->expensePayload($request);
        $this->db->execute(
            'UPDATE expenses SET supplier=?, category_id=?, description=?, amount=?, date=?, due_date=?, paid_at=?, payment_method=?, status=?, updated_at=? WHERE id=?',
            [$data['supplier'], $data['category_id'], $data['description'], $data['amount'], $data['date'], $data['due_date'], $data['paid_at'], $data['payment_method'], $data['status'], now(), $id]
        );
        $this->withFlash('success', 'Despesa atualizada.');
        return $this->redirect('/despesas');
    }

    public function destroyExpense(Request $request): Response
    {
        $id = (int) $request->param('id');
        $this->ownedRow('expenses', $id);
        $this->db->execute('DELETE FROM expenses WHERE id = ?', [$id]);
        $this->withFlash('success', 'Despesa removida.');
        return $this->redirect('/despesas');
    }

    // -------- Helpers --------

    protected function sum(string $table, string $status, int $userId, string $start, string $end): float
    {
        $row = $this->db->selectOne(
            "SELECT COALESCE(SUM(amount),0) c FROM {$table} WHERE user_id = ? AND status = ? AND date BETWEEN ? AND ?",
            [$userId, $status, substr($start, 0, 10), substr($end, 0, 10)]
        );
        return (float) ($row['c'] ?? 0);
    }

    /** Marca como vencidas as contas pendentes com vencimento passado. */
    protected function refreshOverdue(int $userId): void
    {
        $this->db->execute("UPDATE revenues SET status='overdue' WHERE user_id=? AND status='pending' AND due_date IS NOT NULL AND due_date < CURDATE()", [$userId]);
        $this->db->execute("UPDATE expenses SET status='overdue' WHERE user_id=? AND status='pending' AND due_date IS NOT NULL AND due_date < CURDATE()", [$userId]);
    }

    protected function ownedRow(string $table, int $id): void
    {
        $row = $this->db->selectOne("SELECT id FROM {$table} WHERE id = ? AND user_id = ?", [$id, $this->auth()->id()]);
        if (!$row) {
            $this->abort(404);
        }
    }

    protected function customers(): array
    {
        return $this->db->select('SELECT id, name FROM customers WHERE user_id = ? ORDER BY name', [$this->auth()->id()]);
    }

    protected function categories(string $type): array
    {
        return $this->db->select('SELECT id, name FROM categories WHERE (user_id = ? OR user_id IS NULL) AND type = ? ORDER BY name', [$this->auth()->id(), $type]);
    }

    protected function period(Request $request): array
    {
        $start = $request->query('start') ? $request->query('start') : date('Y-m-01');
        $end = $request->query('end') ? $request->query('end') : date('Y-m-t');
        return [$start . ' 00:00:00', $end . ' 23:59:59'];
    }

    protected function revenuePayload(Request $request): array
    {
        return [
            'customer_id' => (int) $request->input('customer_id') ?: null,
            'category_id' => (int) $request->input('category_id') ?: null,
            'description' => trim((string) $request->input('description')),
            'amount' => (float) str_replace(',', '.', (string) $request->input('amount', '0')),
            'date' => $request->input('date') ?: date('Y-m-d'),
            'due_date' => $request->input('due_date') ?: null,
            'received_at' => $request->input('received_at') ?: null,
            'payment_method' => trim((string) $request->input('payment_method')) ?: null,
            'status' => in_array($request->input('status'), ['pending', 'received', 'overdue', 'canceled'], true) ? $request->input('status') : 'pending',
        ];
    }

    protected function expensePayload(Request $request): array
    {
        return [
            'supplier' => trim((string) $request->input('supplier')) ?: null,
            'category_id' => (int) $request->input('category_id') ?: null,
            'description' => trim((string) $request->input('description')),
            'amount' => (float) str_replace(',', '.', (string) $request->input('amount', '0')),
            'date' => $request->input('date') ?: date('Y-m-d'),
            'due_date' => $request->input('due_date') ?: null,
            'paid_at' => $request->input('paid_at') ?: null,
            'payment_method' => trim((string) $request->input('payment_method')) ?: null,
            'status' => in_array($request->input('status'), ['pending', 'paid', 'overdue', 'canceled'], true) ? $request->input('status') : 'pending',
        ];
    }
}
