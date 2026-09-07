<?php
/** @var \App\Core\View $this */
/** @var string $type */ /** @var array $headers */ /** @var array $rows */
$this->extend('layouts.admin');
?>
<?php $this->start('content'); ?>
<div class="flex gap-1 mb-3">
    <a class="btn btn-ghost btn-sm" href="<?= url('/admin/relatorios/' . $type . '/export/csv') ?>">Exportar CSV</a>
    <a class="btn btn-ghost btn-sm" href="<?= url('/admin/relatorios/' . $type . '/export/pdf') ?>" target="_blank">Exportar PDF</a>
</div>
<div class="table-wrap">
    <table class="table">
        <thead><tr><?php foreach ($headers as $h): ?><th><?= e($h) ?></th><?php endforeach; ?></tr></thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr><?php foreach ($row as $cell): ?><td class="text-sm"><?= e($cell) ?></td><?php endforeach; ?></tr>
        <?php endforeach; ?>
        <?php if (empty($rows)): ?><tr><td colspan="<?= count($headers) ?>"><div class="empty-state"><h3>Sem dados no periodo</h3></div></td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php $this->stop(); ?>
