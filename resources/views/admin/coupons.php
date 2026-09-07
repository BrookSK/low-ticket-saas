<?php
/** @var \App\Core\View $this */
/** @var array $coupons */
$this->extend('layouts.admin');
?>
<?php $this->start('content'); ?>
<div class="grid" style="grid-template-columns:1fr 340px;gap:1.5rem;align-items:start">
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Codigo</th><th>Tipo</th><th>Valor</th><th>Usos</th><th>Validade</th><th>Ativo</th><th></th></tr></thead>
            <tbody>
            <?php if (empty($coupons)): ?>
                <tr><td colspan="7"><div class="empty-state"><h3>Nenhum cupom</h3></div></td></tr>
            <?php endif; ?>
            <?php foreach ($coupons as $c): ?>
                <tr>
                    <td class="fw-600"><?= e($c['code']) ?></td>
                    <td><?= $c['type'] === 'percent' ? 'Percentual' : 'Fixo' ?></td>
                    <td><?= $c['type'] === 'percent' ? e($c['percent']) . '%' : money($c['amount']) ?></td>
                    <td><?= (int) $c['used_count'] ?><?= $c['max_uses'] ? ' / ' . (int) $c['max_uses'] : '' ?></td>
                    <td class="text-sm text-muted"><?= $c['valid_until'] ? e(date('d/m/Y', strtotime($c['valid_until']))) : 'Sem limite' ?></td>
                    <td><?= $c['is_active'] ? '<span class="badge badge-green">Sim</span>' : '<span class="badge badge-gray">Nao</span>' ?></td>
                    <td>
                        <form method="POST" action="<?= url('/admin/cupons/' . $c['id']) ?>" data-confirm="Remover cupom?" style="display:inline">
                            <?= csrf_field() ?><input type="hidden" name="_method" value="DELETE">
                            <button class="btn btn-ghost btn-sm" type="submit">Remover</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="card"><div class="card-header"><h3>Novo cupom</h3></div><div class="card-body">
        <form method="POST" action="<?= url('/admin/cupons') ?>">
            <?= csrf_field() ?>
            <div class="form-group"><label class="form-label">Codigo</label><input class="form-control" name="code" required></div>
            <div class="form-group"><label class="form-label">Tipo</label>
                <select class="form-control" name="type"><option value="percent">Percentual</option><option value="fixed">Valor fixo</option></select></div>
            <div class="form-group"><label class="form-label">Percentual (%)</label><input class="form-control" name="percent"></div>
            <div class="form-group"><label class="form-label">Valor fixo (R$)</label><input class="form-control" name="amount"></div>
            <div class="form-group"><label class="form-label">Valido ate</label><input class="form-control" type="date" name="valid_until"></div>
            <div class="form-group"><label class="form-label">Max. usos</label><input class="form-control" type="number" name="max_uses"></div>
            <div class="form-group"><label><input type="checkbox" name="is_active" value="1" checked> Ativo</label></div>
            <button class="btn btn-primary btn-block" type="submit">Criar cupom</button>
        </form>
    </div></div>
</div>
<?php $this->stop(); ?>
