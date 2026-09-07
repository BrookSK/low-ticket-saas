<?php
/** @var \App\Core\View $this */
/** @var array $products */ /** @var array $hero */ /** @var ?string $campaign */
$this->extend('layouts.site');
$ctaUrl = url('/cadastro' . ($campaign ? '?utm_campaign=' . urlencode($campaign) : ''));
usort($products, fn($a, $b) => $a['sort_order'] <=> $b['sort_order']);
$appName = e(setting('app.name', 'Meu Orçamento'));
?>
<?php $this->start('content'); ?>

<!-- HERO -->
<section class="hero">
    <div class="container">
        <span class="eyebrow"><span class="dot"></span> Novo jeito de orçar, cobrar e organizar</span>
        <h1>Envie orçamentos que <span class="grad">fecham negócio</span> e saiba o preço certo de cobrar</h1>
        <p class="sub">Monte um orçamento profissional em 2 minutos, mande pelo WhatsApp e acompanhe quando o cliente aprova. Tudo num só lugar, sem planilha e sem complicação.</p>
        <div class="cta-row">
            <a class="btn btn-primary btn-lg btn-glow" href="<?= e($ctaUrl) ?>">Começar agora — é grátis</a>
            <a class="btn btn-ghost btn-lg" href="#como-funciona">Ver como funciona</a>
        </div>
        <div class="trust">
            <span>✓ Sem instalar nada</span>
            <span>✓ Funciona no celular</span>
            <span>✓ Pronto em 1 minuto</span>
        </div>

        <div class="stats-band reveal">
            <div class="stat"><div class="n">2 min</div><div class="l">para criar um orçamento</div></div>
            <div class="stat"><div class="n">+30%</div><div class="l">de chance de fechar cobrando certo</div></div>
            <div class="stat"><div class="n">R$ 0</div><div class="l">para começar a usar hoje</div></div>
        </div>
    </div>
</section>

<!-- PROVA SOCIAL / NICHOS -->
<section class="section" style="padding-top:2.5rem;padding-bottom:2.5rem">
    <div class="container">
        <p class="text-center text-muted text-sm mb-2" style="text-transform:uppercase;letter-spacing:.1em">Feito para quem vive de prestar serviço</p>
        <div class="logos">
            <span class="chip">⚡ Eletricistas</span>
            <span class="chip">🔨 Marceneiros</span>
            <span class="chip">🎨 Pintores</span>
            <span class="chip">📷 Fotógrafos</span>
            <span class="chip">🧰 Técnicos</span>
            <span class="chip">💼 Autônomos e MEIs</span>
        </div>
    </div>
</section>

<!-- BENEFICIOS -->
<section class="section alt">
    <div class="container">
        <div class="section-head">
            <div class="kicker">Por que usar</div>
            <h2>Tudo que você precisa para vender mais e trabalhar menos</h2>
            <p>Economize tempo, passe profissionalismo e nunca mais deixe dinheiro na mesa.</p>
        </div>
        <div class="benefits">
            <div class="benefit reveal"><div class="ico">📄</div><h3>Orçamentos em minutos</h3><p>Monte, gere PDF com a sua marca e envie pelo WhatsApp na hora. O cliente aprova com um clique.</p></div>
            <div class="benefit reveal"><div class="ico">🧮</div><h3>Saiba quanto cobrar</h3><p>O precificador calcula o preço ideal com base nos seus custos e na margem que você quer ganhar.</p></div>
            <div class="benefit reveal"><div class="ico">💰</div><h3>Dinheiro organizado</h3><p>Receitas, despesas e lucro real num painel simples. Chega de adivinhar se o mês fechou no azul.</p></div>
            <div class="benefit reveal"><div class="ico">📲</div><h3>Envio pelo WhatsApp</h3><p>Compartilhe um link profissional e receba a notificação assim que o cliente visualizar e aprovar.</p></div>
            <div class="benefit reveal"><div class="ico">👥</div><h3>Clientes na palma da mão</h3><p>Histórico completo de cada cliente e orçamentos reaproveitáveis. Refaça uma proposta em segundos.</p></div>
            <div class="benefit reveal"><div class="ico">🔔</div><h3>Nunca esqueça uma cobrança</h3><p>Contas a receber, a pagar e alertas de vencimento. Seu financeiro no controle, sem esforço.</p></div>
        </div>
    </div>
</section>

<!-- COMO FUNCIONA -->
<section class="section" id="como-funciona">
    <div class="container">
        <div class="section-head"><div class="kicker">Simples assim</div><h2>Do zero ao orçamento enviado em 3 passos</h2></div>
        <div class="steps">
            <div class="step reveal"><div class="num">1</div><h3>Crie sua conta</h3><p>Cadastro rápido e gratuito. Sem cartão, sem burocracia.</p></div>
            <div class="step reveal"><div class="num">2</div><h3>Monte o orçamento</h3><p>Adicione serviços e valores. O total, desconto e prazo calculam sozinhos.</p></div>
            <div class="step reveal"><div class="num">3</div><h3>Envie e feche</h3><p>Manda pelo WhatsApp e recebe o aviso quando o cliente aprova. Simples.</p></div>
        </div>
    </div>
</section>

<!-- DEMONSTRACAO: ORCAMENTO -->
<section class="section alt">
    <div class="container">
        <div class="feature-split">
            <div class="feature-media">
                <div class="mock">
                    <div class="bar"><span></span><span></span><span></span><div class="url"></div></div>
                    <div class="body">
                        <span class="pill">✓ Aprovado pelo cliente</span>
                        <div class="line"></div><div class="line short"></div>
                        <div class="line"></div><div class="line short"></div>
                        <div class="line total"></div>
                    </div>
                </div>
            </div>
            <div class="feature-copy">
                <div class="kicker">Gerador de orçamentos</div>
                <h2>Orçamentos que passam confiança</h2>
                <p class="lead">Um documento limpo e profissional, com os seus dados, os itens e o total calculado. O cliente abre no celular e aprova na hora.</p>
                <ul>
                    <li>PDF profissional com a sua identidade</li>
                    <li>Link público com botões de aprovar e recusar</li>
                    <li>Status em tempo real: enviado, visualizado, aprovado</li>
                </ul>
                <a class="btn btn-primary" href="<?= e($ctaUrl) ?>">Criar meu primeiro orçamento</a>
            </div>
        </div>
    </div>
</section>

<!-- DEMONSTRACAO: PRECIFICADOR -->
<section class="section">
    <div class="container">
        <div class="feature-split reverse">
            <div class="feature-media">
                <div class="price-hero">
                    <div class="text-muted text-sm">Preço recomendado</div>
                    <div class="big">R$ 500,00</div>
                    <div class="text-muted text-sm">Custo R$ 350 · Margem 30% · Lucro R$ 150</div>
                </div>
            </div>
            <div class="feature-copy">
                <div class="kicker">Precificador inteligente</div>
                <h2>Pare de cobrar no chute</h2>
                <p class="lead">Informe seus custos e a margem desejada. Em segundos você vê o preço mínimo para não ter prejuízo e o recomendado para lucrar de verdade.</p>
                <ul><li>Custo por hora e por serviço</li><li>Preço mínimo, recomendado e agressivo</li><li>Descubra quanto realmente sobra no seu bolso</li></ul>
                <a class="btn btn-primary" href="<?= e($ctaUrl) ?>">Descobrir meu preço ideal</a>
            </div>
        </div>
    </div>
</section>

<!-- DEMONSTRACAO: FINANCEIRO -->
<section class="section alt">
    <div class="container">
        <div class="feature-split">
            <div class="feature-media">
                <div style="width:100%">
                    <div class="flex justify-between mb-2"><span class="text-muted">Faturamento do mês</span><strong>R$ 4.850</strong></div>
                    <div class="flex justify-between mb-2"><span class="text-muted">Despesas</span><strong style="color:var(--danger)">R$ 1.200</strong></div>
                    <div class="flex justify-between" style="font-size:1.4rem;border-top:1px solid var(--line);padding-top:.75rem"><strong>Lucro real</strong><strong style="color:var(--success)">R$ 3.650</strong></div>
                </div>
            </div>
            <div class="feature-copy">
                <div class="kicker">Kit financeiro</div>
                <h2>Saiba exatamente quanto você ganha</h2>
                <p class="lead">Registre o que entra e o que sai e veja seu lucro real na hora. Nunca mais esqueça uma conta a receber ou pague algo em atraso.</p>
                <ul><li>Contas a receber e a pagar</li><li>Alertas de vencimento</li><li>Comparativo mês a mês</li></ul>
                <a class="btn btn-primary" href="<?= e($ctaUrl) ?>">Organizar minhas finanças</a>
            </div>
        </div>
    </div>
</section>

<!-- DEPOIMENTOS -->
<section class="section">
    <div class="container">
        <div class="section-head"><div class="kicker">Quem usa, aprova</div><h2>Resultados de quem vive disso</h2></div>
        <div class="testimonials">
            <div class="testimonial reveal"><div class="stars">★★★★★</div><p>"Fecho mais serviços porque mando o orçamento na hora, direto no WhatsApp. O cliente vê que sou profissional."</p><div class="who"><span class="av">J</span><div><strong>João</strong><div class="text-sm text-muted">Eletricista · SP</div></div></div></div>
            <div class="testimonial reveal"><div class="stars">★★★★★</div><p>"Descobri que estava cobrando barato demais. Só o precificador já pagou o investimento no primeiro serviço."</p><div class="who"><span class="av">M</span><div><strong>Márcia</strong><div class="text-sm text-muted">Confeiteira · MG</div></div></div></div>
            <div class="testimonial reveal"><div class="stars">★★★★★</div><p>"Agora sei quanto entra e quanto sai sem abrir planilha. Simples de usar e me poupa horas todo mês."</p><div class="who"><span class="av">R</span><div><strong>Rafael</strong><div class="text-sm text-muted">Marceneiro · PR</div></div></div></div>
        </div>
    </div>
</section>

<!-- PLANOS -->
<section class="section alt" id="planos">
    <div class="container">
        <div class="section-head"><div class="kicker">Preços honestos</div><h2>Comece pelo que faz sentido pra você</h2><p>Pagamento único. Sem mensalidade obrigatória, sem pegadinha.</p></div>
        <div class="pricing-grid">
            <?php foreach ($products as $p):
                $features = $p['features'] ? (array) json_decode($p['features'], true) : [];
                $featured = $p['slug'] === 'plano-completo';
                $price = $p['promo_price'] && (float) $p['promo_price'] > 0 ? $p['promo_price'] : $p['price'];
            ?>
                <div class="price-card <?= $featured ? 'featured' : '' ?>">
                    <?php $hasPromo = $p['promo_price'] && (float) $p['promo_price'] > 0 && (float) $p['promo_price'] < (float) $p['price']; ?>
                    <?php if ($featured): ?><span class="tag">⭐ Mais escolhido</span><?php endif; ?>
                    <h3><?= e($p['name']) ?></h3>
                    <?php if ($hasPromo): ?><div class="old-price">de R$ <?= number_format((float) $p['price'], 2, ',', '.') ?></div><?php endif; ?>
                    <div class="price"><small>R$</small> <?= number_format((float) $price, 2, ',', '.') ?></div>
                    <div class="once">pagamento único<?= $hasPromo ? ' · oferta por tempo limitado' : '' ?></div>
                    <p class="text-muted text-sm mt-1"><?= e($p['description']) ?></p>
                    <ul>
                        <?php foreach ($features as $f): ?><li><?= e($f) ?></li><?php endforeach; ?>
                    </ul>
                    <a class="btn <?= $featured ? 'btn-primary btn-glow' : 'btn-ghost' ?> btn-block" href="<?= url('/checkout/' . $p['slug']) ?>">Quero esse</a>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="guarantee">
            <div class="g-ico">🛡️</div>
            <div><strong>Risco zero.</strong> Você começa de graça e só paga quando quiser desbloquear mais recursos. Seus dados são seus e você pode exportar ou excluir quando quiser.</div>
        </div>
    </div>
</section>

<!-- FAQ resumido -->
<section class="section">
    <div class="container">
        <div class="section-head"><div class="kicker">Dúvidas</div><h2>Perguntas rápidas</h2></div>
        <div class="faq-list">
            <div class="faq-item"><h3>Preciso instalar alguma coisa?</h3><p>Não. Funciona direto no navegador, no computador ou no celular.</p></div>
            <div class="faq-item"><h3>Dá pra enviar pelo WhatsApp?</h3><p>Sim! Com um clique você compartilha um link profissional do orçamento.</p></div>
            <div class="faq-item"><h3>Consigo cancelar quando quiser?</h3><p>Sim. Você compra o que precisa e continua com acesso ao que adquiriu.</p></div>
        </div>
        <div class="text-center mt-3"><a href="<?= url('/faq') ?>">Ver todas as perguntas →</a></div>
    </div>
</section>

<!-- CTA FINAL -->
<section class="section">
    <div class="container">
        <div class="cta-final">
            <h2>Pronto para fechar mais orçamentos?</h2>
            <p>Crie sua conta grátis e envie seu primeiro orçamento hoje. Leva menos de 1 minuto.</p>
            <a class="btn btn-primary btn-lg mt-2" href="<?= e($ctaUrl) ?>">Começar agora — é grátis</a>
        </div>
    </div>
</section>

<?php $this->stop(); ?>
<?php $this->start('scripts'); ?>
<script>
if (typeof gtag === 'function') { gtag('event', 'page_view'); }
// Animacao de entrada (reveal on scroll)
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
