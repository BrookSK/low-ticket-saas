<?php
/** @var \App\Core\View $this */
/** @var array $faqs */
$this->extend('layouts.site');
?>
<?php $this->start('content'); ?>
<section class="section">
    <div class="container">
        <div class="section-head"><h2>Perguntas frequentes</h2></div>
        <div class="faq-list">
            <?php foreach ($faqs as [$q, $a]): ?>
                <div class="faq-item"><h3><?= e($q) ?></h3><p><?= e($a) ?></p></div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-3"><a class="btn btn-primary" href="<?= url('/cadastro') ?>">Comecar agora</a></div>
    </div>
</section>
<?php $this->stop(); ?>
