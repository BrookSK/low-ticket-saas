<?php
/** @var \App\Core\View $this */
/** @var array $customers */ /** @var string $q */
$this->extend('layouts.app');
?>
<?php $this->start('content'); ?>
<div class="flex justify-between items-center mb-3" style="flex-wrap:wrap;gap:.75rem">
    <form method="GET" class="flex gap-1"><input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Buscar cliente" style="max-width:280px"><button class="btn btn-ghost" type="submit">Buscar</button></form>
    <a class="btn btn-primary" href="<?= url('/clientes/novo') ?>">+ Novo cliente</a>
</div>
<?php if (empty($customers)): ?>
    <div class="card"><div class="empty-state"><div class="icon">👤</div><h3>Nenhum cliente cadastrado</h3><p>Cadastre seus clientes para agilizar orcamentos.</p><a class="btn btn-primary mt-2" href="<?= url('/clientes/novo') ?>">Cadastrar cliente</a></div></div>
<?php else: ?>
<div class="table-wrap">
    <table class="table">
        <thead><tr><th>Nome</th><th>Documento</th><th>Contato</th><th>Cidade</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($customers as $c): ?>
            <tr>
                <td class="fw-600"><a href="<?= url('/clientes/' . $c['id']) ?>"><?= e($c['name']) ?></a></td>
                <td class="text-sm text-muted"><?= e($c['document'] ?? '-') ?></td>
                <td class="text-sm"><?= e($c['phone'] ?? $c['email'] ?? '-') ?></td>
                <td class="text-sm"><?= e($c['city'] ? $c['city'] . '/' . $c['state'] : '-') ?></td>
                <td><a class="btn btn-ghost btn-sm" href="<?= url('/clientes/' . $c['id'] . '/editar') ?>">Editar</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
<?php $this->stop(); ?>
