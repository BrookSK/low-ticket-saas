<?php
/** @var \App\Core\View $this */
/** @var array $products */
$this->extend('layouts.site');
usort($products, fn($a, $b) => $a['sort_order'] <=> $b['sort_order']);
?>
<?php $this->start('content'); ?>
<section class="section">
    <div class="container">
        <div class="section-head">
            <div class="kicker">Preços</div>
            <h2>Simples, honesto e sem mensalidade obrigatória</h2>
            <p>Pague uma vez pelo que precisa. Comece de graça e desbloqueie mais quando fizer sentido.</p>
        </div>
        <div class="pricing-grid">
            <?php foreach ($products as $p):
                $features = $p['features'] ? (array) json_decode($p['features'], true) : [];
                $featured = $p['slug'] === 'plano-completo';
                $price = $p['promo_price'] && (float) $p['promo_price'] > 0 ? $p['promo_price'] : $p['price'];
            ?>
                <div class="price-card <?= $featured ? 'featured' : '' ?>">
                    <?php $hasPromo = $p['promo_price'] && (float) $p['promo_price'] > 0 && (float) $p['promo_price'] < (float) $p['price']; ?>
                    <?php if ($featured): ?><span class="tag">Mais escolhido</span><?php endif; ?>
                    <h3><?= e($p['name']) ?></h3>
                    <?php if ($hasPromo): ?><div class="old-price">de R$ <?= number_format((float) $p['price'], 2, ',', '.') ?></div><?php endif; ?>
                    <div class="price"><small>R$</small> <?= number_format((float) $price, 2, ',', '.') ?></div>
                    <div class="once">pagamento único<?= $hasPromo ? ' · oferta por tempo limitado' : '' ?></div>
                    <p class="text-muted text-sm mt-1"><?= e($p['description']) ?></p>
                    <ul><?php foreach ($features as $f): ?><li><?= e($f) ?></li><?php endforeach; ?></ul>
                    <a class="btn <?= $featured ? 'btn-primary btn-glow' : 'btn-ghost' ?> btn-block" href="<?= url('/checkout/' . $p['slug']) ?>">Quero esse</a>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="guarantee">
            <div class="g-ico"><?php $this->partial('partials.icon', ['name' => 'shield', 'size' => 28]); ?></div>
            <div><strong>Risco zero.</strong> Comece grátis e pague só quando quiser mais recursos. Seus dados são seus — exporte ou exclua quando quiser.</div>
        </div>
    </div>
</section>
<?php $this->stop(); ?>
