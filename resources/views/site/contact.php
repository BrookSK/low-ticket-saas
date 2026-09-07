<?php
/** @var \App\Core\View $this */
$this->extend('layouts.site');
?>
<?php $this->start('content'); ?>
<section class="section">
    <div class="container container-sm">
        <div class="section-head"><h2>Fale com a gente</h2><p>Tire suas duvidas ou envie uma sugestao.</p></div>
        <div class="card"><div class="card-body">
            <?php $this->partial('partials.flash'); ?>
            <form method="POST" action="<?= url('/contato') ?>">
                <?= csrf_field() ?>
                <div class="form-group"><label class="form-label">Nome</label><input class="form-control" name="name" value="<?= e(old('name')) ?>" required></div>
                <div class="form-group"><label class="form-label">E-mail</label><input class="form-control" type="email" name="email" value="<?= e(old('email')) ?>" required></div>
                <div class="form-group"><label class="form-label">Mensagem</label><textarea class="form-control" name="message" required><?= e(old('message')) ?></textarea></div>
                <button class="btn btn-primary btn-block" type="submit">Enviar mensagem</button>
            </form>
        </div></div>
    </div>
</section>
<?php $this->stop(); ?>
