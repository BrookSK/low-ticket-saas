<?php /** @var \App\Core\View $this */ ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Checkout') ?> - <?= e(setting('app.name', 'Meu Orçamento')) ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= url('assets/img/logo.svg') ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= url('assets/css/app.css') ?>">
    <?php $this->partial('partials.analytics'); ?>
    <style>body{background:#f1f5f9}.co{max-width:560px;margin:2rem auto;padding:0 1rem}</style>
</head>
<body>
    <div class="co">
        <div class="text-center mb-3" style="font-weight:800;font-size:1.3rem"><img src="<?= url('assets/img/logo.svg') ?>" alt="" style="width:34px;height:34px;vertical-align:middle;margin-right:.4rem;border-radius:8px"><?= e(setting('app.name', 'Meu Orçamento')) ?></div>
        <?php $this->partial('partials.flash'); ?>
        <?= $this->section('content') ?>
    </div>
    <script src="<?= url('assets/js/app.js') ?>"></script>
    <?= $this->section('scripts') ?>
</body>
</html>
