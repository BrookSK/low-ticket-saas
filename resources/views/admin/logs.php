<?php
/** @var \App\Core\View $this */
/** @var array $logs */
$this->extend('layouts.admin');
$sevBadge = ['info'=>'badge-blue','warning'=>'badge-yellow','error'=>'badge-red'];
?>
<?php $this->start('content'); ?>
<form method="GET" class="flex gap-1 mb-3">
    <select class="form-control" name="severity" style="max-width:160px">
        <option value="">Todas severidades</option>
        <?php foreach (['info','warning','error'] as $s): ?><option value="<?= $s ?>" <?= $severity === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?>
    </select>
    <input class="form-control" name="action" value="<?= e($action) ?>" placeholder="Acao" style="max-width:220px">
    <button class="btn btn-ghost" type="submit">Filtrar</button>
</form>
<div class="table-wrap">
    <table class="table"><thead><tr><th>Data</th><th>Severidade</th><th>Acao</th><th>Usuario</th><th>Descricao</th><th>IP</th></tr></thead>
    <tbody>
    <?php foreach ($logs as $l): ?>
        <tr><td class="text-sm text-muted"><?= e(date('d/m/Y H:i', strtotime($l['created_at']))) ?></td>
            <td><span class="badge <?= $sevBadge[$l['severity']] ?? 'badge-gray' ?>"><?= e($l['severity']) ?></span></td>
            <td class="text-sm"><?= e($l['action']) ?></td><td class="text-sm"><?= e($l['user_name'] ?? '-') ?></td>
            <td class="text-sm"><?= e($l['description']) ?></td><td class="text-sm text-muted"><?= e($l['ip_address'] ?? '-') ?></td></tr>
    <?php endforeach; ?>
    <?php if (empty($logs)): ?><tr><td colspan="6"><div class="empty-state"><h3>Nenhum log</h3></div></td></tr><?php endif; ?>
    </tbody></table>
</div>
<?php $this->stop(); ?>
