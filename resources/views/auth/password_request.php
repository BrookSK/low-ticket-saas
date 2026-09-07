<?php
/** @var \App\Core\View $this */
$this->extend('layouts.auth');
$title = 'Recuperar senha';
?>
<?php $this->start('content'); ?>
    <h1 style="font-size:1.4rem;text-align:center">Recuperar senha</h1>
    <p class="text-muted text-center mb-3">Informe seu e-mail e enviaremos as instrucoes.</p>

    <form method="POST" action="<?= url('/esqueci-senha') ?>">
        <?= csrf_field() ?>
        <div class="form-group">
            <label class="form-label" for="email">E-mail</label>
            <input class="form-control" type="email" id="email" name="email" value="<?= e(old('email')) ?>" required autofocus>
        </div>
        <button type="submit" class="btn btn-primary btn-block btn-lg">Enviar instrucoes</button>
    </form>
<?php $this->stop(); ?>

<?php $this->start('links'); ?>
    <a href="<?= url('/login') ?>">Voltar ao login</a>
<?php $this->stop(); ?>
