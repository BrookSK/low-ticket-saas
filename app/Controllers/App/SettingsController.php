<?php

namespace App\Controllers\App;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\User;

/**
 * Configuracoes do usuario: perfil, senha, dados da empresa (para PDF),
 * e exclusao de conta (LGPD).
 */
class SettingsController extends Controller
{
    protected Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function index(): Response
    {
        $user = $this->user();
        $company = $user['company_id'] ? $this->db->selectOne('SELECT * FROM companies WHERE id = ?', [$user['company_id']]) : null;
        return $this->view('app.settings', ['title' => 'Configuracoes', 'user' => $user, 'company' => $company]);
    }

    public function updateProfile(Request $request): Response
    {
        $userId = $this->auth()->id();
        $data = [
            'name' => trim((string) $request->input('name')),
            'phone' => trim((string) $request->input('phone')),
            'whatsapp' => trim((string) $request->input('whatsapp')),
        ];
        $errors = $this->validate($data, ['name' => 'required|max:191']);
        if (!empty($errors)) {
            $this->withErrors($errors, $data);
            return $this->redirect('/configuracoes');
        }
        User::update($userId, $data);
        $this->withFlash('success', 'Perfil atualizado.');
        return $this->redirect('/configuracoes');
    }

    public function updatePassword(Request $request): Response
    {
        $userId = $this->auth()->id();
        $user = $this->user();
        $current = (string) $request->input('current_password');
        $new = (string) $request->input('password');

        if (!$this->auth()->verify($current, $user['password'])) {
            $this->withErrors(['current_password' => 'Senha atual incorreta.']);
            return $this->redirect('/configuracoes');
        }
        $errors = $this->validate($request->all(), ['password' => 'required|min:8|confirmed']);
        if (!empty($errors)) {
            $this->withErrors($errors);
            return $this->redirect('/configuracoes');
        }
        User::update($userId, ['password' => $this->auth()->hash($new)]);
        $this->withFlash('success', 'Senha alterada.');
        return $this->redirect('/configuracoes');
    }

    public function updateCompany(Request $request): Response
    {
        $user = $this->user();
        $data = [
            'name' => trim((string) $request->input('company_name')),
            'document' => trim((string) $request->input('company_document')),
            'email' => trim((string) $request->input('company_email')),
            'phone' => trim((string) $request->input('company_phone')),
            'address' => trim((string) $request->input('company_address')),
            'city' => trim((string) $request->input('company_city')),
            'state' => trim((string) $request->input('company_state')),
            'zipcode' => trim((string) $request->input('company_zipcode')),
        ];

        if ($user['company_id']) {
            $this->db->execute(
                'UPDATE companies SET name=?, document=?, email=?, phone=?, address=?, city=?, state=?, zipcode=?, updated_at=? WHERE id=?',
                [$data['name'], $data['document'], $data['email'], $data['phone'], $data['address'], $data['city'], $data['state'], $data['zipcode'], now(), $user['company_id']]
            );
        } else {
            $companyId = $this->db->insert(
                'INSERT INTO companies (name, document, email, phone, address, city, state, zipcode, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?)',
                [$data['name'], $data['document'], $data['email'], $data['phone'], $data['address'], $data['city'], $data['state'], $data['zipcode'], now(), now()]
            );
            User::update((int) $user['id'], ['company_id' => $companyId]);
        }
        $this->withFlash('success', 'Dados da empresa atualizados.');
        return $this->redirect('/configuracoes');
    }

    public function deleteAccount(Request $request): Response
    {
        $userId = $this->auth()->id();
        $confirm = (string) $request->input('confirm');
        if ($confirm !== 'EXCLUIR') {
            $this->withFlash('error', 'Digite EXCLUIR para confirmar.');
            return $this->redirect('/configuracoes');
        }
        // Exclusao definitiva dos dados do usuario (LGPD). FKs em cascata cuidam do restante.
        $this->auth()->logout();
        $this->db->execute('DELETE FROM users WHERE id = ?', [$userId]);
        Session::flash('success', 'Sua conta foi excluida.');
        return $this->redirect('/');
    }
}
