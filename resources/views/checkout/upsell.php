<?php
/** @var \App\Core\View $this */
/** @var array $order */ /** @var array $upsell */ /** @var array $offer */ /** @var float $price */
$this->extend('layouts.checkout');
?>
<?php $this->start('content'); ?>
<div class="alert alert-success" style="display:flex;align-items:center;gap:.5rem;justify-content:center">
    <?php $this->partial('partials.icon', ['name' => 'check-circle', 'size' => 18]); ?>
    <span>Sua compra foi confirmada! Antes de continuar, uma oferta so pra voce.</span>
</div>
<div class="card"><div class="card-body text-center" style="padding:2rem">
    <div class="badge badge-purple mb-2">Oferta unica — nao aparece de novo</div>
    <h1 style="font-size:1.5rem"><?= e($upsell['title']) ?></h1>
    <p class="text-muted"><?= e($upsell['description'] ?: $offer['description']) ?></p>
    <div style="font-size:2.4rem;font-weight:900;color:var(--brand-600);margin:1rem 0;letter-spacing:-.02em"><?= money($price) ?></div>

    <form method="POST" action="<?= url('/upsell/' . $order['id'] . '/aceitar') ?>">
        <?= csrf_field() ?>
        <button class="btn btn-primary btn-block btn-lg btn-glow" type="submit">Sim, quero adicionar por <?= money($price) ?></button>
    </form>
    <form method="POST" action="<?= url('/upsell/' . $order['id'] . '/recusar') ?>" class="mt-2">
        <?= csrf_field() ?>
        <button class="btn btn-ghost btn-block" type="submit">Nao, obrigado. Continuar sem isso.</button>
    </form>
    <p class="text-sm text-muted mt-2">Sua compra anterior ja esta garantida — isto e um acrescimo opcional.</p>
</div></div>
<?php $this->stop(); ?>
