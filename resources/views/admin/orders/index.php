<?php
/** @var \App\Core\View $this */
/** @var array $orders */
$this->extend('layouts.admin');
$badge = ['approved'=>'badge-green','pending'=>'badge-yellow','refused'=>'badge-red','canceled'=>'badge-gray','refunded'=>'badge-purple','expired'=>'badge-gray'];
?>
<?php $this->start('content'); ?>
<form method="GET" class="mb-3">
    <select class="form-control" name="status" style="max-width:220px" onchange="this.form.submit()">
        <option value="">Todos os status</option>
        <?php foreach (['pending','approved','refused','canceled','refunded','expired'] as $s): ?>
            <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?>
    </select>
</form>
<div class="table-wrap">
    <table class="table">
        <thead><tr><th>Ref</th><th>Cliente</th><th>Total</th><th>Gateway</th><th>Status</th><th>Upsell</th><th>Data</th></tr></thead>
        <tbody>
        <?php foreach ($orders as $o): ?>
            <tr>
                <td><a href="<?= url('/admin/pedidos/' . $o['id']) ?>"><?= e($o['reference']) ?></a></td>
                <td><?= e($o['user_name'] ?? $o['email'] ?? '-') ?></td>
                <td><?= money($o['total']) ?></td>
                <td><?= e($o['gateway'] ?? '-') ?></td>
                <td><span class="badge <?= $badge[$o['status']] ?? 'badge-gray' ?>"><?= e($o['status']) ?></span></td>
                <td><?= $o['is_upsell'] ? 'Sim' : '-' ?></td>
                <td class="text-sm text-muted"><?= e(date('d/m/Y H:i', strtotime($o['created_at']))) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($orders)): ?><tr><td colspan="7"><div class="empty-state"><h3>Nenhum pedido</h3></div></td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php $this->stop(); ?>
