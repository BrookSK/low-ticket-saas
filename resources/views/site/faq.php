<?php
/** @var \App\Core\View $this */
/** @var array $faqs */
$this->extend('layouts.site');
?>
<?php $this->start('content'); ?>
<section class="page-hero">
    <div class="container">
        <div class="kicker">Central de ajuda</div>
        <h1>Perguntas frequentes</h1>
        <p>Tudo o que você precisa saber antes de começar. Não achou o que procurava? É só falar com a gente.</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="faq-list">
            <?php foreach ($faqs as [$q, $a]): ?>
                <div class="faq-item"><h3><span class="q">P.</span> <?= e($q) ?></h3><p><?= e($a) ?></p></div>
            <?php endforeach; ?>
        </div>
        <div class="cta-final" style="margin-top:3rem">
            <h2>Ainda com dúvidas?</h2>
            <p>Crie sua conta grátis e teste na prática — leva menos de 1 minuto.</p>
            <div class="flex gap-1 justify-center mt-2" style="flex-wrap:wrap">
                <a class="btn btn-primary btn-lg" href="<?= url('/cadastro') ?>">Começar agora</a>
                <a class="btn btn-ghost btn-lg" href="<?= url('/contato') ?>" style="background:rgba(255,255,255,.15);color:#fff;border-color:rgba(255,255,255,.3)">Falar com a gente</a>
            </div>
        </div>
    </div>
</section>
<?php $this->stop(); ?>
