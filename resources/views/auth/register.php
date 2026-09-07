<?php
/** @var \App\Core\View $this */
$this->extend('layouts.auth');
$title = 'Criar conta';
?>
<?php $this->start('content'); ?>
    <h1 style="font-size:1.4rem;text-align:center">Criar sua conta</h1>
    <p class="text-muted text-center mb-3">Comece agora, leva menos de 1 minuto.</p>

    <form method="POST" action="<?= url('/cadastro') ?>">
        <?= csrf_field() ?>
        <div class="form-group">
            <label class="form-label" for="name">Nome</label>
            <input class="form-control" type="text" id="name" name="name" value="<?= e(old('name')) ?>" required autofocus>
        </div>
        <div class="form-group">
            <label class="form-label" for="email">E-mail</label>
            <input class="form-control" type="email" id="email" name="email" value="<?= e(old('email')) ?>" required>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label" for="phone">Telefone</label>
                <input class="form-control" type="text" id="phone" name="phone" value="<?= e(old('phone')) ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="whatsapp">WhatsApp</label>
                <input class="form-control" type="text" id="whatsapp" name="whatsapp" value="<?= e(old('whatsapp')) ?>">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label" for="password">Senha</label>
            <input class="form-control" type="password" id="password" name="password" required>
            <div class="form-hint">Minimo de 8 caracteres.</div>
        </div>
        <div class="form-group">
            <label class="form-label" for="password_confirmation">Confirmar senha</label>
            <input class="form-control" type="password" id="password_confirmation" name="password_confirmation" required>
        </div>
        <button type="submit" class="btn btn-primary btn-block btn-lg">Criar conta</button>
    </form>
<?php $this->stop(); ?>

<?php $this->start('links'); ?>
    Ja tem conta? <a href="<?= url('/login') ?>">Entrar</a>
<?php $this->stop(); ?>
