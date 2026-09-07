<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Models\User;
use App\Services\LogService;

/**
 * Gestao de usuarios: listar, ver, bloquear/desbloquear, conceder acesso.
 */
class UsersController extends Controller
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
        $q = trim((string) $request->query('q', ''));
        $page = max(1, (int) $request->query('page', 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        if ($q !== '') {
            $like = '%' . $q . '%';
            $users = $this->db->select(
                'SELECT * FROM users WHERE name LIKE ? OR email LIKE ? ORDER BY id DESC LIMIT ? OFFSET ?',
                [$like, $like, $perPage, $offset]
            );
            $total = (int) ($this->db->selectOne('SELECT COUNT(*) c FROM users WHERE name LIKE ? OR email LIKE ?', [$like, $like])['c'] ?? 0);
        } else {
            $users = $this->db->select('SELECT * FROM users ORDER BY id DESC LIMIT ? OFFSET ?', [$perPage, $offset]);
            $total = (int) ($this->db->selectOne('SELECT COUNT(*) c FROM users')['c'] ?? 0);
        }

        return $this->view('admin.users.index', [
            'title' => 'Usuarios',
            'users' => $users,
            'q' => $q,
            'page' => $page,
            'pages' => (int) ceil($total / $perPage),
            'total' => $total,
        ]);
    }

    public function show(Request $request): Response
    {
        $id = (int) $request->param('id');
        $user = User::find($id);
        if (!$user) {
            $this->abort(404);
        }
        $orders = $this->db->select('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC', [$id]);
        $access = $this->db->select('SELECT * FROM user_product_access WHERE user_id = ?', [$id]);
        $roles = User::roles($id);

        return $this->view('admin.users.show', [
            'title' => 'Usuario',
            'user' => $user,
            'orders' => $orders,
            'access' => $access,
            'roles' => $roles,
        ]);
    }

    public function block(Request $request): Response
    {
        $id = (int) $request->param('id');
        User::update($id, ['status' => 'blocked']);
        $this->log->admin('user_blocked', $this->auth()->id(), "Usuario #{$id} bloqueado", [], 'warning');
        $this->withFlash('success', 'Usuario bloqueado.');
        return $this->redirect('/admin/usuarios/' . $id);
    }

    public function unblock(Request $request): Response
    {
        $id = (int) $request->param('id');
        User::update($id, ['status' => 'active']);
        $this->log->admin('user_unblocked', $this->auth()->id(), "Usuario #{$id} desbloqueado");
        $this->withFlash('success', 'Usuario desbloqueado.');
        return $this->redirect('/admin/usuarios/' . $id);
    }

    public function grantAccess(Request $request): Response
    {
        $id = (int) $request->param('id');
        $module = trim((string) $request->input('module'));
        if ($module !== '') {
            User::grantModule($id, $module, null, 'admin');
            $this->log->admin('access_granted', $this->auth()->id(), "Acesso '{$module}' concedido ao usuario #{$id}");
            $this->withFlash('success', "Acesso ao modulo '{$module}' concedido.");
        }
        return $this->redirect('/admin/usuarios/' . $id);
    }
}
