<?php
/** @var \App\Core\View $this */
/** @var array $metrics */ /** @var array $monthly */ /** @var array $monthlyExp */ /** @var string $start */ /** @var string $end */
$this->extend('layouts.app');
// Monta series mensais combinadas.
$labels = [];
for ($i = 5; $i >= 0; $i--) { $labels[] = date('Y-m', strtotime("-$i months")); }
$recvMap = []; foreach ($monthly as $m) { $recvMap[$m['ym']] = (float) $m['received']; }
$expMap = []; foreach ($monthlyExp as $m) { $expMap[$m['ym']] = (float) $m['paid']; }
$recvSeries = array_map(fn($l) => $recvMap[$l] ?? 0, $labels);
$expSeries = array_map(fn($l) => $expMap[$l] ?? 0, $labels);
?>
<?php $this->start('content'); ?>
<form method="GET" class="flex gap-1 mb-3" style="flex-wrap:wrap">
    <input class="form-control" type="date" name="start" value="<?= e($start) ?>" style="max-width:170px">
    <input class="form-control" type="date" name="end" value="<?= e($end) ?>" style="max-width:170px">
    <button class="btn btn-ghost" type="submit">Filtrar</button>
</form>

<div class="grid grid-4 mb-3">
    <div class="stat-card"><div class="label">Faturamento</div><div class="value"><?= money($metrics['billing']) ?></div></div>
    <div class="stat-card"><div class="label">Recebido</div><div class="value" style="color:var(--success)"><?= money($metrics['received']) ?></div></div>
    <div class="stat-card"><div class="label">A receber</div><div class="value"><?= money($metrics['pending_rev']) ?></div></div>
    <div class="stat-card"><div class="label">Despesas pagas</div><div class="value" style="color:var(--danger)"><?= money($metrics['expenses']) ?></div></div>
</div>
<div class="grid grid-4 mb-3">
    <div class="stat-card"><div class="label">Despesas pendentes</div><div class="value"><?= money($metrics['pending_exp']) ?></div></div>
    <div class="stat-card"><div class="label">Lucro (realizado)</div><div class="value" style="color:<?= $metrics['profit']>=0?'var(--success)':'var(--danger)' ?>"><?= money($metrics['profit']) ?></div></div>
    <div class="stat-card"><div class="label">Contas vencidas</div><div class="value" style="color:var(--danger)"><?= money($metrics['overdue']) ?></div></div>
    <div class="stat-card"><div class="label">A vencer</div><div class="value"><?= money($metrics['upcoming']) ?></div></div>
</div>

<div class="card"><div class="card-header"><h3>Comparativo mensal</h3></div><div class="card-body">
    <canvas id="financeChart" height="90"></canvas>
</div></div>
<?php $this->stop(); ?>
<?php $this->start('scripts'); ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
new Chart(document.getElementById('financeChart'), {
    type:'bar',
    data:{labels:<?= json_encode($labels) ?>,datasets:[
        {label:'Receitas',data:<?= json_encode($recvSeries) ?>,backgroundColor:'#16a34a'},
        {label:'Despesas',data:<?= json_encode($expSeries) ?>,backgroundColor:'#dc2626'}
    ]},
    options:{scales:{y:{beginAtZero:true}}}
});
</script>
<?php $this->stop(); ?>
