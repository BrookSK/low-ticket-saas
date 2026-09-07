<?php
/** @var \App\Core\View $this */
/** @var array $products */
$this->extend('layouts.site');
usort($products, fn($a, $b) => $a['sort_order'] <=> $b['sort_order']);
?>
<?php $this->start('content'); ?>
<section class="section">
    <div class="container">
        <div class="section-head"><h2>Precos simples e transparentes</h2><p>Pague uma vez pelo que precisa. Assinaturas em breve.</p></div>
        <div class="pricing-grid">
            <?php foreach ($products as $p):
                $features = $p['features'] ? (array) json_decode($p['features'], true) : [];
                $featured = $p['slug'] === 'plano-completo';
                $price = $p['promo_price'] && (float) $p['promo_price'] > 0 ? $p['promo_price'] : $p['price'];
            ?>
                <div class="price-card <?= $featured ? 'featured' : '' ?>">
                    <?php if ($featured): ?><span class="tag">Mais completo</span><?php endif; ?>
                    <h3><?= e($p['name']) ?></h3>
                    <div class="price"><small>R$</small> <?= number_format((float) $price, 2, ',', '.') ?></div>
                    <p class="text-muted text-sm"><?= e($p['description']) ?></p>
                    <ul><?php foreach ($features as $f): ?><li><?= e($f) ?></li><?php endforeach; ?></ul>
                    <a class="btn <?= $featured ? 'btn-primary' : 'btn-ghost' ?> btn-block" href="<?= url('/checkout/' . $p['slug']) ?>">Comprar agora</a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php $this->stop(); ?>
