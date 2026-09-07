<?php

namespace App\Controllers\Auth;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\User;
use App\Services\AttributionService;
use App\Services\AuthService;
use App\Services\EventService;
use App\Services\MailService;

/**
 * Cadastro de usuario e confirmacao de e-mail.
 */
class RegisterController extends Controller
{
    protected Database $db;
    protected AuthService $auth;
    protected AttributionService $attribution;
    protected EventService $events;

    public function __construct(Database $db, AuthService $auth, AttributionService $attribution, EventService $events)
    {
        $this->db = $db;
        $this->auth = $auth;
        $this->attribution = $attribution;
        $this->events = $events;
    }

    public function show(): Response
    {
        return $this->view('auth.register');
    }

    public function store(Request $request): Response
    {
        $data = $request->only(['name', 'email', 'phone', 'whatsapp', 'password', 'password_confirmation']);

        $errors = $this->validate($data, [
            'name' => 'required|min:2|max:191',
            'email' => 'required|email|max:191|unique:users,email',
            'phone' => 'max:32',
            'password' => 'required|min:8|confirmed',
        ]);

        if (!empty($errors)) {
            $this->withErrors($errors, $data);
            return $this->redirect('/cadastro');
        }

        $userId = User::create([
            'name' => trim($data['name']),
            'email' => strtolower(trim($data['email'])),
            'phone' => $data['phone'] ?? null,
            'whatsapp' => $data['whatsapp'] ?? ($data['phone'] ?? null),
            'password' => $this->auth->hash($data['password']),
            'status' => 'active',
        ]);

        User::assignRole($userId, 'user');

        // Associa a atribuicao de marketing ao novo usuario.
        $this->attribution->linkUser($userId);

        // Gera token de confirmacao de e-mail.
        $token = str_random(48);
        $this->db->insert(
            'INSERT INTO email_confirmations (user_id, token, created_at, expires_at) VALUES (?,?,?,?)',
            [$userId, $token, now(), date('Y-m-d H:i:s', strtotime('+3 days'))]
        );

        $user = User::find($userId);

        // Evento comercial + e-mail de boas-vindas/confirmacao.
        $this->events->dispatch('UserRegistered', [
            'user_id' => $userId,
            'email' => $user['email'],
            'name' => $user['name'],
        ]);

        try {
            app(MailService::class)->sendTemplate('email_confirmation', $user['email'], [
                'name' => $user['name'],
                'confirm_url' => url('/confirmar-email/' . $token),
            ]);
        } catch (\Throwable $e) {
            // Nao bloqueia o cadastro se o e-mail falhar.
        }

        // Autentica e envia ao onboarding.
        $this->auth->login($user);
        $this->withFlash('success', 'Conta criada com sucesso! Bem-vindo(a).');
        return $this->redirect('/onboarding');
    }

    public function confirmEmail(Request $request): Response
    {
        $token = (string) $request->param('token');
        $row = $this->db->selectOne(
            'SELECT * FROM email_confirmations WHERE token = ? AND used_at IS NULL AND expires_at > NOW()',
            [$token]
        );

        if (!$row) {
            $this->withFlash('error', 'Link de confirmacao invalido ou expirado.');
            return $this->redirect('/login');
        }

        User::update((int) $row['user_id'], ['email_verified_at' => now()]);
        $this->db->execute('UPDATE email_confirmations SET used_at = ? WHERE id = ?', [now(), $row['id']]);

        $this->withFlash('success', 'E-mail confirmado com sucesso!');
        return $this->redirect($this->auth->check() ? '/dashboard' : '/login');
    }
}
