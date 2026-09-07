<?php
/** @var \App\Core\View $this */
/** @var array $rows */
$this->extend('layouts.admin');
?>
<?php $this->start('content'); ?>
<div class="table-wrap">
    <table class="table">
        <thead><tr><th>Origem</th><th>Meio</th><th>Campanha</th><th>Visitantes</th><th>Vendas</th><th>Receita</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr><td><?= e($r['first_source'] ?? 'direct') ?></td><td><?= e($r['first_medium'] ?? 'none') ?></td>
                <td><?= e($r['first_campaign'] ?? '-') ?></td><td><?= (int) $r['visitors'] ?></td>
                <td><?= (int) $r['orders'] ?></td><td><?= money($r['revenue']) ?></td></tr>
        <?php endforeach; ?>
        <?php if (empty($rows)): ?><tr><td colspan="6"><div class="empty-state"><h3>Sem dados de atribuicao</h3><p>Os dados aparecem conforme o trafego chega com UTMs.</p></div></td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php $this->stop(); ?>
