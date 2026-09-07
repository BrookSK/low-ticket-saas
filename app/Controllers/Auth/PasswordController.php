<?php

namespace App\Controllers\Auth;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Models\User;
use App\Services\AuthService;
use App\Services\MailService;

/**
 * Recuperacao e redefinicao de senha com token seguro e expiracao.
 */
class PasswordController extends Controller
{
    protected Database $db;
    protected AuthService $auth;

    public function __construct(Database $db, AuthService $auth)
    {
        $this->db = $db;
        $this->auth = $auth;
    }

    public function requestForm(): Response
    {
        return $this->view('auth.password_request');
    }

    public function sendLink(Request $request): Response
    {
        $email = strtolower(trim((string) $request->input('email')));

        $errors = $this->validate(['email' => $email], ['email' => 'required|email']);
        if (!empty($errors)) {
            $this->withErrors($errors, ['email' => $email]);
            return $this->redirect('/esqueci-senha');
        }

        $user = User::findBy('email', $email);

        // Sempre responde igual (nao revela se o e-mail existe).
        if ($user) {
            $token = str_random(64);
            $this->db->insert(
                'INSERT INTO password_resets (email, token, created_at, expires_at) VALUES (?,?,?,?)',
                [$email, hash('sha256', $token), now(), date('Y-m-d H:i:s', strtotime('+1 hour'))]
            );

            try {
                app(MailService::class)->sendTemplate('password_reset', $email, [
                    'name' => $user['name'],
                    'reset_url' => url('/redefinir-senha/' . $token),
                ]);
            } catch (\Throwable $e) {
                // Ignora falha de envio para nao vazar informacao.
            }
        }

        $this->withFlash('success', 'Se o e-mail existir, enviamos as instrucoes de recuperacao.');
        return $this->redirect('/esqueci-senha');
    }

    public function resetForm(Request $request): Response
    {
        $token = (string) $request->param('token');
        return $this->view('auth.password_reset', ['token' => $token]);
    }

    public function reset(Request $request): Response
    {
        $token = (string) $request->input('token');
        $email = strtolower(trim((string) $request->input('email')));
        $password = (string) $request->input('password');

        $errors = $this->validate($request->all(), [
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);
        if (!empty($errors)) {
            $this->withErrors($errors, ['email' => $email]);
            return $this->redirect('/redefinir-senha/' . $token);
        }

        $row = $this->db->selectOne(
            'SELECT * FROM password_resets WHERE email = ? AND token = ? AND used_at IS NULL AND expires_at > NOW() ORDER BY id DESC LIMIT 1',
            [$email, hash('sha256', $token)]
        );

        if (!$row) {
            $this->withErrors(['email' => 'Token invalido ou expirado.'], ['email' => $email]);
            return $this->redirect('/esqueci-senha');
        }

        $user = User::findBy('email', $email);
        if (!$user) {
            $this->withFlash('error', 'Usuario nao encontrado.');
            return $this->redirect('/esqueci-senha');
        }

        User::update((int) $user['id'], ['password' => $this->auth->hash($password)]);
        $this->db->execute('UPDATE password_resets SET used_at = ? WHERE id = ?', [now(), $row['id']]);

        $this->withFlash('success', 'Senha redefinida com sucesso. Faca login.');
        return $this->redirect('/login');
    }
}
