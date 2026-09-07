<?php
/** @var \App\Core\View $this */
/** @var array $upsells */
/** @var array $products */
$this->extend('layouts.admin');
?>
<?php $this->start('content'); ?>
<div class="grid" style="grid-template-columns:1fr 360px;gap:1.5rem;align-items:start">
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Gatilho</th><th>Oferta</th><th>Titulo</th><th>Preco</th><th>Ativo</th><th></th></tr></thead>
            <tbody>
            <?php if (empty($upsells)): ?><tr><td colspan="6"><div class="empty-state"><h3>Nenhum upsell configurado</h3></div></td></tr><?php endif; ?>
            <?php foreach ($upsells as $u): ?>
                <tr>
                    <td class="text-sm"><?= e($u['trigger_name']) ?></td>
                    <td class="text-sm fw-600"><?= e($u['offer_name']) ?></td>
                    <td class="text-sm"><?= e($u['title']) ?></td>
                    <td><?= $u['price'] !== null ? money($u['price']) : '-' ?></td>
                    <td><?= $u['is_active'] ? '<span class="badge badge-green">Sim</span>' : '<span class="badge badge-gray">Nao</span>' ?></td>
                    <td><form method="POST" action="<?= url('/admin/upsells/' . $u['id']) ?>" data-confirm="Remover?" style="display:inline">
                        <?= csrf_field() ?><input type="hidden" name="_method" value="DELETE">
                        <button class="btn btn-ghost btn-sm">Remover</button></form></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="card"><div class="card-header"><h3>Novo upsell</h3></div><div class="card-body">
        <form method="POST" action="<?= url('/admin/upsells') ?>">
            <?= csrf_field() ?>
            <div class="form-group"><label class="form-label">Ao comprar (gatilho)</label>
                <select class="form-control" name="trigger_product_id" required>
                    <?php foreach ($products as $p): ?><option value="<?= $p['id'] ?>"><?= e($p['name']) ?></option><?php endforeach; ?></select></div>
            <div class="form-group"><label class="form-label">Oferecer</label>
                <select class="form-control" name="offer_product_id" required>
                    <?php foreach ($products as $p): ?><option value="<?= $p['id'] ?>"><?= e($p['name']) ?></option><?php endforeach; ?></select></div>
            <div class="form-group"><label class="form-label">Titulo</label><input class="form-control" name="title" required></div>
            <div class="form-group"><label class="form-label">Descricao</label><textarea class="form-control" name="description"></textarea></div>
            <div class="form-group"><label class="form-label">Preco especial (R$)</label><input class="form-control" name="price"></div>
            <div class="form-group"><label><input type="checkbox" name="is_active" value="1" checked> Ativo</label></div>
            <button class="btn btn-primary btn-block" type="submit">Criar upsell</button>
        </form>
    </div></div>
</div>
<?php $this->stop(); ?>
