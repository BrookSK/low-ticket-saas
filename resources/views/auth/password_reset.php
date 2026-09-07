<?php
/** @var \App\Core\View $this */
/** @var string $token */
$this->extend('layouts.auth');
$title = 'Redefinir senha';
?>
<?php $this->start('content'); ?>
    <h1 style="font-size:1.4rem;text-align:center">Redefinir senha</h1>
    <p class="text-muted text-center mb-3">Escolha uma nova senha para sua conta.</p>

    <form method="POST" action="<?= url('/redefinir-senha') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="token" value="<?= e($token) ?>">
        <div class="form-group">
            <label class="form-label" for="email">E-mail</label>
            <input class="form-control" type="email" id="email" name="email" value="<?= e(old('email')) ?>" required autofocus>
        </div>
        <div class="form-group">
            <label class="form-label" for="password">Nova senha</label>
            <input class="form-control" type="password" id="password" name="password" required>
        </div>
        <div class="form-group">
            <label class="form-label" for="password_confirmation">Confirmar nova senha</label>
            <input class="form-control" type="password" id="password_confirmation" name="password_confirmation" required>
        </div>
        <button type="submit" class="btn btn-primary btn-block btn-lg">Redefinir senha</button>
    </form>
<?php $this->stop(); ?>

<?php $this->start('links'); ?>
    <a href="<?= url('/login') ?>">Voltar ao login</a>
<?php $this->stop(); ?>
