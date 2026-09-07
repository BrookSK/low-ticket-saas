<?php
/** @var \App\Core\View $this */
/** @var array $order */
$this->extend('layouts.checkout');
$approved = $order['status'] === 'approved';
?>
<?php $this->start('content'); ?>
<div class="card"><div class="card-body text-center" style="padding:2.5rem">
    <div style="font-size:3rem"><?= $approved ? '✅' : '⏳' ?></div>
    <h1 style="font-size:1.5rem"><?= $approved ? 'Pagamento aprovado!' : 'Recebemos seu pedido' ?></h1>
    <p class="text-muted">
        <?= $approved
            ? 'Seu acesso ja esta liberado. Aproveite!'
            : 'Assim que o pagamento for confirmado, seu acesso sera liberado automaticamente.' ?>
    </p>
    <p class="text-sm text-muted">Pedido: <?= e($order['reference']) ?> · Total: <?= money($order['total']) ?></p>
    <a class="btn btn-primary btn-lg mt-2" href="<?= url('/dashboard') ?>">Ir para o painel</a>
</div></div>
<?php $this->stop(); ?>

<?php $this->start('scripts'); ?>
<?php if ($order['status'] === 'approved'): ?>
<script>
// Evento de conversao purchase (se GA/Ads/Pixel configurados no cliente).
if (typeof gtag === 'function') {
    gtag('event', 'purchase', {
        transaction_id: '<?= e($order['reference']) ?>',
        value: <?= (float) $order['total'] ?>,
        currency: 'BRL'
    });
}
if (typeof fbq === 'function') {
    fbq('track', 'Purchase', {value: <?= (float) $order['total'] ?>, currency: 'BRL'});
}
</script>
<?php endif; ?>
<?php $this->stop(); ?>
