<?php
/** @var \App\Core\View $this */
$this->extend('layouts.admin');
$reports = [
    ['sales','Vendas','Pedidos, produtos, origem e status.'],
    ['finance','Financeiro','Faturamento da plataforma por mes.'],
    ['marketing','Marketing','Origem, campanha, conversao e receita.'],
    ['users','Usuarios','Novos, ativos e pagantes.'],
];
?>
<?php $this->start('content'); ?>
<div class="grid grid-2">
    <?php foreach ($reports as [$type, $name, $desc]): ?>
        <div class="card"><div class="card-body">
            <h3><?= e($name) ?></h3>
            <p class="text-muted"><?= e($desc) ?></p>
            <div class="flex gap-1">
                <a class="btn btn-primary btn-sm" href="<?= url('/admin/relatorios/' . $type) ?>">Ver</a>
                <a class="btn btn-ghost btn-sm" href="<?= url('/admin/relatorios/' . $type . '/export/csv') ?>">CSV</a>
                <a class="btn btn-ghost btn-sm" href="<?= url('/admin/relatorios/' . $type . '/export/pdf') ?>" target="_blank">PDF</a>
            </div>
        </div></div>
    <?php endforeach; ?>
</div>
<?php $this->stop(); ?>
