<?php
/** @var \App\Core\View $this */
$this->extend('layouts.auth');
$title = 'Entrar';
?>
<?php $this->start('content'); ?>
    <h1 style="font-size:1.4rem;text-align:center">Entrar na sua conta</h1>
    <p class="text-muted text-center mb-3">Bem-vindo de volta! Acesse seu painel.</p>

    <form method="POST" action="<?= url('/login') ?>">
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
    <a href="<?= url('/esqueci-senha') ?>">Esqueci minha senha</a>
    <div class="mt-1">Nao tem conta? <a href="<?= url('/cadastro') ?>">Criar conta gratis</a></div>
<?php $this->stop(); ?>
