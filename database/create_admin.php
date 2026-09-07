<?php

/**
 * Cria (ou atualiza a senha de) um Super Admin de forma segura.
 *
 * Uso interativo:  php database/create_admin.php
 * Uso direto:      php database/create_admin.php "Nome" email@dominio.com "SenhaForte"
 */

declare(strict_types=1);

require dirname(__DIR__) . '/app/Support/autoload.php';

use App\Core\Config;
use App\Core\Database;

Config::load(dirname(__DIR__) . '/config');
$db = new Database(Config::get('database'));

function prompt(string $label, bool $hidden = false): string
{
    echo $label;
    if ($hidden && strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
        shell_exec('stty -echo');
        $value = trim((string) fgets(STDIN));
        shell_exec('stty echo');
        echo "\n";
    } else {
        $value = trim((string) fgets(STDIN));
    }
    return $value;
}

$name = $argv[1] ?? prompt('Nome do Super Admin: ');
$email = $argv[2] ?? prompt('E-mail: ');
$password = $argv[3] ?? prompt('Senha: ', true);

if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
    fwrite(STDERR, "Dados invalidos. E-mail valido e senha com no minimo 8 caracteres sao obrigatorios.\n");
    exit(1);
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$superRole = $db->selectOne('SELECT id FROM roles WHERE slug = ?', ['super_admin']);
if (!$superRole) {
    fwrite(STDERR, "Role super_admin nao encontrada. Rode 'php database/seed.php' primeiro.\n");
    exit(1);
}

$existing = $db->selectOne('SELECT id FROM users WHERE email = ?', [$email]);
if ($existing) {
    $db->execute('UPDATE users SET name = ?, password = ?, status = ?, email_verified_at = ?, updated_at = ? WHERE id = ?',
        [$name, $hash, 'active', now(), now(), $existing['id']]);
    $hasRole = $db->selectOne('SELECT 1 FROM user_roles WHERE user_id = ? AND role_id = ?', [$existing['id'], $superRole['id']]);
    if (!$hasRole) {
        $db->insert('INSERT INTO user_roles (user_id, role_id) VALUES (?,?)', [$existing['id'], $superRole['id']]);
    }
    echo "Super Admin atualizado: {$email}\n";
} else {
    $userId = $db->insert(
        'INSERT INTO users (name, email, password, email_verified_at, status, onboarding_done, created_at, updated_at)
         VALUES (?,?,?,?,?,1,?,?)',
        [$name, $email, $hash, now(), 'active', now(), now()]
    );
    $db->insert('INSERT INTO user_roles (user_id, role_id) VALUES (?,?)', [$userId, $superRole['id']]);
    echo "Super Admin criado: {$email}\n";
}
