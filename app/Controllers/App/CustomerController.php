<?php

namespace App\Controllers\App;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

/**
 * CRUD de clientes com pagina de detalhes/historico.
 * Todo acesso e escopado ao usuario logado (isolamento por tenant/usuario).
 */
class CustomerController extends Controller
{
    protected Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function index(Request $request): Response
    {
        $userId = $this->auth()->id();
        $q = trim((string) $request->query('q', ''));

        if ($q !== '') {
            $like = '%' . $q . '%';
            $customers = $this->db->select(
                'SELECT * FROM customers WHERE user_id = ? AND (name LIKE ? OR email LIKE ? OR document LIKE ?) ORDER BY name',
                [$userId, $like, $like, $like]
            );
        } else {
            $customers = $this->db->select('SELECT * FROM customers WHERE user_id = ? ORDER BY name', [$userId]);
        }

        return $this->view('app.customers.index', [
            'title' => 'Clientes',
            'customers' => $customers,
            'q' => $q,
        ]);
    }

    public function create(): Response
    {
        return $this->view('app.customers.form', ['title' => 'Novo cliente', 'customer' => null]);
    }

    public function store(Request $request): Response
    {
        $data = $this->payload($request);
        $errors = $this->validate($data, ['name' => 'required|max:191', 'email' => 'email']);
        if (!empty($errors)) {
            $this->withErrors($errors, $data);
            return $this->redirect('/clientes/novo');
        }

        $data['user_id'] = $this->auth()->id();
        $id = $this->db->insert(
            'INSERT INTO customers (user_id, name, document, email, phone, whatsapp, address, city, state, zipcode, notes, created_at, updated_at)
             VALUES (:user_id,:name,:document,:email,:phone,:whatsapp,:address,:city,:state,:zipcode,:notes,:c,:u)',
            $this->bindings($data)
        );

        $this->withFlash('success', 'Cliente cadastrado.');
        return $this->redirect('/clientes/' . $id);
    }

    public function show(Request $request): Response
    {
        $customer = $this->ownedCustomer((int) $request->param('id'));

        $quotes = $this->db->select('SELECT * FROM quotes WHERE customer_id = ? ORDER BY created_at DESC', [$customer['id']]);
        $revenues = $this->db->select('SELECT * FROM revenues WHERE customer_id = ? ORDER BY date DESC', [$customer['id']]);
        $documents = $this->db->select('SELECT * FROM customer_documents WHERE customer_id = ? ORDER BY created_at DESC', [$customer['id']]);

        return $this->view('app.customers.show', [
            'title' => $customer['name'],
            'customer' => $customer,
            'quotes' => $quotes,
            'revenues' => $revenues,
            'documents' => $documents,
        ]);
    }

    public function edit(Request $request): Response
    {
        $customer = $this->ownedCustomer((int) $request->param('id'));
        return $this->view('app.customers.form', ['title' => 'Editar cliente', 'customer' => $customer]);
    }

    public function update(Request $request): Response
    {
        $id = (int) $request->param('id');
        $this->ownedCustomer($id);
        $data = $this->payload($request);
        $errors = $this->validate($data, ['name' => 'required|max:191', 'email' => 'email']);
        if (!empty($errors)) {
            $this->withErrors($errors, $data);
            return $this->redirect('/clientes/' . $id . '/editar');
        }

        $this->db->execute(
            'UPDATE customers SET name=:name, document=:document, email=:email, phone=:phone, whatsapp=:whatsapp,
             address=:address, city=:city, state=:state, zipcode=:zipcode, notes=:notes, updated_at=:u WHERE id=:id',
            array_merge($this->bindings($data, false), [':id' => $id])
        );

        $this->withFlash('success', 'Cliente atualizado.');
        return $this->redirect('/clientes/' . $id);
    }

    public function destroy(Request $request): Response
    {
        $id = (int) $request->param('id');
        $this->ownedCustomer($id);
        $this->db->execute('DELETE FROM customers WHERE id = ?', [$id]);
        $this->withFlash('success', 'Cliente removido.');
        return $this->redirect('/clientes');
    }

    // ---- Helpers ----

    protected function ownedCustomer(int $id): array
    {
        $customer = $this->db->selectOne('SELECT * FROM customers WHERE id = ? AND user_id = ?', [$id, $this->auth()->id()]);
        if (!$customer) {
            $this->abort(404, 'Cliente nao encontrado.');
        }
        return $customer;
    }

    protected function payload(Request $request): array
    {
        return [
            'name' => trim((string) $request->input('name')),
            'document' => trim((string) $request->input('document')),
            'email' => trim((string) $request->input('email')),
            'phone' => trim((string) $request->input('phone')),
            'whatsapp' => trim((string) $request->input('whatsapp')),
            'address' => trim((string) $request->input('address')),
            'city' => trim((string) $request->input('city')),
            'state' => trim((string) $request->input('state')),
            'zipcode' => trim((string) $request->input('zipcode')),
            'notes' => (string) $request->input('notes'),
        ];
    }

    protected function bindings(array $data, bool $withUserAndCreate = true): array
    {
        $b = [
            ':name' => $data['name'], ':document' => $data['document'] ?: null,
            ':email' => $data['email'] ?: null, ':phone' => $data['phone'] ?: null,
            ':whatsapp' => $data['whatsapp'] ?: null, ':address' => $data['address'] ?: null,
            ':city' => $data['city'] ?: null, ':state' => $data['state'] ?: null,
            ':zipcode' => $data['zipcode'] ?: null, ':notes' => $data['notes'] ?: null,
            ':u' => now(),
        ];
        if ($withUserAndCreate) {
            $b[':user_id'] = $data['user_id'];
            $b[':c'] = now();
        }
        return $b;
    }
}
