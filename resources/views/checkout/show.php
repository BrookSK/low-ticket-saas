<?php
/** @var \App\Core\View $this */
/** @var array $product */ /** @var float $price */ /** @var array|null $user */ /** @var bool $paymentConfigured */
$this->extend('layouts.checkout');
$features = $product['features'] ? (array) json_decode($product['features'], true) : [];
?>
<?php $this->start('content'); ?>
<div class="card">
    <div class="card-body">
        <h1 style="font-size:1.4rem"><?= e($product['name']) ?></h1>
        <p class="text-muted"><?= e($product['description']) ?></p>
        <?php if ($features): ?>
            <ul style="padding-left:1.1rem;margin:1rem 0">
                <?php foreach ($features as $f): ?><li><?= e($f) ?></li><?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if (!$paymentConfigured): ?>
            <div class="alert alert-warning">O pagamento ainda nao foi configurado. Volte em breve.</div>
        <?php endif; ?>

        <form method="POST" action="<?= url('/checkout/' . $product['slug']) ?>" id="checkoutForm">
            <?= csrf_field() ?>
            <?php if (!$user): ?>
                <div class="form-group"><label class="form-label">Seu e-mail</label>
                    <input class="form-control" type="email" name="email" required></div>
            <?php endif; ?>

            <div class="form-group"><label class="form-label">Cupom de desconto</label>
                <div class="flex gap-1">
                    <input class="form-control" name="coupon" id="coupon" placeholder="Opcional">
                    <button class="btn btn-ghost" type="button" id="applyCoupon">Aplicar</button>
                </div>
                <div class="form-hint" id="couponMsg"></div>
            </div>

            <div class="flex justify-between mt-2" style="font-size:1.3rem">
                <strong>Total</strong>
                <strong style="color:var(--brand-600)" id="total"><?= money($price) ?></strong>
            </div>

            <button class="btn btn-primary btn-block btn-lg mt-3" type="submit" <?= $paymentConfigured ? '' : 'disabled' ?>>
                Pagar <?= money($price) ?>
            </button>
            <p class="text-center text-muted text-sm mt-2">Pagamento seguro. Nao armazenamos dados do seu cartao.</p>
        </form>
    </div>
</div>
<?php $this->stop(); ?>

<?php $this->start('scripts'); ?>
<script>
document.getElementById('applyCoupon')?.addEventListener('click', function(){
    var code = document.getElementById('coupon').value;
    fetch('<?= url('/cupom/validar') ?>', {
        method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'<?= e(csrf_token()) ?>'},
        body: JSON.stringify({coupon: code, slug: '<?= e($product['slug']) ?>'})
    }).then(r=>r.json()).then(function(d){
        var msg = document.getElementById('couponMsg');
        if(d.valid){ msg.textContent = 'Cupom aplicado! Desconto de ' + window.formatBRL(d.discount); msg.style.color='var(--success)';
            document.getElementById('total').textContent = window.formatBRL(d.total); }
        else { msg.textContent = d.message || 'Cupom invalido.'; msg.style.color='var(--danger)'; }
    });
});
</script>
<?php $this->stop(); ?>
