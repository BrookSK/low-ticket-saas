<?php

namespace App\Controllers\Auth;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\AuthService;

/**
 * Login e logout de usuarios finais.
 */
class LoginController extends Controller
{
    protected AuthService $auth;

    public function __construct(AuthService $auth)
    {
        $this->auth = $auth;
    }

    public function show(): Response
    {
        return $this->view('auth.login');
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
            return $this->redirect('/login');
        }

        if ($this->auth->tooManyAttempts($email, 'user')) {
            $this->withErrors(['email' => 'Muitas tentativas de login. Tente novamente mais tarde.'], ['email' => $email]);
            return $this->redirect('/login');
        }

        $user = $this->auth->attempt($email, $password, 'user', $request->ip());
        if (!$user) {
            $this->withErrors(['email' => 'Credenciais invalidas.'], ['email' => $email]);
            return $this->redirect('/login');
        }

        $this->auth->clearAttempts($email, 'user');
        $this->auth->login($user);

        $intended = Session::getFlash('intended', '/dashboard');
        return $this->redirect($intended ?: '/dashboard');
    }

    public function logout(): Response
    {
        $this->auth->logout();
        return $this->redirect('/');
    }
}
