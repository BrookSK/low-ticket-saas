<?php
/** @var \App\Core\View $this */
/** @var array $services */
$this->extend('layouts.app');
?>
<?php $this->start('content'); ?>
<div class="flex justify-between items-center mb-3">
    <div></div>
    <a class="btn btn-primary" href="<?= url('/servicos/novo') ?>">+ Novo item</a>
</div>
<?php if (empty($services)): ?>
    <div class="card"><div class="empty-state"><div class="icon">≣</div><h3>Nenhum servico ou produto</h3><p>Cadastre itens que voce usa com frequencia para reutilizar nos orcamentos.</p><a class="btn btn-primary mt-2" href="<?= url('/servicos/novo') ?>">Cadastrar item</a></div></div>
<?php else: ?>
<div class="table-wrap"><table class="table">
    <thead><tr><th>Nome</th><th>Tipo</th><th>Custo</th><th>Preco sugerido</th><th>Unidade</th><th>Ativo</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($services as $s): ?>
        <tr>
            <td class="fw-600"><?= e($s['name']) ?></td>
            <td><span class="badge <?= $s['kind'] === 'product' ? 'badge-purple' : 'badge-blue' ?>"><?= $s['kind'] === 'product' ? 'Produto' : 'Servico' ?></span></td>
            <td><?= $s['cost'] !== null ? money($s['cost']) : '-' ?></td>
            <td><?= $s['suggested_price'] !== null ? money($s['suggested_price']) : '-' ?></td>
            <td class="text-sm"><?= e($s['unit'] ?? '-') ?></td>
            <td><?= $s['is_active'] ? '<span class="badge badge-green">Sim</span>' : '<span class="badge badge-gray">Nao</span>' ?></td>
            <td><a class="btn btn-ghost btn-sm" href="<?= url('/servicos/' . $s['id'] . '/editar') ?>">Editar</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table></div>
<?php endif; ?>
<?php $this->stop(); ?>
