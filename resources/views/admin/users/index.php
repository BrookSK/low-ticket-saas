<?php
/** @var \App\Core\View $this */
/** @var array $users */
$this->extend('layouts.admin');
$badge = ['active' => 'badge-green', 'blocked' => 'badge-red', 'pending' => 'badge-yellow'];
?>
<?php $this->start('content'); ?>
<form method="GET" class="mb-3 flex gap-1">
    <input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Buscar por nome ou e-mail" style="max-width:320px">
    <button class="btn btn-ghost" type="submit">Buscar</button>
</form>
<div class="table-wrap">
    <table class="table">
        <thead><tr><th>Nome</th><th>E-mail</th><th>Telefone</th><th>Status</th><th>Cadastro</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($users as $u): ?>
            <tr>
                <td class="fw-600"><?= e($u['name']) ?></td>
                <td><?= e($u['email']) ?></td>
                <td class="text-muted text-sm"><?= e($u['phone'] ?? '-') ?></td>
                <td><span class="badge <?= $badge[$u['status']] ?? 'badge-gray' ?>"><?= e($u['status']) ?></span></td>
                <td class="text-muted text-sm"><?= e(date('d/m/Y', strtotime($u['created_at']))) ?></td>
                <td><a class="btn btn-ghost btn-sm" href="<?= url('/admin/usuarios/' . $u['id']) ?>">Ver</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<p class="text-muted text-sm mt-2"><?= (int) $total ?> usuario(s) — pagina <?= $page ?> de <?= max(1, $pages) ?></p>
<?php $this->stop(); ?>
