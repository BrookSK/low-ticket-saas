<?php
/** @var \App\Core\View $this */
/** @var array|null $customer */
$this->extend('layouts.app');
$isEdit = $customer !== null;
$action = $isEdit ? url('/clientes/' . $customer['id']) : url('/clientes');
$v = fn($k) => $isEdit ? e($customer[$k] ?? '') : e(old($k));
?>
<?php $this->start('content'); ?>
<div class="card" style="max-width:760px"><div class="card-body">
    <form method="POST" action="<?= $action ?>">
        <?= csrf_field() ?>
        <?php if ($isEdit): ?><input type="hidden" name="_method" value="PUT"><?php endif; ?>
        <div class="form-row">
            <div class="form-group"><label class="form-label">Nome *</label><input class="form-control" name="name" value="<?= $v('name') ?>" required></div>
            <div class="form-group"><label class="form-label">CPF / CNPJ</label><input class="form-control" name="document" value="<?= $v('document') ?>"></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label class="form-label">E-mail</label><input class="form-control" type="email" name="email" value="<?= $v('email') ?>"></div>
            <div class="form-group"><label class="form-label">Telefone</label><input class="form-control" name="phone" value="<?= $v('phone') ?>"></div>
        </div>
        <div class="form-group"><label class="form-label">WhatsApp</label><input class="form-control" name="whatsapp" value="<?= $v('whatsapp') ?>"></div>
        <div class="form-group"><label class="form-label">Endereco</label><input class="form-control" name="address" value="<?= $v('address') ?>"></div>
        <div class="form-row-3">
            <div class="form-group"><label class="form-label">Cidade</label><input class="form-control" name="city" value="<?= $v('city') ?>"></div>
            <div class="form-group"><label class="form-label">UF</label><input class="form-control" name="state" value="<?= $v('state') ?>"></div>
            <div class="form-group"><label class="form-label">CEP</label><input class="form-control" name="zipcode" value="<?= $v('zipcode') ?>"></div>
        </div>
        <div class="form-group"><label class="form-label">Observacoes</label><textarea class="form-control" name="notes"><?= $v('notes') ?></textarea></div>
        <button class="btn btn-primary" type="submit">Salvar</button>
        <a class="btn btn-ghost" href="<?= url('/clientes') ?>">Cancelar</a>
    </form>
</div></div>
<?php $this->stop(); ?>
