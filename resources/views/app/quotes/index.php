<?php
/** @var \App\Core\View $this */
/** @var array $quotes */ /** @var string $status */
$this->extend('layouts.app');
$badge = ['draft'=>'badge-gray','sent'=>'badge-blue','viewed'=>'badge-purple','approved'=>'badge-green','refused'=>'badge-red','expired'=>'badge-yellow','canceled'=>'badge-gray'];
$labels = ['draft'=>'Rascunho','sent'=>'Enviado','viewed'=>'Visualizado','approved'=>'Aprovado','refused'=>'Recusado','expired'=>'Expirado','canceled'=>'Cancelado'];
?>
<?php $this->start('content'); ?>
<div class="flex justify-between items-center mb-3" style="flex-wrap:wrap;gap:.75rem">
    <form method="GET"><select class="form-control" name="status" style="max-width:200px" onchange="this.form.submit()">
        <option value="">Todos os status</option>
        <?php foreach ($labels as $s => $l): ?><option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?>
    </select></form>
    <a class="btn btn-primary" href="<?= url('/orcamentos/novo') ?>">+ Novo orcamento</a>
</div>
<?php if (empty($quotes)): ?>
    <div class="card"><div class="empty-state"><div class="icon">📄</div><h3>Nenhum orcamento</h3><p>Crie orcamentos profissionais e envie pelo WhatsApp em segundos.</p><a class="btn btn-primary mt-2" href="<?= url('/orcamentos/novo') ?>">Criar orcamento</a></div></div>
<?php else: ?>
<div class="table-wrap"><table class="table">
    <thead><tr><th>Numero</th><th>Cliente</th><th>Total</th><th>Validade</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($quotes as $q): ?>
        <tr>
            <td class="fw-600"><a href="<?= url('/orcamentos/' . $q['id']) ?>"><?= e($q['number']) ?></a></td>
            <td><?= e($q['customer_name'] ?? '-') ?></td>
            <td><?= money($q['total']) ?></td>
            <td class="text-sm text-muted"><?= $q['valid_until'] ? e(date('d/m/Y', strtotime($q['valid_until']))) : '-' ?></td>
            <td><span class="badge <?= $badge[$q['status']] ?? 'badge-gray' ?>"><?= e($labels[$q['status']] ?? $q['status']) ?></span></td>
            <td><a class="btn btn-ghost btn-sm" href="<?= url('/orcamentos/' . $q['id']) ?>">Abrir</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table></div>
<?php endif; ?>
<?php $this->stop(); ?>
