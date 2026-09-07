<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

/**
 * Visualizacao de logs administrativos com filtros.
 */
class LogsController extends Controller
{
    protected Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function index(Request $request): Response
    {
        $severity = (string) $request->query('severity', '');
        $action = (string) $request->query('action', '');
        $page = max(1, (int) $request->query('page', 1));
        $perPage = 40;
        $offset = ($page - 1) * $perPage;

        $where = '1=1';
        $bindings = [];
        if ($severity !== '') {
            $where .= ' AND l.severity = ?';
            $bindings[] = $severity;
        }
        if ($action !== '') {
            $where .= ' AND l.action LIKE ?';
            $bindings[] = '%' . $action . '%';
        }

        $logs = $this->db->select(
            "SELECT l.*, u.name AS user_name FROM admin_logs l
             LEFT JOIN users u ON u.id = l.user_id
             WHERE {$where} ORDER BY l.created_at DESC LIMIT ? OFFSET ?",
            array_merge($bindings, [$perPage, $offset])
        );
        $total = (int) ($this->db->selectOne("SELECT COUNT(*) c FROM admin_logs l WHERE {$where}", $bindings)['c'] ?? 0);

        return $this->view('admin.logs', [
            'title' => 'Logs',
            'logs' => $logs,
            'severity' => $severity,
            'action' => $action,
            'page' => $page,
            'pages' => (int) ceil($total / $perPage),
        ]);
    }
}
