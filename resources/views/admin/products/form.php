<?php
/** @var \App\Core\View $this */
/** @var array|null $product */
$this->extend('layouts.admin');
$isEdit = $product !== null;
$action = $isEdit ? url('/admin/produtos/' . $product['id']) : url('/admin/produtos');
$features = $isEdit && $product['features'] ? implode("\n", (array) json_decode($product['features'], true)) : '';
?>
<?php $this->start('content'); ?>
<div class="card" style="max-width:720px">
    <div class="card-body">
        <form method="POST" action="<?= $action ?>">
            <?= csrf_field() ?>
            <?php if ($isEdit): ?><input type="hidden" name="_method" value="PUT"><?php endif; ?>
            <div class="form-row">
                <div class="form-group"><label class="form-label">Nome</label>
                    <input class="form-control" name="name" value="<?= e($isEdit ? $product['name'] : old('name')) ?>" required></div>
                <div class="form-group"><label class="form-label">Slug</label>
                    <input class="form-control" name="slug" value="<?= e($isEdit ? $product['slug'] : old('slug')) ?>" required></div>
            </div>
            <div class="form-group"><label class="form-label">Descricao</label>
                <textarea class="form-control" name="description"><?= e($isEdit ? $product['description'] : '') ?></textarea></div>
            <div class="form-row-3">
                <div class="form-group"><label class="form-label">Preco</label>
                    <input class="form-control" name="price" value="<?= e($isEdit ? $product['price'] : old('price')) ?>" required></div>
                <div class="form-group"><label class="form-label">Preco promocional</label>
                    <input class="form-control" name="promo_price" value="<?= e($isEdit ? $product['promo_price'] : '') ?>"></div>
                <div class="form-group"><label class="form-label">Tipo</label>
                    <select class="form-control" name="type">
                        <?php foreach (['one_time' => 'Pagamento unico', 'subscription' => 'Assinatura', 'bundle' => 'Combo'] as $v => $l): ?>
                            <option value="<?= $v ?>" <?= $isEdit && $product['type'] === $v ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select></div>
            </div>
            <div class="form-group"><label class="form-label">Modulos liberados (csv)</label>
                <input class="form-control" name="grants_access" value="<?= e($isEdit ? $product['grants_access'] : '') ?>" placeholder="orcamentos,financeiro,precificador">
                <div class="form-hint">Ex: orcamentos,clientes,servicos</div></div>
            <div class="form-group"><label class="form-label">Recursos (um por linha)</label>
                <textarea class="form-control" name="features"><?= e($features) ?></textarea></div>
            <div class="form-row">
                <div class="form-group"><label class="form-label">Ordem</label>
                    <input class="form-control" type="number" name="sort_order" value="<?= e($isEdit ? $product['sort_order'] : 0) ?>"></div>
                <div class="form-group"><label class="form-label">Ativo</label><br>
                    <label><input type="checkbox" name="is_active" value="1" <?= !$isEdit || $product['is_active'] ? 'checked' : '' ?>> Ativo</label></div>
            </div>
            <button class="btn btn-primary" type="submit">Salvar</button>
            <a class="btn btn-ghost" href="<?= url('/admin/produtos') ?>">Cancelar</a>
        </form>
    </div>
</div>
<?php $this->stop(); ?>
