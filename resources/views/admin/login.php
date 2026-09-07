<?php
/** @var \App\Core\View $this */
$this->extend('layouts.auth');
$title = 'Admin';
?>
<?php $this->start('content'); ?>
    <h1 style="font-size:1.4rem;text-align:center">Painel Administrativo</h1>
    <p class="text-muted text-center mb-3">Acesso restrito.</p>

    <form method="POST" action="<?= url('/admin/login') ?>">
        <?= csrf_field() ?>
        <div class="form-group">
            <label class="form-label" for="email">E-mail</label>
            <input class="form-control" type="email" id="email" name="email" value="<?= e(old('email')) ?>" required autofocus>
        </div>
        <div class="form-group">
            <label class="form-label" for="password">Senha</label>
            <input class="form-control" type="password" id="password" name="password" required>
        </div>
        <button type="submit" class="btn btn-primary btn-block btn-lg">Entrar</button>
    </form>
<?php $this->stop(); ?>
<?php $this->start('links'); ?>
    <a href="<?= url('/') ?>">Voltar ao site</a>
<?php $this->stop(); ?>
