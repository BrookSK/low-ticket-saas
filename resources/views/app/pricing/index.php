<?php
/** @var \App\Core\View $this */
/** @var array|null $result */ /** @var array|null $input */ /** @var array $recent */
$this->extend('layouts.app');
$i = fn($k) => isset($input) ? e($input[$k]) : '';
?>
<?php $this->start('content'); ?>
<div class="grid" style="grid-template-columns:1fr 1fr;gap:1.5rem;align-items:start">
    <div class="card"><div class="card-header"><h3>Descubra quanto cobrar</h3></div><div class="card-body">
        <form method="POST" action="<?= url('/precificador/calcular') ?>">
            <?= csrf_field() ?>
            <div class="form-group"><label class="form-label">Nome do calculo</label><input class="form-control" name="name" value="<?= isset($input) ? '' : '' ?>" placeholder="Ex: Instalacao eletrica"></div>
            <div class="form-row">
                <div class="form-group"><label class="form-label">Custo de materiais (R$)</label><input class="form-control" name="material_cost" value="<?= $i('material_cost') ?>"></div>
                <div class="form-group"><label class="form-label">Custo de mao de obra (R$)</label><input class="form-control" name="labor_cost" value="<?= $i('labor_cost') ?>"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label class="form-label">Horas trabalhadas</label><input class="form-control" name="hours" value="<?= $i('hours') ?>"></div>
                <div class="form-group"><label class="form-label">Deslocamento (R$)</label><input class="form-control" name="travel_cost" value="<?= $i('travel_cost') ?>"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label class="form-label">Custos fixos (R$)</label><input class="form-control" name="fixed_cost" value="<?= $i('fixed_cost') ?>"></div>
                <div class="form-group"><label class="form-label">Outros custos (R$)</label><input class="form-control" name="other_costs" value="<?= $i('other_costs') ?>"></div>
            </div>
            <div class="form-row-3">
                <div class="form-group"><label class="form-label">Impostos (%)</label><input class="form-control" name="taxes_percent" value="<?= $i('taxes_percent') ?>"></div>
                <div class="form-group"><label class="form-label">Taxas (%)</label><input class="form-control" name="fees_percent" value="<?= $i('fees_percent') ?>"></div>
                <div class="form-group"><label class="form-label">Margem desejada (%)</label><input class="form-control" name="margin_percent" value="<?= $i('margin_percent') ?>"></div>
            </div>
            <button class="btn btn-primary" type="submit">Calcular</button>
        </form>
    </div></div>

    <div>
        <?php if (isset($result)): ?>
            <div class="card mb-3"><div class="card-body text-center" style="background:linear-gradient(135deg,#eef2ff,#faf5ff)">
                <div class="text-muted">Preco recomendado</div>
                <div style="font-size:2.4rem;font-weight:800;color:var(--brand-600)"><?= money($result['recommended_price']) ?></div>
                <p class="text-muted text-sm mt-1">
                    Custo do servico: <?= money($result['total_cost']) ?> ·
                    Margem: <?= e($result['margin_percent']) ?>% ·
                    Lucro estimado: <?= money($result['estimated_profit']) ?>
                </p>
                <form method="POST" action="<?= url('/precificador/salvar') ?>" class="mt-2">
                    <?= csrf_field() ?>
                    <?php foreach ($input as $k => $v): ?><input type="hidden" name="<?= e($k) ?>" value="<?= e($v) ?>"><?php endforeach; ?>
                    <button class="btn btn-ghost btn-sm" type="submit">Salvar este calculo</button>
                </form>
            </div></div>
            <div class="grid grid-2 mb-3">
                <div class="stat-card"><div class="label">Preco minimo</div><div class="value" style="font-size:1.3rem"><?= money($result['min_price']) ?></div><div class="trend text-muted">cobre custos + taxas</div></div>
                <div class="stat-card"><div class="label">Custo por hora</div><div class="value" style="font-size:1.3rem"><?= money($result['cost_per_hour']) ?></div></div>
                <div class="stat-card"><div class="label">Preco conservador</div><div class="value" style="font-size:1.3rem"><?= money($result['conservative_price']) ?></div></div>
                <div class="stat-card"><div class="label">Preco agressivo</div><div class="value" style="font-size:1.3rem"><?= money($result['aggressive_price']) ?></div></div>
            </div>
        <?php else: ?>
            <div class="card"><div class="empty-state"><div class="icon">🧮</div><h3>Preencha os campos</h3><p>Informe seus custos e a margem desejada para descobrir o preco ideal.</p></div></div>
        <?php endif; ?>

        <?php if (!empty($recent)): ?>
        <div class="card"><div class="card-header"><h3>Calculos recentes</h3></div>
            <div class="table-wrap" style="border:none"><table class="table">
                <thead><tr><th>Nome</th><th>Custo</th><th>Recomendado</th><th>Data</th></tr></thead>
                <tbody><?php foreach ($recent as $r): ?><tr><td><?= e($r['name']) ?></td><td><?= money($r['total_cost']) ?></td>
                    <td class="fw-600"><?= money($r['recommended_price']) ?></td><td class="text-sm text-muted"><?= e(date('d/m/Y', strtotime($r['created_at']))) ?></td></tr><?php endforeach; ?></tbody>
            </table></div>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php $this->stop(); ?>
