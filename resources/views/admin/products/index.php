<?php
/** @var \App\Core\View $this */
/** @var array $products */
$this->extend('layouts.admin');
?>
<?php $this->start('content'); ?>
<div class="flex justify-between items-center mb-3">
    <div></div>
    <a class="btn btn-primary" href="<?= url('/admin/produtos/novo') ?>">+ Novo produto</a>
</div>
<div class="table-wrap">
    <table class="table">
        <thead><tr><th>Nome</th><th>Slug</th><th>Preco</th><th>Tipo</th><th>Acesso</th><th>Ativo</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($products as $p): ?>
            <tr>
                <td class="fw-600"><?= e($p['name']) ?></td>
                <td class="text-muted text-sm"><?= e($p['slug']) ?></td>
                <td><?= money($p['price']) ?><?php if ($p['promo_price']): ?> <span class="text-muted"><s><?= money($p['promo_price']) ?></s></span><?php endif; ?></td>
                <td><span class="badge badge-blue"><?= e($p['type']) ?></span></td>
                <td class="text-sm text-muted"><?= e($p['grants_access']) ?></td>
                <td><?= $p['is_active'] ? '<span class="badge badge-green">Sim</span>' : '<span class="badge badge-gray">Nao</span>' ?></td>
                <td><a class="btn btn-ghost btn-sm" href="<?= url('/admin/produtos/' . $p['id'] . '/editar') ?>">Editar</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php $this->stop(); ?>
