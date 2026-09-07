<?php
/** @var \App\Core\View $this */
/** @var array $order */ /** @var array $upsell */ /** @var array $offer */ /** @var float $price */
$this->extend('layouts.checkout');
?>
<?php $this->start('content'); ?>
<div class="card"><div class="card-body text-center" style="padding:2rem">
    <div class="badge badge-purple mb-2">Oferta unica</div>
    <h1 style="font-size:1.5rem"><?= e($upsell['title']) ?></h1>
    <p class="text-muted"><?= e($upsell['description'] ?: $offer['description']) ?></p>
    <div style="font-size:2.2rem;font-weight:800;color:var(--brand-600);margin:1rem 0"><?= money($price) ?></div>

    <form method="POST" action="<?= url('/upsell/' . $order['id'] . '/aceitar') ?>">
        <?= csrf_field() ?>
        <button class="btn btn-primary btn-block btn-lg" type="submit">SIM! Quero adicionar</button>
    </form>
    <form method="POST" action="<?= url('/upsell/' . $order['id'] . '/recusar') ?>" class="mt-2">
        <?= csrf_field() ?>
        <button class="btn btn-ghost btn-block" type="submit">Nao, obrigado. Continuar sem isso.</button>
    </form>
    <p class="text-sm text-muted mt-2">Sua compra anterior ja esta garantida.</p>
</div></div>
<?php $this->stop(); ?>
