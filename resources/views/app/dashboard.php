<?php
/** @var \App\Core\View $this */
/** @var array $user */ /** @var array $stats */ /** @var array $checklist */ /** @var int $progress */ /** @var callable $has */ /** @var array $recentQuotes */
$this->extend('layouts.app');
$statusBadge = ['draft'=>'badge-gray','sent'=>'badge-blue','viewed'=>'badge-purple','approved'=>'badge-green','refused'=>'badge-red','expired'=>'badge-yellow','canceled'=>'badge-gray'];
?>
<?php $this->start('content'); ?>
<h1 class="mb-3">Ola, <?= e(explode(' ', $user['name'])[0]) ?> 👋</h1>

<div class="quick-actions mb-4">
    <?php if ($has('orcamentos')): ?><a class="quick-action" href="<?= url('/orcamentos/novo') ?>"><div class="qa-icon">📄</div><div class="qa-label">Novo orcamento</div></a><?php endif; ?>
    <?php if ($has('clientes') || $has('orcamentos')): ?><a class="quick-action" href="<?= url('/clientes/novo') ?>"><div class="qa-icon">👤</div><div class="qa-label">Novo cliente</div></a><?php endif; ?>
    <?php if ($has('financeiro')): ?><a class="quick-action" href="<?= url('/receitas') ?>"><div class="qa-icon">💰</div><div class="qa-label">Nova receita</div></a><?php endif; ?>
    <?php if ($has('financeiro')): ?><a class="quick-action" href="<?= url('/despesas') ?>"><div class="qa-icon">💸</div><div class="qa-label">Nova despesa</div></a><?php endif; ?>
    <?php if ($has('precificador')): ?><a class="quick-action" href="<?= url('/precificador') ?>"><div class="qa-icon">🧮</div><div class="qa-label">Calcular preco</div></a><?php endif; ?>
</div>

<div class="grid grid-4 mb-4">
    <div class="stat-card"><div class="label">Orcamentos</div><div class="value"><?= (int) $stats['quotes'] ?></div></div>
    <div class="stat-card"><div class="label">Aprovados</div><div class="value"><?= (int) $stats['quotes_approved'] ?></div></div>
    <div class="stat-card"><div class="label">Pendentes</div><div class="value"><?= (int) $stats['quotes_pending'] ?></div></div>
    <div class="stat-card"><div class="label">Clientes</div><div class="value"><?= (int) $stats['customers'] ?></div></div>
</div>

<?php if (($has)('financeiro')): ?>
<div class="grid grid-3 mb-4">
    <div class="stat-card"><div class="label">Faturamento</div><div class="value"><?= money($stats['revenue']) ?></div></div>
    <div class="stat-card"><div class="label">Despesas</div><div class="value"><?= money($stats['expenses']) ?></div></div>
    <div class="stat-card"><div class="label">Lucro</div><div class="value" style="color:<?= $stats['profit'] >= 0 ? 'var(--success)' : 'var(--danger)' ?>"><?= money($stats['profit']) ?></div></div>
</div>
<?php endif; ?>

<div class="grid" style="grid-template-columns:1fr 340px;gap:1.5rem;align-items:start">
    <div class="card">
        <div class="card-header"><h3>Orcamentos recentes</h3><?php if (($has)('orcamentos')): ?><a class="btn btn-ghost btn-sm" href="<?= url('/orcamentos') ?>">Ver todos</a><?php endif; ?></div>
        <?php if (empty($recentQuotes)): ?>
            <div class="empty-state"><div class="icon">📄</div><h3>Nenhum orcamento ainda</h3><p>Crie seu primeiro orcamento em segundos.</p>
            <?php if (($has)('orcamentos')): ?><a class="btn btn-primary mt-2" href="<?= url('/orcamentos/novo') ?>">Criar orcamento</a><?php endif; ?></div>
        <?php else: ?>
            <div class="table-wrap" style="border:none"><table class="table">
                <thead><tr><th>Numero</th><th>Cliente</th><th>Total</th><th>Status</th></tr></thead>
                <tbody><?php foreach ($recentQuotes as $q): ?>
                    <tr><td><a href="<?= url('/orcamentos/' . $q['id']) ?>"><?= e($q['number']) ?></a></td>
                        <td><?= e($q['customer_name'] ?? '-') ?></td><td><?= money($q['total']) ?></td>
                        <td><span class="badge <?= $statusBadge[$q['status']] ?? 'badge-gray' ?>"><?= e($q['status']) ?></span></td></tr>
                <?php endforeach; ?></tbody>
            </table></div>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="card-header"><h3>Primeiros passos</h3></div>
        <div class="card-body">
            <div class="progress-bar mb-2"><div class="fill" style="width:<?= $progress ?>%"></div></div>
            <p class="text-sm text-muted mb-2"><?= $progress ?>% concluido</p>
            <ul class="checklist">
                <li class="<?= $checklist['profile'] ? 'done' : '' ?>"><span class="check">✓</span><span class="txt"><a href="<?= url('/configuracoes') ?>">Complete seu perfil</a></span></li>
                <li class="<?= $checklist['customer'] ? 'done' : '' ?>"><span class="check">✓</span><span class="txt"><a href="<?= url('/clientes/novo') ?>">Cadastre seu primeiro cliente</a></span></li>
                <li class="<?= $checklist['quote'] ? 'done' : '' ?>"><span class="check">✓</span><span class="txt"><a href="<?= url('/orcamentos/novo') ?>">Crie seu primeiro orcamento</a></span></li>
                <li class="<?= $checklist['pricing'] ? 'done' : '' ?>"><span class="check">✓</span><span class="txt"><a href="<?= url('/precificador') ?>">Calcule o preco do seu servico</a></span></li>
                <li class="<?= $checklist['revenue'] ? 'done' : '' ?>"><span class="check">✓</span><span class="txt"><a href="<?= url('/receitas') ?>">Registre sua primeira receita</a></span></li>
            </ul>
        </div>
    </div>
</div>
<?php $this->stop(); ?>
