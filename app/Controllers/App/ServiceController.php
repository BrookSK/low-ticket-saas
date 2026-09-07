<?php

namespace App\Controllers\App;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

/**
 * CRUD de servicos e produtos reutilizaveis nos orcamentos.
 */
class ServiceController extends Controller
{
    protected Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function index(): Response
    {
        $userId = $this->auth()->id();
        $services = $this->db->select('SELECT * FROM services WHERE user_id = ? ORDER BY name', [$userId]);
        return $this->view('app.services.index', ['title' => 'Servicos e Produtos', 'services' => $services]);
    }

    public function create(): Response
    {
        return $this->view('app.services.form', ['title' => 'Novo item', 'service' => null]);
    }

    public function store(Request $request): Response
    {
        $data = $this->payload($request);
        $errors = $this->validate($data, ['name' => 'required|max:191']);
        if (!empty($errors)) {
            $this->withErrors($errors, $data);
            return $this->redirect('/servicos/novo');
        }
        $this->db->insert(
            'INSERT INTO services (user_id, name, description, kind, cost, suggested_price, unit, is_active, created_at, updated_at)
             VALUES (?,?,?,?,?,?,?,?,?,?)',
            [
                $this->auth()->id(), $data['name'], $data['description'], $data['kind'],
                $data['cost'], $data['suggested_price'], $data['unit'], $data['is_active'], now(), now(),
            ]
        );
        $this->withFlash('success', 'Item cadastrado.');
        return $this->redirect('/servicos');
    }

    public function edit(Request $request): Response
    {
        $service = $this->owned((int) $request->param('id'));
        return $this->view('app.services.form', ['title' => 'Editar item', 'service' => $service]);
    }

    public function update(Request $request): Response
    {
        $id = (int) $request->param('id');
        $this->owned($id);
        $data = $this->payload($request);
        $errors = $this->validate($data, ['name' => 'required|max:191']);
        if (!empty($errors)) {
            $this->withErrors($errors, $data);
            return $this->redirect('/servicos/' . $id . '/editar');
        }
        $this->db->execute(
            'UPDATE services SET name=?, description=?, kind=?, cost=?, suggested_price=?, unit=?, is_active=?, updated_at=? WHERE id=?',
            [$data['name'], $data['description'], $data['kind'], $data['cost'], $data['suggested_price'], $data['unit'], $data['is_active'], now(), $id]
        );
        $this->withFlash('success', 'Item atualizado.');
        return $this->redirect('/servicos');
    }

    public function destroy(Request $request): Response
    {
        $id = (int) $request->param('id');
        $this->owned($id);
        $this->db->execute('DELETE FROM services WHERE id = ?', [$id]);
        $this->withFlash('success', 'Item removido.');
        return $this->redirect('/servicos');
    }

    protected function owned(int $id): array
    {
        $service = $this->db->selectOne('SELECT * FROM services WHERE id = ? AND user_id = ?', [$id, $this->auth()->id()]);
        if (!$service) {
            $this->abort(404);
        }
        return $service;
    }

    protected function payload(Request $request): array
    {
        return [
            'name' => trim((string) $request->input('name')),
            'description' => (string) $request->input('description'),
            'kind' => in_array($request->input('kind'), ['service', 'product'], true) ? $request->input('kind') : 'service',
            'cost' => $request->input('cost') ? (float) str_replace(',', '.', (string) $request->input('cost')) : null,
            'suggested_price' => $request->input('suggested_price') ? (float) str_replace(',', '.', (string) $request->input('suggested_price')) : null,
            'unit' => trim((string) $request->input('unit')) ?: null,
            'is_active' => $request->input('is_active') ? 1 : 0,
        ];
    }
}
