<?php
/** @var \App\Core\View $this */
$this->extend('layouts.site');
$support = setting('app.support_email', '');
$wa = preg_replace('/\D+/', '', (string) setting('whatsapp.phone_number', ''));
?>
<?php $this->start('content'); ?>
<section class="page-hero">
    <div class="container">
        <div class="kicker">Estamos por aqui</div>
        <h1>Fale com a gente</h1>
        <p>Dúvida, sugestão ou precisa de uma mãozinha? Respondemos rápido e com gente de verdade.</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="contact-grid">
            <div class="card"><div class="card-body">
                <h2 style="margin-top:0">Envie sua mensagem</h2>
                <?php $this->partial('partials.flash'); ?>
                <form method="POST" action="<?= url('/contato') ?>">
                    <?= csrf_field() ?>
                    <div class="form-group"><label class="form-label">Seu nome</label><input class="form-control" name="name" value="<?= e(old('name')) ?>" required></div>
                    <div class="form-group"><label class="form-label">Seu e-mail</label><input class="form-control" type="email" name="email" value="<?= e(old('email')) ?>" required></div>
                    <div class="form-group"><label class="form-label">Mensagem</label><textarea class="form-control" name="message" style="min-height:140px" required><?= e(old('message')) ?></textarea></div>
                    <button class="btn btn-primary btn-block btn-glow" type="submit">Enviar mensagem</button>
                </form>
            </div></div>

            <div class="contact-cards">
                <?php if ($support): ?>
                <div class="contact-card">
                    <div class="ic">✉️</div>
                    <div><h4>E-mail</h4><p><a href="mailto:<?= e($support) ?>"><?= e($support) ?></a></p></div>
                </div>
                <?php endif; ?>
                <?php if ($wa): ?>
                <div class="contact-card">
                    <div class="ic">💬</div>
                    <div><h4>WhatsApp</h4><p><a href="https://wa.me/<?= e($wa) ?>" target="_blank">Chamar no WhatsApp</a></p></div>
                </div>
                <?php endif; ?>
                <div class="contact-card">
                    <div class="ic">⚡</div>
                    <div><h4>Resposta rápida</h4><p>Costumamos responder em poucas horas nos dias úteis.</p></div>
                </div>
                <div class="contact-card">
                    <div class="ic">📚</div>
                    <div><h4>Central de dúvidas</h4><p><a href="<?= url('/faq') ?>">Ver perguntas frequentes</a></p></div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php $this->stop(); ?>
