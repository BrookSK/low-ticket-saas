<?php
/** @var \App\Core\View $this */
$auth = app(\App\Services\AuthService::class);
$logged = $auth->check();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php $this->partial('partials.seo', ['title' => $title ?? null, 'metaDescription' => $metaDescription ?? null]); ?>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= url('assets/css/app.css') ?>">
    <link rel="stylesheet" href="<?= url('assets/css/landing.css') ?>">
    <?php $this->partial('partials.analytics'); ?>
</head>
<body class="site">
    <header class="site-header">
        <div class="container flex items-center justify-between" style="height:68px">
            <a href="<?= url('/') ?>" class="site-logo"><span class="mark">L</span> <?= e(setting('app.name', 'LowTicket')) ?></a>
            <nav class="site-nav">
                <a href="<?= url('/precos') ?>">Precos</a>
                <a href="<?= url('/faq') ?>">Duvidas</a>
                <a href="<?= url('/contato') ?>">Contato</a>
            </nav>
            <div class="flex items-center gap-1">
                <?php if ($logged): ?>
                    <a class="btn btn-primary btn-sm" href="<?= url('/dashboard') ?>">Meu painel</a>
                <?php else: ?>
                    <a class="btn btn-ghost btn-sm" href="<?= url('/login') ?>">Entrar</a>
                    <a class="btn btn-primary btn-sm" href="<?= url('/cadastro') ?>">Comecar agora</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <?= $this->section('content') ?>

    <footer class="site-footer">
        <div class="container">
            <div class="footer-grid">
                <div>
                    <div class="site-logo" style="color:#fff"><span class="mark">L</span> <?= e(setting('app.name', 'LowTicket')) ?></div>
                    <p class="text-sm" style="color:#94a3b8;margin-top:.75rem;max-width:280px">Orcamentos, financeiro e precificador para quem quer crescer sem complicacao.</p>
                </div>
                <div>
                    <h4>Produto</h4>
                    <a href="<?= url('/precos') ?>">Precos</a>
                    <a href="<?= url('/faq') ?>">Duvidas</a>
                    <a href="<?= url('/cadastro') ?>">Criar conta</a>
                </div>
                <div>
                    <h4>Empresa</h4>
                    <a href="<?= url('/contato') ?>">Contato</a>
                    <a href="<?= url('/termos-de-uso') ?>">Termos de uso</a>
                    <a href="<?= url('/politica-de-privacidade') ?>">Privacidade</a>
                    <a href="<?= url('/politica-de-cookies') ?>">Cookies</a>
                </div>
            </div>
            <div class="footer-bottom">© <?= date('Y') ?> <?= e(setting('app.name', 'LowTicket')) ?>. Todos os direitos reservados.</div>
        </div>
    </footer>

    <?php if (setting('marketing.cookie_consent_enabled', true)): ?>
    <div class="cookie-banner" id="cookieBanner">
        <span>Usamos cookies para melhorar sua experiencia. Veja nossa <a href="<?= url('/politica-de-cookies') ?>">politica de cookies</a>.</span>
        <button class="btn btn-primary btn-sm" onclick="localStorage.setItem('cookie_ok','1');document.getElementById('cookieBanner').remove()">Aceitar</button>
    </div>
    <script>if(localStorage.getItem('cookie_ok')){document.getElementById('cookieBanner')?.remove();}</script>
    <?php endif; ?>

    <script src="<?= url('assets/js/app.js') ?>"></script>
</body>
</html>
