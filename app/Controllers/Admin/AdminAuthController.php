<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;
use App\Services\LogService;

/**
 * Login separado do painel administrativo.
 */
class AdminAuthController extends Controller
{
    protected AuthService $auth;
    protected LogService $log;

    public function __construct(AuthService $auth, LogService $log)
    {
        $this->auth = $auth;
        $this->log = $log;
    }

    public function show(): Response
    {
        // Se ja logado como admin, vai direto ao painel.
        if ($this->auth->check() && $this->auth->isAdmin()) {
            return $this->redirect('/admin');
        }
        return $this->view('admin.login');
    }

    public function login(Request $request): Response
    {
        $email = strtolower(trim((string) $request->input('email')));
        $password = (string) $request->input('password');

        $errors = $this->validate(['email' => $email, 'password' => $password], [
            'email' => 'required|email',
            'password' => 'required',
        ]);
        if (!empty($errors)) {
            $this->withErrors($errors, ['email' => $email]);
            return $this->redirect('/admin/login');
        }

        if ($this->auth->tooManyAttempts($email, 'admin')) {
            $this->withErrors(['email' => 'Muitas tentativas. Tente novamente mais tarde.'], ['email' => $email]);
            return $this->redirect('/admin/login');
        }

        $user = $this->auth->attempt($email, $password, 'admin', $request->ip());
        if (!$user) {
            $this->log->admin('admin_login_failed', null, "Tentativa de login admin falhou para {$email}", [], 'warning');
            $this->withErrors(['email' => 'Credenciais invalidas ou sem permissao administrativa.'], ['email' => $email]);
            return $this->redirect('/admin/login');
        }

        $this->auth->clearAttempts($email, 'admin');
        $this->auth->login($user);
        $this->log->admin('admin_login', (int) $user['id'], "Login administrativo: {$email}");

        return $this->redirect('/admin');
    }

    public function logout(): Response
    {
        $this->auth->logout();
        return $this->redirect('/admin/login');
    }
}
