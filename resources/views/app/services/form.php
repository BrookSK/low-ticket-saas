<?php
/** @var \App\Core\View $this */
/** @var array|null $service */
$this->extend('layouts.app');
$isEdit = $service !== null;
$action = $isEdit ? url('/servicos/' . $service['id']) : url('/servicos');
$v = fn($k) => $isEdit ? e($service[$k] ?? '') : e(old($k));
?>
<?php $this->start('content'); ?>
<div class="card" style="max-width:700px"><div class="card-body">
    <form method="POST" action="<?= $action ?>">
        <?= csrf_field() ?>
        <?php if ($isEdit): ?><input type="hidden" name="_method" value="PUT"><?php endif; ?>
        <div class="form-group"><label class="form-label">Nome *</label><input class="form-control" name="name" value="<?= $v('name') ?>" required></div>
        <div class="form-group"><label class="form-label">Descricao</label><textarea class="form-control" name="description"><?= $v('description') ?></textarea></div>
        <div class="form-row-3">
            <div class="form-group"><label class="form-label">Tipo</label>
                <select class="form-control" name="kind">
                    <option value="service" <?= $isEdit && $service['kind'] === 'service' ? 'selected' : '' ?>>Servico</option>
                    <option value="product" <?= $isEdit && $service['kind'] === 'product' ? 'selected' : '' ?>>Produto</option>
                </select></div>
            <div class="form-group"><label class="form-label">Custo</label><input class="form-control" name="cost" value="<?= $v('cost') ?>"></div>
            <div class="form-group"><label class="form-label">Preco sugerido</label><input class="form-control" name="suggested_price" value="<?= $v('suggested_price') ?>"></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label class="form-label">Unidade</label><input class="form-control" name="unit" value="<?= $v('unit') ?>" placeholder="un, h, m2..."></div>
            <div class="form-group"><label class="form-label">Ativo</label><br><label><input type="checkbox" name="is_active" value="1" <?= !$isEdit || $service['is_active'] ? 'checked' : '' ?>> Ativo</label></div>
        </div>
        <button class="btn btn-primary" type="submit">Salvar</button>
        <a class="btn btn-ghost" href="<?= url('/servicos') ?>">Cancelar</a>
    </form>
</div></div>
<?php $this->stop(); ?>
