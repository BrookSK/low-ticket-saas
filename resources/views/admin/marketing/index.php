<?php
/** @var \App\Core\View $this */
/** @var array $events */ /** @var array $integrations */
$this->extend('layouts.admin');
?>
<?php $this->start('content'); ?>
<div class="grid grid-4 mb-3">
    <div class="stat-card"><div class="label">Google Analytics</div><div class="value" style="font-size:1.1rem"><?= $integrations['analytics'] ? '<span class="badge badge-green">Ativo</span>' : '<span class="badge badge-gray">Inativo</span>' ?></div></div>
    <div class="stat-card"><div class="label">Google Ads</div><div class="value" style="font-size:1.1rem"><?= $integrations['ads'] ? '<span class="badge badge-green">Ativo</span>' : '<span class="badge badge-gray">Inativo</span>' ?></div></div>
    <div class="stat-card"><div class="label">Tag Manager</div><div class="value" style="font-size:1.1rem"><?= $integrations['gtm'] ? '<span class="badge badge-green">Ativo</span>' : '<span class="badge badge-gray">Inativo</span>' ?></div></div>
    <div class="stat-card"><div class="label">Meta Pixel</div><div class="value" style="font-size:1.1rem"><?= $integrations['meta'] ? '<span class="badge badge-green">Ativo</span>' : '<span class="badge badge-gray">Inativo</span>' ?></div></div>
</div>
<div class="flex gap-1 mb-3">
    <a class="btn btn-ghost" href="<?= url('/admin/configuracoes/google') ?>">Configurar Google</a>
    <a class="btn btn-ghost" href="<?= url('/admin/marketing/performance') ?>">Performance de marketing</a>
    <a class="btn btn-ghost" href="<?= url('/admin/marketing/atribuicao') ?>">Atribuicao</a>
</div>
<div class="card"><div class="card-header"><h3>Eventos (ultimos 30 dias)</h3></div>
    <div class="table-wrap" style="border:none"><table class="table"><thead><tr><th>Evento</th><th>Total</th><th>Valor</th></tr></thead>
    <tbody><?php foreach ($events as $ev): ?><tr><td><?= e($ev['event']) ?></td><td><?= (int) $ev['total'] ?></td><td><?= $ev['value'] > 0 ? money($ev['value']) : '-' ?></td></tr><?php endforeach; ?>
    <?php if (empty($events)): ?><tr><td colspan="3" class="text-muted">Sem eventos registrados.</td></tr><?php endif; ?></tbody></table></div>
</div>
<?php $this->stop(); ?>
