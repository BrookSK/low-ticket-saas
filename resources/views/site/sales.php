<?php
/** @var \App\Core\View $this */
/** @var array $product */ /** @var array $page */ /** @var float $price */ /** @var ?float $oldPrice */
/** @var string $checkoutUrl */ /** @var ?string $campaign */
$this->extend('layouts.site');
$features = $product['features'] ? (array) json_decode($product['features'], true) : [];
$icon = fn($n, $s = 24) => $this->partial('partials.icon', ['name' => $n, 'size' => $s]);
?>
<?php $this->start('content'); ?>

<!-- HERO -->
<section class="hero">
    <div class="container">
        <span class="eyebrow"><span class="dot"></span> <?= e($page['eyebrow']) ?></span>
        <h1><?= $page['hero_title'] /* pode conter <span class=grad> */ ?></h1>
        <p class="sub"><?= e($page['hero_sub']) ?></p>
        <div class="cta-row">
            <a class="btn btn-primary btn-lg btn-glow" href="<?= e($checkoutUrl) ?>"><?= e($page['hero_cta']) ?></a>
            <a class="btn btn-ghost btn-lg" href="#como-funciona">Ver como funciona</a>
        </div>
        <div class="price-inline">
            <?php if ($oldPrice): ?><span class="from">de R$ <?= number_format($oldPrice, 2, ',', '.') ?></span><?php endif; ?>
            <span class="now">R$ <?= number_format($price, 2, ',', '.') ?></span>
            <span class="tag-line">pagamento unico · acesso imediato</span>
        </div>

        <div class="stats-band reveal">
            <?php foreach ($page['stats'] as [$n, $l]): ?>
                <div class="stat"><div class="n"><?= e($n) ?></div><div class="l"><?= e($l) ?></div></div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- BENEFICIOS -->
<section class="section alt" id="beneficios">
    <div class="container">
        <div class="section-head">
            <div class="kicker">Por que voce vai amar</div>
            <h2><?= e($page['benefits_title']) ?></h2>
        </div>
        <div class="benefits">
            <?php foreach ($page['benefits'] as [$ic, $t, $d]): ?>
                <div class="benefit reveal">
                    <div class="ico"><?= $icon($ic, 26) ?></div>
                    <h3><?= e($t) ?></h3>
                    <p><?= e($d) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- COMO FUNCIONA -->
<section class="section" id="como-funciona">
    <div class="container">
        <div class="section-head"><div class="kicker">Simples assim</div><h2>Em 3 passos voce ja esta usando</h2></div>
        <div class="steps">
            <div class="step reveal"><div class="num">1</div><h3>Crie sua conta</h3><p>Cadastro rapido. Voce comeca a usar em menos de 1 minuto.</p></div>
            <div class="step reveal"><div class="num">2</div><h3>Use a ferramenta</h3><p>Interface simples e direta. Sem manual, sem curva de aprendizado.</p></div>
            <div class="step reveal"><div class="num">3</div><h3>Tenha resultado</h3><p>Feche mais, cobre certo e organize o seu negocio de verdade.</p></div>
        </div>
    </div>
</section>

<!-- FEATURE SPLIT -->
<section class="section alt">
    <div class="container">
        <div class="feature-split">
            <div class="feature-media">
                <div class="mock">
                    <div class="bar"><span></span><span></span><span></span><div class="url"></div></div>
                    <div class="body">
                        <span class="pill"><?= $icon('check', 13) ?> Pronto e aprovado</span>
                        <div class="line"></div><div class="line short"></div>
                        <div class="line"></div><div class="line short"></div>
                        <div class="line total"></div>
                    </div>
                </div>
            </div>
            <div class="feature-copy">
                <div class="kicker"><?= e($page['eyebrow']) ?></div>
                <h2><?= e($page['feature_title']) ?></h2>
                <ul>
                    <?php foreach ($page['feature_points'] as $pt): ?>
                        <li><?= e($pt) ?></li>
                    <?php endforeach; ?>
                </ul>
                <a class="btn btn-primary btn-glow" href="<?= e($checkoutUrl) ?>"><?= e($page['hero_cta']) ?></a>
            </div>
        </div>
    </div>
</section>

<!-- DEPOIMENTOS -->
<section class="section">
    <div class="container">
        <div class="section-head"><div class="kicker">Quem usa, aprova</div><h2>Resultados de quem vive disso</h2></div>
        <div class="testimonials">
            <div class="testimonial reveal">
                <div class="stars"><?= str_repeat($icon('star', 16), 5) ?></div>
                <p>"Fecho mais servicos porque mando o orcamento na hora. O cliente ve que sou profissional."</p>
                <div class="who"><span class="av">J</span><div><strong>Joao</strong><div class="text-sm text-muted">Eletricista · SP</div></div></div>
            </div>
            <div class="testimonial reveal">
                <div class="stars"><?= str_repeat($icon('star', 16), 5) ?></div>
                <p>"Descobri que cobrava barato demais. Se pagou no primeiro servico que refiz o preco."</p>
                <div class="who"><span class="av">M</span><div><strong>Marcia</strong><div class="text-sm text-muted">Confeiteira · MG</div></div></div>
            </div>
            <div class="testimonial reveal">
                <div class="stars"><?= str_repeat($icon('star', 16), 5) ?></div>
                <p>"Simples de usar e me poupa horas todo mes. Recomendo de olhos fechados."</p>
                <div class="who"><span class="av">R</span><div><strong>Rafael</strong><div class="text-sm text-muted">Marceneiro · PR</div></div></div>
            </div>
        </div>
    </div>
</section>

<!-- OFERTA / CTA FINAL (um unico produto) -->
<section class="section" id="oferta">
    <div class="container">
        <div class="offer-card">
            <div class="offer-left">
                <div class="kicker">Oferta de lancamento</div>
                <h2><?= e($product['name']) ?></h2>
                <p class="text-muted"><?= e($product['description']) ?></p>
                <ul class="offer-list">
                    <?php foreach ($features as $f): ?>
                        <li><?= $icon('check', 18) ?> <span><?= e($f) ?></span></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="offer-right">
                <?php if ($oldPrice): ?><div class="old-price">de R$ <?= number_format($oldPrice, 2, ',', '.') ?></div><?php endif; ?>
                <div class="price"><small>R$</small> <?= number_format($price, 2, ',', '.') ?></div>
                <div class="once">pagamento unico<?= $oldPrice ? ' · oferta por tempo limitado' : '' ?></div>
                <a class="btn btn-primary btn-lg btn-glow btn-block mt-2" href="<?= e($checkoutUrl) ?>"><?= e($page['hero_cta']) ?></a>
                <div class="offer-badges">
                    <span><?= $icon('lock', 15) ?> Pagamento seguro</span>
                    <span><?= $icon('zap', 15) ?> Acesso imediato</span>
                </div>
            </div>
        </div>
        <div class="guarantee">
            <div class="g-ico"><?= $icon('shield', 28) ?></div>
            <div><strong>Risco zero.</strong> Nao guardamos dados do seu cartao e voce pode exportar ou excluir seus dados quando quiser.</div>
        </div>
    </div>
</section>

<?php $this->stop(); ?>
<?php $this->start('scripts'); ?>
<script>
if (typeof gtag === 'function') { gtag('event', 'page_view'); }
(function(){
    var els = document.querySelectorAll('.reveal');
    if (!('IntersectionObserver' in window)) { els.forEach(function(e){e.classList.add('in');}); return; }
    var io = new IntersectionObserver(function(entries){
        entries.forEach(function(en){ if(en.isIntersecting){ en.target.classList.add('in'); io.unobserve(en.target); } });
    }, {threshold: .12});
    els.forEach(function(e){ io.observe(e); });
})();
</script>
<?php $this->stop(); ?>
