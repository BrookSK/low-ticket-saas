<?php
/** @var \App\Core\View $this */
/** @var array $products */ /** @var array $hero */ /** @var ?string $campaign */
$this->extend('layouts.site');
$ctaUrl = url('/cadastro' . ($campaign ? '?utm_campaign=' . urlencode($campaign) : ''));
// Ordena produtos para destacar o plano completo.
usort($products, fn($a, $b) => $a['sort_order'] <=> $b['sort_order']);
?>
<?php $this->start('content'); ?>

<!-- HERO -->
<section class="hero">
    <div class="container">
        <span class="eyebrow">Feito para MEIs, autonomos e pequenos negocios</span>
        <h1><?= e($hero['title']) ?></h1>
        <p class="sub"><?= e($hero['subtitle']) ?></p>
        <div class="cta-row">
            <a class="btn btn-primary btn-lg" href="<?= e($ctaUrl) ?>">Comecar agora</a>
            <a class="btn btn-ghost btn-lg" href="#como-funciona">Ver como funciona</a>
        </div>
        <div class="trust">Sem instalar nada · Funciona no celular · Comece em 1 minuto</div>
    </div>
</section>

<!-- BENEFICIOS -->
<section class="section">
    <div class="container">
        <div class="section-head"><h2>Tudo que voce precisa para parecer profissional</h2>
            <p>Economize tempo, cobre o valor certo e nunca mais perca uma cobranca.</p></div>
        <div class="benefits">
            <div class="benefit"><div class="ico">📄</div><h3>Orcamentos em minutos</h3><p>Monte orcamentos bonitos, gere PDF e envie pelo WhatsApp na hora.</p></div>
            <div class="benefit"><div class="ico">🧮</div><h3>Saiba quanto cobrar</h3><p>O precificador calcula o preco ideal com base nos seus custos e margem.</p></div>
            <div class="benefit"><div class="ico">💰</div><h3>Organize o dinheiro</h3><p>Controle receitas, despesas e veja seu lucro real de forma simples.</p></div>
            <div class="benefit"><div class="ico">📲</div><h3>Envio pelo WhatsApp</h3><p>Compartilhe um link profissional e acompanhe quando o cliente aprova.</p></div>
            <div class="benefit"><div class="ico">👥</div><h3>Clientes organizados</h3><p>Guarde o historico de cada cliente e reaproveite orcamentos.</p></div>
            <div class="benefit"><div class="ico">📊</div><h3>Visao do negocio</h3><p>Acompanhe faturamento, contas a receber e a pagar num painel claro.</p></div>
        </div>
    </div>
</section>

<!-- COMO FUNCIONA -->
<section class="section alt" id="como-funciona">
    <div class="container">
        <div class="section-head"><h2>Como funciona</h2><p>Tres passos simples para comecar hoje.</p></div>
        <div class="steps">
            <div class="step"><div class="num">1</div><h3>Crie sua conta</h3><p>Cadastro rapido e gratuito. Sem cartao para comecar.</p></div>
            <div class="step"><div class="num">2</div><h3>Monte seu orcamento</h3><p>Adicione servicos, valores e condicoes. O total e calculado sozinho.</p></div>
            <div class="step"><div class="num">3</div><h3>Envie e feche</h3><p>Compartilhe pelo WhatsApp e receba a aprovacao do cliente.</p></div>
        </div>
    </div>
</section>

<!-- DEMONSTRACAO VISUAL -->
<section class="section">
    <div class="container">
        <div class="feature-split">
            <div class="feature-media">
                <div class="mock">
                    <div class="bar"><span></span><span></span><span></span></div>
                    <div class="body">
                        <div class="line"></div><div class="line short"></div>
                        <div class="line"></div><div class="line short"></div>
                        <div class="line total"></div>
                    </div>
                </div>
            </div>
            <div class="feature-copy">
                <h2>Orcamentos que passam confianca</h2>
                <p class="text-muted">Um documento limpo e profissional, com seus dados, os itens e o total. O cliente aprova com um clique.</p>
                <ul>
                    <li>Gere PDF com sua identidade</li>
                    <li>Link publico com aprovar/recusar</li>
                    <li>Status: enviado, visualizado, aprovado</li>
                </ul>
                <a class="btn btn-primary" href="<?= e($ctaUrl) ?>">Testar agora</a>
            </div>
        </div>
    </div>
</section>

<!-- PRECIFICADOR -->
<section class="section alt">
    <div class="container">
        <div class="feature-split reverse">
            <div class="feature-media">
                <div style="text-align:center">
                    <div class="text-muted text-sm">Preco recomendado</div>
                    <div style="font-size:2.6rem;font-weight:900;color:var(--brand-600)">R$ 500,00</div>
                    <div class="text-muted text-sm">Custo R$ 350 · Margem 30%</div>
                </div>
            </div>
            <div class="feature-copy">
                <h2>Pare de cobrar no chute</h2>
                <p class="text-muted">Informe seus custos e a margem desejada. Mostramos o preco minimo e o recomendado para voce nao sair no prejuizo.</p>
                <ul><li>Custo por hora e por servico</li><li>Preco minimo e recomendado</li><li>Margem conservadora e agressiva</li></ul>
                <a class="btn btn-primary" href="<?= e($ctaUrl) ?>">Descobrir meu preco</a>
            </div>
        </div>
    </div>
</section>

<!-- CONTROLE FINANCEIRO -->
<section class="section">
    <div class="container">
        <div class="feature-split">
            <div class="feature-media">
                <div style="width:100%">
                    <div class="flex justify-between mb-2"><span class="text-muted">Faturamento</span><strong>R$ 4.850</strong></div>
                    <div class="flex justify-between mb-2"><span class="text-muted">Despesas</span><strong style="color:var(--danger)">R$ 1.200</strong></div>
                    <div class="flex justify-between" style="font-size:1.3rem"><strong>Lucro</strong><strong style="color:var(--success)">R$ 3.650</strong></div>
                </div>
            </div>
            <div class="feature-copy">
                <h2>Saiba quanto realmente ganha</h2>
                <p class="text-muted">Registre entradas e saidas e veja seu lucro real. Nunca mais esqueca uma conta a receber.</p>
                <ul><li>Contas a receber e a pagar</li><li>Alertas de vencidas</li><li>Comparativo mensal</li></ul>
                <a class="btn btn-primary" href="<?= e($ctaUrl) ?>">Organizar minhas financas</a>
            </div>
        </div>
    </div>
</section>

<!-- DEPOIMENTOS -->
<section class="section alt">
    <div class="container">
        <div class="section-head"><h2>Quem usa, recomenda</h2></div>
        <div class="testimonials">
            <div class="testimonial"><div class="stars">★★★★★</div><p>"Fecho mais servicos porque envio o orcamento na hora, direto no WhatsApp."</p><div class="who"><span class="av">J</span><div><strong>Joao</strong><div class="text-sm text-muted">Eletricista</div></div></div></div>
            <div class="testimonial"><div class="stars">★★★★★</div><p>"Descobri que estava cobrando barato. O precificador mudou meu negocio."</p><div class="who"><span class="av">M</span><div><strong>Marcia</strong><div class="text-sm text-muted">Confeiteira</div></div></div></div>
            <div class="testimonial"><div class="stars">★★★★★</div><p>"Agora sei exatamente quanto entra e quanto sai. Simples de usar."</p><div class="who"><span class="av">R</span><div><strong>Rafael</strong><div class="text-sm text-muted">Marceneiro</div></div></div></div>
        </div>
    </div>
</section>

<!-- PLANOS -->
<section class="section" id="planos">
    <div class="container">
        <div class="section-head"><h2>Escolha como comecar</h2><p>Pague uma vez pelo que precisa. Sem mensalidade obrigatoria.</p></div>
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
                    <ul>
                        <?php foreach ($features as $f): ?><li><?= e($f) ?></li><?php endforeach; ?>
                    </ul>
                    <a class="btn <?= $featured ? 'btn-primary' : 'btn-ghost' ?> btn-block" href="<?= url('/checkout/' . $p['slug']) ?>">Comprar agora</a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- CTA FINAL -->
<section class="section">
    <div class="container">
        <div class="cta-final">
            <h2>Pronto para parecer mais profissional?</h2>
            <p>Crie seu primeiro orcamento hoje. Leva menos de 1 minuto.</p>
            <a class="btn btn-primary btn-lg mt-2" href="<?= e($ctaUrl) ?>">Comecar agora</a>
        </div>
    </div>
</section>

<?php $this->stop(); ?>
<?php $this->start('scripts'); ?>
<script>
if (typeof gtag === 'function') { gtag('event', 'page_view'); }
</script>
<?php $this->stop(); ?>
