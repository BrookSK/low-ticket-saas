<?php
/** @var \App\Core\View $this */
/** @var array $customer */ /** @var array $quotes */ /** @var array $revenues */ /** @var array $documents */
$this->extend('layouts.app');
$statusBadge = ['draft'=>'badge-gray','sent'=>'badge-blue','viewed'=>'badge-purple','approved'=>'badge-green','refused'=>'badge-red','expired'=>'badge-yellow','canceled'=>'badge-gray'];
?>
<?php $this->start('content'); ?>
<div class="flex justify-between items-center mb-3">
    <div></div>
    <div class="flex gap-1"><a class="btn btn-ghost" href="<?= url('/clientes/' . $customer['id'] . '/editar') ?>">Editar</a>
        <a class="btn btn-primary" href="<?= url('/orcamentos/novo?customer_id=' . $customer['id']) ?>">Novo orcamento</a></div>
</div>
<div class="grid" style="grid-template-columns:320px 1fr;gap:1.5rem;align-items:start">
    <div class="card"><div class="card-body">
        <h2><?= e($customer['name']) ?></h2>
        <p class="text-sm text-muted"><?= e($customer['document'] ?? '') ?></p>
        <hr style="border:none;border-top:1px solid var(--line);margin:1rem 0">
        <p class="text-sm"><strong>E-mail:</strong> <?= e($customer['email'] ?? '-') ?></p>
        <p class="text-sm"><strong>Telefone:</strong> <?= e($customer['phone'] ?? '-') ?></p>
        <p class="text-sm"><strong>WhatsApp:</strong> <?= e($customer['whatsapp'] ?? '-') ?></p>
        <p class="text-sm"><strong>Endereco:</strong> <?= e($customer['address'] ?? '-') ?></p>
        <p class="text-sm"><strong>Cidade:</strong> <?= e($customer['city'] ? $customer['city'] . '/' . $customer['state'] : '-') ?></p>
        <?php if ($customer['notes']): ?><p class="text-sm"><strong>Obs:</strong> <?= e($customer['notes']) ?></p><?php endif; ?>
    </div></div>
    <div>
        <div class="card mb-3"><div class="card-header"><h3>Orcamentos</h3></div>
            <div class="table-wrap" style="border:none"><table class="table">
                <thead><tr><th>Numero</th><th>Total</th><th>Status</th><th>Data</th></tr></thead>
                <tbody><?php foreach ($quotes as $q): ?><tr><td><a href="<?= url('/orcamentos/' . $q['id']) ?>"><?= e($q['number']) ?></a></td>
                    <td><?= money($q['total']) ?></td><td><span class="badge <?= $statusBadge[$q['status']] ?? 'badge-gray' ?>"><?= e($q['status']) ?></span></td>
                    <td class="text-sm text-muted"><?= e(date('d/m/Y', strtotime($q['created_at']))) ?></td></tr><?php endforeach; ?>
                <?php if (empty($quotes)): ?><tr><td colspan="4" class="text-muted">Nenhum orcamento.</td></tr><?php endif; ?></tbody>
            </table></div>
        </div>
        <div class="card"><div class="card-header"><h3>Receitas</h3></div>
            <div class="table-wrap" style="border:none"><table class="table">
                <thead><tr><th>Descricao</th><th>Valor</th><th>Status</th><th>Data</th></tr></thead>
                <tbody><?php foreach ($revenues as $r): ?><tr><td><?= e($r['description']) ?></td><td><?= money($r['amount']) ?></td>
                    <td><?= e($r['status']) ?></td><td class="text-sm text-muted"><?= $r['date'] ? e(date('d/m/Y', strtotime($r['date']))) : '-' ?></td></tr><?php endforeach; ?>
                <?php if (empty($revenues)): ?><tr><td colspan="4" class="text-muted">Nenhuma receita.</td></tr><?php endif; ?></tbody>
            </table></div>
        </div>
    </div>
</div>
<?php $this->stop(); ?>
