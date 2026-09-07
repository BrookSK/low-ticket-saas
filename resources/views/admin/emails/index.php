<?php
/** @var \App\Core\View $this */
/** @var array $templates */
$this->extend('layouts.admin');
?>
<?php $this->start('content'); ?>
<div class="table-wrap">
    <table class="table">
        <thead><tr><th>Template</th><th>Assunto</th><th>Ativo</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($templates as $t): ?>
            <tr><td class="fw-600"><?= e($t['name']) ?></td><td class="text-sm"><?= e($t['subject']) ?></td>
                <td><?= $t['is_active'] ? '<span class="badge badge-green">Sim</span>' : '<span class="badge badge-gray">Nao</span>' ?></td>
                <td><a class="btn btn-ghost btn-sm" href="<?= url('/admin/emails/' . $t['id'] . '/editar') ?>">Editar</a></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php $this->stop(); ?>
