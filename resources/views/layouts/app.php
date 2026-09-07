<?php
/** @var \App\Core\View $this */
$auth = app(\App\Services\AuthService::class);
$currentUser = $auth->user();
$modules = $currentUser ? \App\Models\User::modules((int) $currentUser['id']) : [];
$isAdmin = $auth->isAdmin();
$path = app(\App\Core\Request::class)->path();
$has = fn($m) => $isAdmin || in_array($m, $modules, true);
$active = fn($prefix) => str_starts_with($path, $prefix) ? 'active' : '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Painel') ?> - <?= e(setting('app.name', 'LowTicket SaaS')) ?></title>
    <meta name="robots" content="noindex">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= url('assets/css/app.css') ?>">
    <link rel="stylesheet" href="<?= url('assets/css/dashboard.css') ?>">
    <?php $this->partial('partials.analytics'); ?>
    <?= $this->section('head') ?>
</head>
<body>
<div class="layout">
    <aside class="sidebar" id="sidebar">
        <div class="brand"><span class="logo-mark">L</span> <?= e(setting('app.name', 'LowTicket')) ?></div>
        <nav>
            <a class="nav-link <?= $active('/dashboard') ?>" href="<?= url('/dashboard') ?>"><span class="ic">■</span> Dashboard</a>

            <?php if ($has('orcamentos')): ?>
            <div class="nav-group">Orcamentos</div>
            <a class="nav-link <?= $active('/orcamentos') ?>" href="<?= url('/orcamentos') ?>"><span class="ic">▤</span> Orcamentos</a>
            <a class="nav-link <?= $active('/clientes') ?>" href="<?= url('/clientes') ?>"><span class="ic">◉</span> Clientes</a>
            <a class="nav-link <?= $active('/servicos') ?>" href="<?= url('/servicos') ?>"><span class="ic">≣</span> Servicos e Produtos</a>
            <?php endif; ?>

            <?php if ($has('financeiro')): ?>
            <div class="nav-group">Financeiro</div>
            <a class="nav-link <?= $active('/financeiro') ?>" href="<?= url('/financeiro') ?>"><span class="ic">$</span> Dashboard financeiro</a>
            <a class="nav-link <?= $active('/receitas') ?>" href="<?= url('/receitas') ?>"><span class="ic">↑</span> Receitas</a>
            <a class="nav-link <?= $active('/despesas') ?>" href="<?= url('/despesas') ?>"><span class="ic">↓</span> Despesas</a>
            <?php endif; ?>

            <?php if ($has('precificador')): ?>
            <div class="nav-group">Ferramentas</div>
            <a class="nav-link <?= $active('/precificador') ?>" href="<?= url('/precificador') ?>"><span class="ic">%</span> Precificador</a>
            <?php endif; ?>

            <div class="nav-group">Conta</div>
            <a class="nav-link <?= $active('/configuracoes') ?>" href="<?= url('/configuracoes') ?>"><span class="ic">⚙</span> Configuracoes</a>
            <?php if ($isAdmin): ?>
            <a class="nav-link" href="<?= url('/admin') ?>"><span class="ic">★</span> Painel Admin</a>
            <?php endif; ?>
        </nav>
        <div class="side-footer">
            <?php if (!$has('financeiro') || !$has('precificador')): ?>
                <a class="btn btn-primary btn-sm btn-block" href="<?= url('/checkout/plano-completo') ?>">Desbloquear tudo</a>
            <?php endif; ?>
        </div>
    </aside>
    <div class="sidebar-backdrop"></div>

    <div class="main">
        <header class="topbar">
            <div class="flex items-center gap-2">
                <button class="menu-toggle" data-sidebar-toggle aria-label="Menu">☰</button>
                <span class="page-title"><?= e($title ?? 'Painel') ?></span>
            </div>
            <div class="actions">
                <div class="dropdown">
                    <button class="flex items-center gap-1" data-dropdown style="background:none;border:none;cursor:pointer">
                        <span class="avatar"><?= e(strtoupper(mb_substr($currentUser['name'] ?? 'U', 0, 1))) ?></span>
                        <span class="text-sm fw-600" style="max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= e($currentUser['name'] ?? '') ?></span>
                    </button>
                    <div class="dropdown-menu">
                        <a href="<?= url('/configuracoes') ?>">Configuracoes</a>
                        <form method="POST" action="<?= url('/logout') ?>">
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
