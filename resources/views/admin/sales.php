<?php
/** @var \App\Core\View $this */
/** @var string $range */ /** @var array $metrics */ /** @var array $byProduct */ /** @var array $bySource */
$this->extend('layouts.admin');
$ranges = ['today'=>'Hoje','yesterday'=>'Ontem','7d'=>'7 dias','30d'=>'30 dias','this_month'=>'Mes atual','last_month'=>'Mes anterior'];
?>
<?php $this->start('content'); ?>
<div class="flex gap-1 mb-3" style="flex-wrap:wrap">
    <?php foreach ($ranges as $k => $label): ?>
        <a class="btn <?= $range === $k ? 'btn-primary' : 'btn-ghost' ?> btn-sm" href="<?= url('/admin/vendas?range=' . $k) ?>"><?= $label ?></a>
    <?php endforeach; ?>
</div>
<div class="grid grid-4 mb-3">
    <div class="stat-card"><div class="label">Faturamento</div><div class="value"><?= money($metrics['revenue']) ?></div></div>
    <div class="stat-card"><div class="label">Vendas</div><div class="value"><?= (int) $metrics['sales'] ?></div></div>
    <div class="stat-card"><div class="label">Ticket medio</div><div class="value"><?= money($metrics['aov']) ?></div></div>
    <div class="stat-card"><div class="label">Clientes</div><div class="value"><?= (int) $metrics['customers'] ?></div></div>
</div>
<div class="grid grid-4 mb-3">
    <div class="stat-card"><div class="label">Upsells</div><div class="value"><?= (int) $metrics['upsells'] ?></div></div>
    <div class="stat-card"><div class="label">Faturamento upsell</div><div class="value"><?= money($metrics['upsell_revenue']) ?></div></div>
    <div class="stat-card"><div class="label">Taxa de conversao</div><div class="value"><?= e($metrics['conversion']) ?>%</div><div class="trend text-muted">vendas / checkouts</div></div>
    <div class="stat-card"><div class="label">Periodo</div><div class="value" style="font-size:1rem"><?= $ranges[$range] ?? $range ?></div></div>
</div>
<div class="card mb-3"><div class="card-header"><h3>Vendas no periodo</h3></div><div class="card-body">
    <canvas id="salesChart" height="90"></canvas>
</div></div>
<div class="grid grid-2">
    <div class="card"><div class="card-header"><h3>Por produto</h3></div>
        <div class="table-wrap" style="border:none"><table class="table"><thead><tr><th>Produto</th><th>Qtd</th><th>Receita</th></tr></thead>
        <tbody><?php foreach ($byProduct as $p): ?><tr><td><?= e($p['name']) ?></td><td><?= (int) $p['qty'] ?></td><td><?= money($p['revenue']) ?></td></tr><?php endforeach; ?>
        <?php if (empty($byProduct)): ?><tr><td colspan="3" class="text-muted">Sem dados.</td></tr><?php endif; ?></tbody></table></div>
    </div>
    <div class="card"><div class="card-header"><h3>Por origem</h3></div>
        <div class="table-wrap" style="border:none"><table class="table"><thead><tr><th>Origem</th><th>Qtd</th><th>Receita</th></tr></thead>
        <tbody><?php foreach ($bySource as $s): ?><tr><td><?= e($s['source']) ?></td><td><?= (int) $s['qty'] ?></td><td><?= money($s['revenue']) ?></td></tr><?php endforeach; ?>
        <?php if (empty($bySource)): ?><tr><td colspan="3" class="text-muted">Sem dados.</td></tr><?php endif; ?></tbody></table></div>
    </div>
</div>
<?php $this->stop(); ?>
<?php $this->start('scripts'); ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
fetch('<?= url('/admin/vendas/dados?range=' . $range) ?>').then(r=>r.json()).then(function(d){
    var s = d.series || [];
    new Chart(document.getElementById('salesChart'), {
        type:'line',
        data:{labels:s.map(x=>x.d),datasets:[{label:'Faturamento',data:s.map(x=>x.revenue),borderColor:'#6366f1',backgroundColor:'rgba(99,102,241,.1)',tension:.3,fill:true}]},
        options:{plugins:{legend:{display:false}},scales:{y:{beginAtZero:true}}}
    });
});
</script>
<?php $this->stop(); ?>
