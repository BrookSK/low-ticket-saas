<?php
/** @var \App\Core\View $this */
/** @var array $user */ /** @var array $orders */ /** @var array $access */ /** @var array $roles */
$this->extend('layouts.admin');
?>
<?php $this->start('content'); ?>
<div class="grid" style="grid-template-columns:1fr 320px;gap:1.5rem;align-items:start">
    <div>
        <div class="card mb-3"><div class="card-header"><h2><?= e($user['name']) ?></h2>
            <span class="badge <?= $user['status'] === 'active' ? 'badge-green' : 'badge-red' ?>"><?= e($user['status']) ?></span></div>
            <div class="card-body">
                <p><strong>E-mail:</strong> <?= e($user['email']) ?> <?= $user['email_verified_at'] ? '<span class="badge badge-green">verificado</span>' : '<span class="badge badge-yellow">nao verificado</span>' ?></p>
                <p><strong>Telefone:</strong> <?= e($user['phone'] ?? '-') ?> · <strong>WhatsApp:</strong> <?= e($user['whatsapp'] ?? '-') ?></p>
                <p><strong>Papeis:</strong> <?= e(implode(', ', $roles) ?: 'user') ?></p>
                <p><strong>Ultimo login:</strong> <?= $user['last_login_at'] ? e(date('d/m/Y H:i', strtotime($user['last_login_at']))) : 'nunca' ?></p>
            </div>
        </div>
        <div class="card"><div class="card-header"><h3>Pedidos</h3></div>
            <div class="table-wrap" style="border:none"><table class="table">
                <thead><tr><th>Ref</th><th>Total</th><th>Status</th><th>Data</th></tr></thead>
                <tbody>
                <?php foreach ($orders as $o): ?>
                    <tr><td><a href="<?= url('/admin/pedidos/' . $o['id']) ?>"><?= e($o['reference']) ?></a></td>
                        <td><?= money($o['total']) ?></td><td><?= e($o['status']) ?></td>
                        <td class="text-sm text-muted"><?= e(date('d/m/Y', strtotime($o['created_at']))) ?></td></tr>
                <?php endforeach; ?>
                <?php if (empty($orders)): ?><tr><td colspan="4" class="text-muted">Nenhum pedido.</td></tr><?php endif; ?>
                </tbody>
            </table></div>
        </div>
    </div>
    <div>
        <div class="card mb-3"><div class="card-header"><h3>Acoes</h3></div><div class="card-body">
            <?php if ($user['status'] === 'blocked'): ?>
                <form method="POST" action="<?= url('/admin/usuarios/' . $user['id'] . '/desbloquear') ?>"><?= csrf_field() ?>
                    <button class="btn btn-success btn-block" type="submit">Desbloquear</button></form>
            <?php else: ?>
                <form method="POST" action="<?= url('/admin/usuarios/' . $user['id'] . '/bloquear') ?>" data-confirm="Bloquear usuario?"><?= csrf_field() ?>
                    <button class="btn btn-danger btn-block" type="submit">Bloquear</button></form>
            <?php endif; ?>
        </div></div>
        <div class="card"><div class="card-header"><h3>Conceder acesso</h3></div><div class="card-body">
            <p class="text-sm text-muted">Acessos atuais: <?= e(implode(', ', array_column($access, 'module')) ?: 'nenhum') ?></p>
            <form method="POST" action="<?= url('/admin/usuarios/' . $user['id'] . '/acesso') ?>"><?= csrf_field() ?>
                <select class="form-control mb-2" name="module">
                    <?php foreach (['orcamentos','financeiro','precificador','clientes','servicos','relatorios'] as $m): ?>
                        <option value="<?= $m ?>"><?= $m ?></option><?php endforeach; ?>
                </select>
                <button class="btn btn-primary btn-block" type="submit">Conceder</button>
            </form>
        </div></div>
    </div>
</div>
<?php $this->stop(); ?>
