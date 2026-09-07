<?php /** @var \App\Core\View $this */ ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Acesso') ?> - <?= e(setting('app.name', 'Meu Orçamento')) ?></title>
    <meta name="robots" content="noindex">
    <link rel="icon" type="image/svg+xml" href="<?= url('favicon.svg') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= url('assets/css/app.css') ?>">
    <style>
        body { background: linear-gradient(135deg, #eef2ff 0%, #faf5ff 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1.5rem; }
        .auth-card { width: 100%; max-width: 420px; }
        .auth-logo { text-align: center; margin-bottom: 1.5rem; font-weight: 800; font-size: 1.4rem; color: var(--ink); }
        .auth-logo .mark { display: inline-flex; width: 40px; height: 40px; border-radius: 10px; background: linear-gradient(135deg, var(--brand), var(--accent)); color: #fff; align-items: center; justify-content: center; margin-right: .5rem; }
        .auth-links { text-align: center; margin-top: 1.25rem; font-size: .9rem; }
    </style>
</head>
<body>
    <div class="auth-card">
        <a href="<?= url('/') ?>" class="auth-logo" style="text-decoration:none;display:block">
            <img src="<?= url('favicon.svg') ?>" alt="" style="width:40px;height:40px;vertical-align:middle;margin-right:.5rem;border-radius:10px"><?= e(setting('app.name', 'Meu Orçamento')) ?>
        </a>
        <div class="card">
            <div class="card-body">
                <?php $this->partial('partials.flash'); ?>
                <?= $this->section('content') ?>
            </div>
        </div>
        <div class="auth-links">
            <?= $this->section('links') ?>
        </div>
    </div>
</body>
</html>
