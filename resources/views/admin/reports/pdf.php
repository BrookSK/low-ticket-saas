<?php
/** @var array $headers */ /** @var array $rows */ /** @var string $title */
?>
<!DOCTYPE html>
<html lang="pt-BR"><head><meta charset="UTF-8">
<style>
    body{font-family:DejaVu Sans, sans-serif;font-size:11px;color:#0f172a}
    h1{font-size:18px;margin:0 0 4px}
    .meta{color:#64748b;font-size:10px;margin-bottom:12px}
    table{width:100%;border-collapse:collapse}
    th{background:#f1f5f9;text-align:left;padding:6px 8px;font-size:10px;text-transform:uppercase;border-bottom:1px solid #cbd5e1}
    td{padding:6px 8px;border-bottom:1px solid #e2e8f0}
</style></head>
<body>
    <h1><?= e($title) ?></h1>
    <div class="meta"><?= e(setting('app.name', 'LowTicket SaaS')) ?> · Gerado em <?= date('d/m/Y H:i') ?></div>
    <table>
        <thead><tr><?php foreach ($headers as $h): ?><th><?= e($h) ?></th><?php endforeach; ?></tr></thead>
        <tbody>
        <?php foreach ($rows as $row): ?><tr><?php foreach ($row as $c): ?><td><?= e($c) ?></td><?php endforeach; ?></tr><?php endforeach; ?>
        </tbody>
    </table>
</body></html>
