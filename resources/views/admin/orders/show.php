<?php
/** @var \App\Core\View $this */
/** @var array $order */ /** @var array $items */ /** @var array $payments */ /** @var array $webhooks */
$this->extend('layouts.admin');
?>
<?php $this->start('content'); ?>
<div class="grid" style="grid-template-columns:1fr 320px;gap:1.5rem;align-items:start">
    <div>
        <div class="card mb-3"><div class="card-header"><h2>Pedido <?= e($order['reference']) ?></h2>
            <span class="badge badge-blue"><?= e($order['status']) ?></span></div>
            <div class="card-body">
                <p><strong>Cliente:</strong> <?= e($order['user_name'] ?? $order['email'] ?? '-') ?> (<?= e($order['user_email'] ?? '-') ?>)</p>
                <p><strong>Gateway:</strong> <?= e($order['gateway'] ?? '-') ?> · <strong>Pago em:</strong> <?= $order['paid_at'] ? e(date('d/m/Y H:i', strtotime($order['paid_at']))) : '-' ?></p>
                <table class="table mt-2">
                    <thead><tr><th>Item</th><th>Qtd</th><th>Preco</th></tr></thead>
                    <tbody><?php foreach ($items as $it): ?><tr><td><?= e($it['name']) ?></td><td><?= (int) $it['quantity'] ?></td><td><?= money($it['price']) ?></td></tr><?php endforeach; ?></tbody>
                </table>
                <p class="text-right mt-2">Subtotal: <?= money($order['subtotal']) ?> · Desconto: <?= money($order['discount']) ?> · <strong>Total: <?= money($order['total']) ?></strong></p>
            </div>
        </div>
        <div class="card mb-3"><div class="card-header"><h3>Pagamentos</h3></div>
            <div class="table-wrap" style="border:none"><table class="table">
                <thead><tr><th>Gateway</th><th>Transaction ID</th><th>Metodo</th><th>Valor</th><th>Status</th></tr></thead>
                <tbody><?php foreach ($payments as $p): ?><tr><td><?= e($p['gateway']) ?></td><td class="text-sm"><?= e($p['transaction_id'] ?? '-') ?></td><td><?= e($p['method'] ?? '-') ?></td><td><?= money($p['amount']) ?></td><td><?= e($p['status']) ?></td></tr><?php endforeach; ?>
                <?php if (empty($payments)): ?><tr><td colspan="5" class="text-muted">Nenhum pagamento.</td></tr><?php endif; ?></tbody>
            </table></div>
        </div>
        <div class="card"><div class="card-header"><h3>Webhooks</h3></div>
            <div class="table-wrap" style="border:none"><table class="table">
                <thead><tr><th>Evento</th><th>Valido</th><th>Processado</th><th>Data</th></tr></thead>
                <tbody><?php foreach ($webhooks as $w): ?><tr><td><?= e($w['event_type'] ?? '-') ?></td><td><?= $w['signature_valid'] ? 'Sim' : 'Nao' ?></td><td><?= $w['processed'] ? 'Sim' : 'Nao' ?></td><td class="text-sm text-muted"><?= e(date('d/m/Y H:i', strtotime($w['created_at']))) ?></td></tr><?php endforeach; ?>
                <?php if (empty($webhooks)): ?><tr><td colspan="4" class="text-muted">Nenhum webhook.</td></tr><?php endif; ?></tbody>
            </table></div>
        </div>
    </div>
    <div>
        <div class="card"><div class="card-header"><h3>Acoes</h3></div><div class="card-body">
            <?php if (!in_array($order['status'], ['refunded','canceled'], true)): ?>
                <form method="POST" action="<?= url('/admin/pedidos/' . $order['id'] . '/reembolsar') ?>" data-confirm="Marcar como estornado?"><?= csrf_field() ?>
                    <button class="btn btn-danger btn-block" type="submit">Marcar estorno</button></form>
            <?php else: ?><p class="text-muted text-sm">Sem acoes disponiveis.</p><?php endif; ?>
        </div></div>
    </div>
</div>
<?php $this->stop(); ?>
