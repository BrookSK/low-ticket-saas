<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Services\LogService;

/**
 * Gestao de produtos comerciais (precos configuraveis, nao hardcoded).
 */
class ProductsController extends Controller
{
    protected Database $db;
    protected LogService $log;

    public function __construct(Database $db, LogService $log)
    {
        $this->db = $db;
        $this->log = $log;
    }

    public function index(): Response
    {
        $products = $this->db->select('SELECT * FROM products ORDER BY sort_order, id');
        return $this->view('admin.products.index', ['title' => 'Produtos', 'products' => $products]);
    }

    public function create(): Response
    {
        return $this->view('admin.products.form', ['title' => 'Novo produto', 'product' => null]);
    }

    public function edit(Request $request): Response
    {
        $product = $this->db->selectOne('SELECT * FROM products WHERE id = ?', [$request->param('id')]);
        if (!$product) {
            $this->abort(404);
        }
        return $this->view('admin.products.form', ['title' => 'Editar produto', 'product' => $product]);
    }

    public function store(Request $request): Response
    {
        $data = $this->payload($request);
        $errors = $this->validate($data, [
            'name' => 'required|max:191',
            'slug' => 'required|max:191|unique:products,slug',
            'price' => 'required|numeric',
        ]);
        if (!empty($errors)) {
            $this->withErrors($errors, $data);
            return $this->redirect('/admin/produtos/novo');
        }

        $this->db->insert(
            'INSERT INTO products (name, slug, description, price, promo_price, type, grants_access, features, is_active, sort_order, created_at, updated_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
            [
                $data['name'], $data['slug'], $data['description'], $data['price'],
                $data['promo_price'] ?: null, $data['type'], $data['grants_access'],
                json_encode(array_filter(array_map('trim', explode("\n", (string) $data['features']))), JSON_UNESCAPED_UNICODE),
                $data['is_active'], (int) $data['sort_order'], now(), now(),
            ]
        );
        $this->log->admin('product_created', $this->auth()->id(), "Produto criado: {$data['slug']}");
        $this->withFlash('success', 'Produto criado.');
        return $this->redirect('/admin/produtos');
    }

    public function update(Request $request): Response
    {
        $id = (int) $request->param('id');
        $data = $this->payload($request);
        $errors = $this->validate($data, [
            'name' => 'required|max:191',
            'slug' => "required|max:191|unique:products,slug,{$id}",
            'price' => 'required|numeric',
        ]);
        if (!empty($errors)) {
            $this->withErrors($errors, $data);
            return $this->redirect('/admin/produtos/' . $id . '/editar');
        }

        $this->db->execute(
            'UPDATE products SET name=?, slug=?, description=?, price=?, promo_price=?, type=?, grants_access=?, features=?, is_active=?, sort_order=?, updated_at=? WHERE id=?',
            [
                $data['name'], $data['slug'], $data['description'], $data['price'],
                $data['promo_price'] ?: null, $data['type'], $data['grants_access'],
                json_encode(array_filter(array_map('trim', explode("\n", (string) $data['features']))), JSON_UNESCAPED_UNICODE),
                $data['is_active'], (int) $data['sort_order'], now(), $id,
            ]
        );
        $this->log->admin('product_updated', $this->auth()->id(), "Produto atualizado: {$data['slug']}");
        $this->withFlash('success', 'Produto atualizado.');
        return $this->redirect('/admin/produtos');
    }

    public function destroy(Request $request): Response
    {
        $id = (int) $request->param('id');
        $this->db->execute('UPDATE products SET is_active = 0 WHERE id = ?', [$id]);
        $this->log->admin('product_disabled', $this->auth()->id(), "Produto desativado #{$id}");
        $this->withFlash('success', 'Produto desativado.');
        return $this->redirect('/admin/produtos');
    }

    protected function payload(Request $request): array
    {
        return [
            'name' => trim((string) $request->input('name')),
            'slug' => trim((string) $request->input('slug')),
            'description' => (string) $request->input('description'),
            'price' => str_replace(',', '.', (string) $request->input('price')),
            'promo_price' => $request->input('promo_price') ? str_replace(',', '.', (string) $request->input('promo_price')) : null,
            'type' => (string) $request->input('type', 'one_time'),
            'grants_access' => trim((string) $request->input('grants_access')),
            'features' => (string) $request->input('features'),
            'is_active' => $request->input('is_active') ? 1 : 0,
            'sort_order' => (int) $request->input('sort_order', 0),
        ];
    }
}
