<?php
/** @var \App\Core\View $this */
/** @var array|null $order */
$this->extend('layouts.checkout');
?>
<?php $this->start('content'); ?>
<div class="card"><div class="card-body text-center" style="padding:2.5rem">
    <div class="result-icon warn"><?php $this->partial('partials.icon', ['name' => 'bell', 'size' => 30]); ?></div>
    <h1 style="font-size:1.5rem">Nao conseguimos concluir o pagamento</h1>
    <p class="text-muted">Voce pode tentar novamente. Nenhuma cobranca foi confirmada.</p>
    <?php if ($order): ?>
        <?php $slug = $order['items'][0]['product_id'] ?? null; ?>
        <a class="btn btn-primary mt-2" href="<?= url('/dashboard') ?>">Voltar ao painel</a>
    <?php endif; ?>
</div></div>
<?php $this->stop(); ?>
