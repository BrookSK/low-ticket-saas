<?php
/** @var \App\Core\View $this */
$auth = app(\App\Services\AuthService::class);
$currentUser = $auth->user();
$path = app(\App\Core\Request::class)->path();
$active = fn($prefix) => str_starts_with($path, $prefix) ? 'active' : '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Admin') ?> - Admin</title>
    <meta name="robots" content="noindex">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= url('assets/css/app.css') ?>">
    <link rel="stylesheet" href="<?= url('assets/css/dashboard.css') ?>">
    <style>.sidebar .brand .logo-mark{background:linear-gradient(135deg,#f59e0b,#ef4444)}</style>
    <?= $this->section('head') ?>
</head>
<body>
<div class="layout">
    <aside class="sidebar" id="sidebar">
        <div class="brand"><span class="logo-mark">A</span> Admin</div>
        <nav>
            <a class="nav-link <?= $path === '/admin' || $active('/admin/dashboard') ? 'active' : '' ?>" href="<?= url('/admin') ?>"><span class="ic">■</span> Dashboard</a>

            <div class="nav-group">Vendas</div>
            <a class="nav-link <?= $active('/admin/vendas') ?>" href="<?= url('/admin/vendas') ?>"><span class="ic">↗</span> Vendas</a>
            <a class="nav-link <?= $active('/admin/pedidos') ?>" href="<?= url('/admin/pedidos') ?>"><span class="ic">▤</span> Pedidos</a>
            <a class="nav-link <?= $active('/admin/cupons') ?>" href="<?= url('/admin/cupons') ?>"><span class="ic">%</span> Cupons</a>

            <div class="nav-group">Produtos</div>
            <a class="nav-link <?= $active('/admin/produtos') ?>" href="<?= url('/admin/produtos') ?>"><span class="ic">◫</span> Produtos</a>
            <a class="nav-link <?= $active('/admin/upsells') ?>" href="<?= url('/admin/upsells') ?>"><span class="ic">+</span> Upsells</a>

            <div class="nav-group">Usuarios</div>
            <a class="nav-link <?= $active('/admin/usuarios') ?>" href="<?= url('/admin/usuarios') ?>"><span class="ic">◉</span> Usuarios</a>

            <div class="nav-group">Marketing</div>
            <a class="nav-link <?= $active('/admin/marketing') ?>" href="<?= url('/admin/marketing') ?>"><span class="ic">◎</span> Marketing / Analytics</a>

            <div class="nav-group">Comunicacao</div>
            <a class="nav-link <?= $active('/admin/emails') ?>" href="<?= url('/admin/emails') ?>"><span class="ic">✉</span> E-mails</a>
            <a class="nav-link <?= $active('/admin/whatsapp') ?>" href="<?= url('/admin/whatsapp') ?>"><span class="ic">☏</span> WhatsApp</a>
            <a class="nav-link <?= $active('/admin/automacoes') ?>" href="<?= url('/admin/automacoes') ?>"><span class="ic">⟳</span> Automacoes</a>

            <div class="nav-group">Sistema</div>
            <a class="nav-link <?= $active('/admin/relatorios') ?>" href="<?= url('/admin/relatorios') ?>"><span class="ic">▦</span> Relatorios</a>
            <a class="nav-link <?= $active('/admin/configuracoes') ?>" href="<?= url('/admin/configuracoes') ?>"><span class="ic">⚙</span> Configuracoes</a>
            <a class="nav-link <?= $active('/admin/logs') ?>" href="<?= url('/admin/logs') ?>"><span class="ic">≡</span> Logs</a>
        </nav>
        <div class="side-footer">
            <a class="nav-link" href="<?= url('/dashboard') ?>"><span class="ic">←</span> Voltar ao app</a>
        </div>
    </aside>
    <div class="sidebar-backdrop"></div>

    <div class="main">
        <header class="topbar">
            <div class="flex items-center gap-2">
                <button class="menu-toggle" data-sidebar-toggle aria-label="Menu">☰</button>
                <span class="page-title"><?= e($title ?? 'Admin') ?></span>
            </div>
            <div class="actions">
                <span class="badge badge-purple">Super Admin</span>
                <div class="dropdown">
                    <button class="flex items-center gap-1" data-dropdown style="background:none;border:none;cursor:pointer">
                        <span class="avatar"><?= e(strtoupper(mb_substr($currentUser['name'] ?? 'A', 0, 1))) ?></span>
                    </button>
                    <div class="dropdown-menu">
                        <form method="POST" action="<?= url('/admin/logout') ?>">
                            <?= csrf_field() ?>
                            <button type="submit">Sair</button>
                        </form>
                    </div>
                </div>
            </div>
        </header>
        <main class="content">
            <?php $this->partial('partials.flash'); ?>
            <?= $this->section('content') ?>
        </main>
    </div>
</div>
<script src="<?= url('assets/js/app.js') ?>"></script>
<?= $this->section('scripts') ?>
</body>
</html>
