<?php

namespace App\Services;

use App\Core\Database;
use App\Core\Session;
use App\Models\User;

/**
 * Servico de autenticacao: login, logout, sessao, hash de senha,
 * protecao contra brute force via login_attempts.
 */
class AuthService
{
    protected Database $db;
    protected SettingsService $settings;
    protected ?array $cachedUser = null;

    public function __construct(Database $db, SettingsService $settings)
    {
        $this->db = $db;
        $this->settings = $settings;
    }

    public function hash(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    public function verify(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Tenta autenticar. Retorna o usuario ou null.
     * $context: 'user' | 'admin' (admin exige papel administrativo).
     */
    public function attempt(string $email, string $password, string $context = 'user', ?string $ip = null): ?array
    {
        $user = User::findBy('email', $email);

        $success = false;
        if ($user && $this->verify($password, $user['password'])) {
            if ($context === 'admin' && !User::isAdmin((int) $user['id'])) {
                $success = false;
            } elseif ($user['status'] === 'blocked') {
                $success = false;
            } else {
                $success = true;
            }
        }

        $this->recordAttempt($email, $ip, $success, $context);

        if (!$success) {
            return null;
        }

        // Atualiza rehash se necessario.
        if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
            User::update((int) $user['id'], ['password' => $this->hash($password)]);
        }

        User::update((int) $user['id'], [
            'last_login_at' => now(),
            'last_login_ip' => $ip,
        ]);

        return $user;
    }

    public function login(array $user): void
    {
        Session::regenerate();
        Session::put('user_id', (int) $user['id']);
        Session::put('user_roles', User::roles((int) $user['id']));
        $this->cachedUser = $user;
    }

    public function logout(): void
    {
        Session::forget('user_id');
        Session::forget('user_roles');
        Session::regenerate();
        $this->cachedUser = null;
    }

    public function check(): bool
    {
        return Session::has('user_id');
    }

    public function id(): ?int
    {
        $id = Session::get('user_id');
        return $id ? (int) $id : null;
    }

    public function user(): ?array
    {
        if ($this->cachedUser !== null) {
            return $this->cachedUser;
        }
        $id = $this->id();
        if (!$id) {
            return null;
        }
        $this->cachedUser = User::find($id);
        return $this->cachedUser;
    }

    public function roles(): array
    {
        return Session::get('user_roles', []);
    }

    public function isAdmin(): bool
    {
        $roles = $this->roles();
        return in_array('admin', $roles, true) || in_array('super_admin', $roles, true);
    }

    public function isSuperAdmin(): bool
    {
        return in_array('super_admin', $this->roles(), true);
    }

    // ---- Protecao contra brute force ----

    protected function recordAttempt(string $identifier, ?string $ip, bool $success, string $context): void
    {
        $this->db->insert(
            'INSERT INTO login_attempts (identifier, ip_address, successful, context, created_at) VALUES (?,?,?,?,?)',
            [$identifier, $ip, $success ? 1 : 0, $context, now()]
        );
    }

    /**
     * Verifica se o identificador esta bloqueado por excesso de tentativas.
     */
    public function tooManyAttempts(string $identifier, string $context = 'user'): bool
    {
        $max = (int) $this->settings->get('security.max_login_attempts', 5);
        $lockoutMinutes = (int) $this->settings->get('security.lockout_minutes', 15);

        $row = $this->db->selectOne(
            'SELECT COUNT(*) AS c FROM login_attempts
             WHERE identifier = ? AND context = ? AND successful = 0
             AND created_at > (NOW() - INTERVAL ? MINUTE)',
            [$identifier, $context, $lockoutMinutes]
        );
        return (int) ($row['c'] ?? 0) >= $max;
    }

    public function clearAttempts(string $identifier, string $context = 'user'): void
    {
        $this->db->execute(
            'DELETE FROM login_attempts WHERE identifier = ? AND context = ?',
            [$identifier, $context]
        );
    }
}
