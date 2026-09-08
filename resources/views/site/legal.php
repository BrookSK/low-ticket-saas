<?php
/** @var \App\Core\View $this */
/** @var string $heading */ /** @var string $type */
$this->extend('layouts.site');
$appName = setting('app.name', 'Meu Orçamento');
?>
<?php $this->start('content'); ?>
<section class="page-hero">
    <div class="container">
        <div class="kicker">Transparência</div>
        <h1><?= e($heading) ?></h1>
        <p>Escrito de forma clara, sem juridiquês desnecessário. Seus dados e sua confiança em primeiro lugar.</p>
    </div>
</section>

<section class="section">
    <div class="container prose">
        <span class="updated-badge"><?php $this->partial('partials.icon', ['name' => 'clock', 'size' => 14]); ?> Última atualização: <?= date('d/m/Y') ?></span>

        <?php if ($type === 'terms'): ?>
            <h3>1. Aceitacao</h3>
            <p>Ao usar o <?= e($appName) ?>, voce concorda com estes termos. Se nao concordar, nao utilize a plataforma.</p>
            <h3>2. Uso do servico</h3>
            <p>Voce e responsavel pelas informacoes que cadastra e pelo uso adequado das funcionalidades de orcamentos, financeiro e precificacao.</p>
            <h3>3. Pagamentos</h3>
            <p>As compras sao processadas por gateways de pagamento parceiros. Nao armazenamos dados de cartao.</p>
            <h3>4. Cancelamento</h3>
            <p>Voce pode encerrar sua conta a qualquer momento pelas configuracoes.</p>
            <h3>5. Limitacao de responsabilidade</h3>
            <p>O servico e fornecido "como esta". Calculos e sugestoes de preco sao referencias e nao substituem a sua decisao comercial.</p>

        <?php elseif ($type === 'privacy'): ?>
            <h3>1. Dados coletados</h3>
            <p>Coletamos apenas os dados necessarios para operar o servico: cadastro, dados de clientes/orcamentos e informacoes de uso.</p>
            <h3>2. Uso dos dados (LGPD)</h3>
            <p>Usamos seus dados para prestar o servico, melhorar a experiencia e cumprir obrigacoes legais. Nao vendemos seus dados.</p>
            <h3>3. Seus direitos</h3>
            <p>Voce pode acessar, corrigir e excluir seus dados. A exclusao da conta remove seus dados da plataforma.</p>
            <h3>4. Seguranca</h3>
            <p>Adotamos boas praticas de seguranca, incluindo criptografia de credenciais sensiveis e senhas com hash.</p>
            <h3>5. Contato</h3>
            <p>Duvidas sobre privacidade: use a pagina de contato.</p>

        <?php else: ?>
            <h3>O que sao cookies</h3>
            <p>Cookies sao pequenos arquivos usados para lembrar preferencias e medir o desempenho do site.</p>
            <h3>Como usamos</h3>
            <p>Usamos cookies essenciais (sessao e seguranca) e, quando ativados pelo administrador, cookies de analytics e marketing.</p>
            <h3>Gerenciamento</h3>
            <p>Voce pode gerenciar cookies nas configuracoes do seu navegador.</p>
        <?php endif; ?>

        <div class="mt-3"><a class="btn btn-primary" href="<?= url('/') ?>">Voltar ao inicio</a></div>
    </div>
</section>
<?php $this->stop(); ?>
