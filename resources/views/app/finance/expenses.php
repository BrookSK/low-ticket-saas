<?php
/** @var \App\Core\View $this */
/** @var array $expenses */ /** @var array $categories */
$this->extend('layouts.app');
$badge = ['pending'=>'badge-yellow','paid'=>'badge-green','overdue'=>'badge-red','canceled'=>'badge-gray'];
$labels = ['pending'=>'Pendente','paid'=>'Pago','overdue'=>'Vencido','canceled'=>'Cancelado'];
?>
<?php $this->start('content'); ?>
<div class="grid" style="grid-template-columns:1fr 340px;gap:1.5rem;align-items:start">
    <div>
        <?php if (empty($expenses)): ?>
            <div class="card"><div class="empty-state"><div class="icon">↓</div><h3>Nenhuma despesa</h3><p>Registre suas despesas para saber quanto realmente esta ganhando.</p></div></div>
        <?php else: ?>
        <div class="table-wrap"><table class="table">
            <thead><tr><th>Descricao</th><th>Fornecedor</th><th>Valor</th><th>Vencimento</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($expenses as $x): ?>
                <tr>
                    <td class="fw-600"><?= e($x['description']) ?></td>
                    <td class="text-sm"><?= e($x['supplier'] ?? '-') ?></td>
                    <td><?= money($x['amount']) ?></td>
                    <td class="text-sm text-muted"><?= $x['due_date'] ? e(date('d/m/Y', strtotime($x['due_date']))) : '-' ?></td>
                    <td><span class="badge <?= $badge[$x['status']] ?? 'badge-gray' ?>"><?= e($labels[$x['status']] ?? $x['status']) ?></span></td>
                    <td><form method="POST" action="<?= url('/despesas/' . $x['id']) ?>" data-confirm="Remover despesa?" style="display:inline">
                        <?= csrf_field() ?><input type="hidden" name="_method" value="DELETE"><button class="btn btn-ghost btn-sm">×</button></form></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
        <?php endif; ?>
    </div>
    <div class="card"><div class="card-header"><h3>Nova despesa</h3></div><div class="card-body">
        <form method="POST" action="<?= url('/despesas') ?>"><?= csrf_field() ?>
            <div class="form-group"><label class="form-label">Descricao *</label><input class="form-control" name="description" required></div>
            <div class="form-group"><label class="form-label">Valor (R$) *</label><input class="form-control" name="amount" required></div>
            <div class="form-group"><label class="form-label">Fornecedor</label><input class="form-control" name="supplier"></div>
            <div class="form-group"><label class="form-label">Categoria</label>
                <select class="form-control" name="category_id"><option value="">—</option>
                    <?php foreach ($categories as $cat): ?><option value="<?= $cat['id'] ?>"><?= e($cat['name']) ?></option><?php endforeach; ?></select></div>
            <div class="form-row">
                <div class="form-group"><label class="form-label">Data</label><input class="form-control" type="date" name="date" value="<?= date('Y-m-d') ?>"></div>
                <div class="form-group"><label class="form-label">Vencimento</label><input class="form-control" type="date" name="due_date"></div>
            </div>
            <div class="form-group"><label class="form-label">Status</label>
                <select class="form-control" name="status"><option value="pending">Pendente</option><option value="paid">Pago</option></select></div>
            <button class="btn btn-primary btn-block" type="submit">Adicionar despesa</button>
        </form>
    </div></div>
</div>
<?php $this->stop(); ?>
