<?php
/** @var \App\Core\View $this */
/** @var array $revenues */ /** @var array $customers */ /** @var array $categories */
$this->extend('layouts.app');
$badge = ['pending'=>'badge-yellow','received'=>'badge-green','overdue'=>'badge-red','canceled'=>'badge-gray'];
$labels = ['pending'=>'Pendente','received'=>'Recebido','overdue'=>'Vencido','canceled'=>'Cancelado'];
?>
<?php $this->start('content'); ?>
<div class="grid" style="grid-template-columns:1fr 340px;gap:1.5rem;align-items:start">
    <div>
        <?php if (empty($revenues)): ?>
            <div class="card"><div class="empty-state"><div class="icon">↑</div><h3>Nenhuma receita</h3><p>Registre suas receitas para acompanhar seu faturamento.</p></div></div>
        <?php else: ?>
        <div class="table-wrap"><table class="table">
            <thead><tr><th>Descricao</th><th>Cliente</th><th>Valor</th><th>Vencimento</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($revenues as $r): ?>
                <tr>
                    <td class="fw-600"><?= e($r['description']) ?></td>
                    <td class="text-sm"><?= e($r['customer_name'] ?? '-') ?></td>
                    <td><?= money($r['amount']) ?></td>
                    <td class="text-sm text-muted"><?= $r['due_date'] ? e(date('d/m/Y', strtotime($r['due_date']))) : '-' ?></td>
                    <td><span class="badge <?= $badge[$r['status']] ?? 'badge-gray' ?>"><?= e($labels[$r['status']] ?? $r['status']) ?></span></td>
                    <td><form method="POST" action="<?= url('/receitas/' . $r['id']) ?>" data-confirm="Remover receita?" style="display:inline">
                        <?= csrf_field() ?><input type="hidden" name="_method" value="DELETE"><button class="btn btn-ghost btn-sm">×</button></form></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
        <?php endif; ?>
    </div>
    <div class="card"><div class="card-header"><h3>Nova receita</h3></div><div class="card-body">
        <form method="POST" action="<?= url('/receitas') ?>"><?= csrf_field() ?>
            <div class="form-group"><label class="form-label">Descricao *</label><input class="form-control" name="description" required></div>
            <div class="form-group"><label class="form-label">Valor (R$) *</label><input class="form-control" name="amount" required></div>
            <div class="form-group"><label class="form-label">Cliente</label>
                <select class="form-control" name="customer_id"><option value="">—</option>
                    <?php foreach ($customers as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?></select></div>
            <div class="form-group"><label class="form-label">Categoria</label>
                <select class="form-control" name="category_id"><option value="">—</option>
                    <?php foreach ($categories as $cat): ?><option value="<?= $cat['id'] ?>"><?= e($cat['name']) ?></option><?php endforeach; ?></select></div>
            <div class="form-row">
                <div class="form-group"><label class="form-label">Data</label><input class="form-control" type="date" name="date" value="<?= date('Y-m-d') ?>"></div>
                <div class="form-group"><label class="form-label">Vencimento</label><input class="form-control" type="date" name="due_date"></div>
            </div>
            <div class="form-group"><label class="form-label">Status</label>
                <select class="form-control" name="status"><option value="pending">Pendente</option><option value="received">Recebido</option></select></div>
            <button class="btn btn-primary btn-block" type="submit">Adicionar receita</button>
        </form>
    </div></div>
</div>
<?php $this->stop(); ?>
