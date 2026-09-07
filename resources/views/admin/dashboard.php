<?php
/** @var \App\Core\View $this */
/** @var array $stats */
/** @var array $recentOrders */
$this->extend('layouts.admin');
$statusBadge = [
    'approved' => 'badge-green', 'pending' => 'badge-yellow', 'refused' => 'badge-red',
    'canceled' => 'badge-gray', 'refunded' => 'badge-purple', 'expired' => 'badge-gray',
];
?>
<?php $this->start('content'); ?>
<div class="grid grid-4 mb-3">
    <div class="stat-card"><div class="label">Faturamento total</div><div class="value"><?= money($stats['revenue_total']) ?></div></div>
    <div class="stat-card"><div class="label">Faturamento no mes</div><div class="value"><?= money($stats['revenue_month']) ?></div></div>
    <div class="stat-card"><div class="label">Vendas hoje</div><div class="value"><?= (int) $stats['sales_today'] ?></div></div>
    <div class="stat-card"><div class="label">Pedidos totais</div><div class="value"><?= (int) $stats['orders'] ?></div></div>
</div>
<div class="grid grid-4 mb-3">
    <div class="stat-card"><div class="label">Usuarios</div><div class="value"><?= (int) $stats['users'] ?></div></div>
    <div class="stat-card"><div class="label">Usuarios pagantes</div><div class="value"><?= (int) $stats['users_paying'] ?></div></div>
    <div class="stat-card"><div class="label">Upsells vendidos</div><div class="value"><?= (int) $stats['upsells'] ?></div></div>
    <div class="stat-card"><div class="label">Orcamentos criados</div><div class="value"><?= (int) $stats['quotes'] ?></div></div>
</div>

<div class="card">
    <div class="card-header"><h2>Pedidos recentes</h2><a class="btn btn-ghost btn-sm" href="<?= url('/admin/pedidos') ?>">Ver todos</a></div>
    <?php if (empty($recentOrders)): ?>
        <div class="empty-state"><div class="icon">▤</div><h3>Nenhum pedido ainda</h3><p>Assim que houver vendas, elas aparecerao aqui.</p></div>
    <?php else: ?>
    <div class="table-wrap" style="border:none">
        <table class="table">
            <thead><tr><th>Ref.</th><th>Cliente</th><th>Valor</th><th>Gateway</th><th>Status</th><th>Data</th></tr></thead>
            <tbody>
            <?php foreach ($recentOrders as $o): ?>
                <tr>
                    <td><a href="<?= url('/admin/pedidos/' . $o['id']) ?>"><?= e($o['reference']) ?></a></td>
                    <td><?= e($o['user_name'] ?? $o['email'] ?? '-') ?></td>
                    <td><?= money($o['total']) ?></td>
                    <td><?= e($o['gateway'] ?? '-') ?></td>
                    <td><span class="badge <?= $statusBadge[$o['status']] ?? 'badge-gray' ?>"><?= e($o['status']) ?></span></td>
                    <td class="text-muted text-sm"><?= e(date('d/m/Y H:i', strtotime($o['created_at']))) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
<?php $this->stop(); ?>
