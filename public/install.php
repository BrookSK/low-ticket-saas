<?php

/**
 * Instalador web (para hospedagem sem acesso ao terminal/SSH).
 *
 * Acesse pelo navegador: https://SEU_DOMINIO/install.php
 * Ele testa a conexao, cria as tabelas (schema.sql) e popula os dados
 * iniciais (Super Admin, produtos, templates).
 *
 * SEGURANCA:
 * - Exige confirmacao por formulario (nao roda em GET simples).
 * - Apos concluir, APAGUE este arquivo (o proprio instalador avisa e
 *   oferece um botao para remove-lo).
 */

declare(strict_types=1);

use App\Core\Config;
use App\Core\Database;

require dirname(__DIR__) . '/app/Support/autoload.php';

Config::load(dirname(__DIR__) . '/config');

$action = $_POST['action'] ?? null;
$demo = !empty($_POST['demo']);
$output = [];
$error = null;
$done = false;

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

// Auto-remocao apos instalado.
if (($_POST['action'] ?? '') === 'self_delete') {
    @unlink(__FILE__);
    header('Location: /');
    exit;
}

if ($action === 'install') {
    try {
        $db = new Database(Config::get('database'));
        $pdo = $db->pdo(); // testa conexao

        // 1) Schema
        $schema = file_get_contents(dirname(__DIR__) . '/database/schema.sql');
        $statements = array_filter(
            array_map('trim', preg_split('/;\s*[\r\n]/', $schema)),
            fn($s) => $s !== '' && !str_starts_with($s, '--')
        );
        $count = 0;
        foreach ($statements as $stmt) {
            if ($stmt === '' || str_starts_with($stmt, '--')) {
                continue;
            }
            $pdo->exec($stmt);
            $count++;
        }
        $output[] = "Tabelas criadas/atualizadas ({$count} comandos).";

        // 2) Seed (reaproveita o script CLI, capturando a saida).
        // Define credenciais do admin via variaveis, se informadas.
        if (!empty($_POST['admin_email'])) {
            putenv('ADMIN_EMAIL=' . $_POST['admin_email']);
        }
        if (!empty($_POST['admin_password'])) {
            putenv('ADMIN_PASSWORD=' . $_POST['admin_password']);
        }
        // O seed.php usa $argv para detectar --demo; simulamos aqui.
        $GLOBALS['argv'] = $demo ? ['seed', '--demo'] : ['seed'];

        ob_start();
        require dirname(__DIR__) . '/database/seed.php';
        $seedOut = ob_get_clean();
        $output[] = "Dados iniciais aplicados:";
        foreach (array_filter(array_map('trim', explode("\n", $seedOut))) as $line) {
            $output[] = '  ' . $line;
        }

        $done = true;
    } catch (\Throwable $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalador - LowTicket SaaS</title>
    <style>
        body{font-family:system-ui,-apple-system,'Segoe UI',Roboto,sans-serif;background:#0f172a;color:#e2e8f0;margin:0;padding:2rem}
        .box{max-width:640px;margin:0 auto;background:#1e293b;border-radius:14px;padding:2rem;box-shadow:0 20px 40px rgba(0,0,0,.4)}
        h1{margin-top:0}
        .muted{color:#94a3b8;font-size:.9rem}
        label{display:block;margin:.75rem 0 .25rem;font-size:.9rem;font-weight:600}
        input[type=text],input[type=email],input[type=password]{width:100%;padding:.65rem;border-radius:8px;border:1px solid #334155;background:#0f172a;color:#e2e8f0;box-sizing:border-box}
        .btn{display:inline-block;margin-top:1.25rem;padding:.75rem 1.5rem;border:none;border-radius:8px;background:#6366f1;color:#fff;font-weight:600;cursor:pointer;font-size:1rem}
        .btn.danger{background:#dc2626}
        .ok{background:#064e3b;border:1px solid #059669;padding:1rem;border-radius:8px}
        .err{background:#450a0a;border:1px solid #dc2626;padding:1rem;border-radius:8px;color:#fecaca}
        pre{background:#0f172a;padding:1rem;border-radius:8px;overflow:auto;font-size:.82rem;color:#a7f3d0}
        .check{margin:.5rem 0}
    </style>
</head>
<body>
<div class="box">
    <h1>Instalador — LowTicket SaaS</h1>

    <?php if ($error): ?>
        <div class="err"><strong>Erro:</strong> <?= h($error) ?><br><br>
        Verifique as credenciais em <code>config/database.php</code> e se o banco de dados foi criado.</div>
        <p><a href="install.php" style="color:#a5b4fc">Tentar novamente</a></p>

    <?php elseif ($done): ?>
        <div class="ok">
            <strong>Instalacao concluida!</strong>
            <pre><?= h(implode("\n", $output)) ?></pre>
        </div>
        <p class="muted">Por seguranca, remova este instalador agora.</p>
        <form method="post">
            <input type="hidden" name="action" value="self_delete">
            <button class="btn danger" type="submit">Apagar install.php e ir para o site</button>
        </form>

    <?php else: ?>
        <p class="muted">
            Este assistente cria as tabelas e os dados iniciais. Antes de continuar, confirme que
            <code>config/database.php</code> ja aponta para um banco existente.
        </p>
        <form method="post">
            <input type="hidden" name="action" value="install">
            <label>E-mail do Super Admin</label>
            <input type="email" name="admin_email" placeholder="admin@seudominio.com" required>
            <label>Senha do Super Admin (min. 8 caracteres)</label>
            <input type="password" name="admin_password" placeholder="Senha forte" minlength="8" required>
            <div class="check">
                <label style="display:inline-flex;align-items:center;gap:.5rem;font-weight:400">
                    <input type="checkbox" name="demo" value="1"> Incluir dados de demonstracao (apenas para testes)
                </label>
            </div>
            <button class="btn" type="submit">Instalar agora</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
