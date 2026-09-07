<?php
/** @var \App\Core\View $this */
/** @var bool $configured */
$this->extend('layouts.admin');
?>
<?php $this->start('content'); ?>
<?php if (!$configured): ?>
    <div class="card"><div class="card-body text-center" style="padding:3rem">
        <div style="font-size:2.5rem;opacity:.4">◎</div>
        <h2>Integracao com a API do Google Ads nao configurada</h2>
        <p class="text-muted" style="max-width:520px;margin:.5rem auto 1.5rem">
            A interface de Performance de Marketing esta pronta. Para exibir investimento, cliques,
            impressoes, CTR, CPC, conversoes, custo por conversao e ROAS reais, e necessario configurar
            as credenciais da API do Google Ads. Nao exibimos dados ficticios.
        </p>
        <a class="btn btn-primary" href="<?= url('/admin/configuracoes/google') ?>">Configurar Google Ads</a>
    </div></div>
<?php else: ?>
    <div class="grid grid-4 mb-3">
        <?php foreach (['Investimento','Cliques','Impressoes','CTR','CPC','Conversoes','Custo/conv.','ROAS'] as $m): ?>
            <div class="stat-card"><div class="label"><?= $m ?></div><div class="value text-muted" style="font-size:1rem">Aguardando API</div></div>
        <?php endforeach; ?>
    </div>
    <div class="alert alert-info">Credenciais configuradas. A sincronizacao de dados da API sera exibida aqui.</div>
<?php endif; ?>
<?php $this->stop(); ?>
