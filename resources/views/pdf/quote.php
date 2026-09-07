<?php
/** @var array $quote */ /** @var array|null $company */ /** @var array|null $customer */ /** @var array|null $user */
$providerName = $company['name'] ?? ($user['name'] ?? 'Prestador');
$discount = $quote['discount_type'] === 'percent' ? $quote['subtotal'] * $quote['discount_value'] / 100 : $quote['discount_value'];
?>
<!DOCTYPE html>
<html lang="pt-BR"><head><meta charset="UTF-8">
<style>
    body{font-family:DejaVu Sans, sans-serif;font-size:12px;color:#1e293b;margin:0}
    .header{display:flex;justify-content:space-between;border-bottom:3px solid #6366f1;padding-bottom:12px;margin-bottom:20px}
    .provider{font-size:18px;font-weight:bold;color:#0f172a}
    .muted{color:#64748b;font-size:10px}
    .doc-title{text-align:right}
    .doc-title .num{font-size:16px;font-weight:bold;color:#6366f1}
    .box{background:#f8fafc;border-radius:8px;padding:10px 12px;margin-bottom:16px}
    table{width:100%;border-collapse:collapse;margin-top:8px}
    th{background:#eef2ff;text-align:left;padding:8px;font-size:10px;text-transform:uppercase;color:#4338ca}
    td{padding:8px;border-bottom:1px solid #e2e8f0}
    .right{text-align:right}
    .totals{width:260px;margin-left:auto;margin-top:12px}
    .totals .row{display:flex;justify-content:space-between;padding:4px 0}
    .totals .total{border-top:2px solid #6366f1;margin-top:6px;padding-top:8px;font-size:16px;font-weight:bold;color:#6366f1}
    .terms{margin-top:20px;font-size:11px}
    .terms p{margin:4px 0}
    .sign{margin-top:50px;display:flex;justify-content:space-between}
    .sign .line{border-top:1px solid #94a3b8;width:45%;text-align:center;padding-top:4px;color:#64748b;font-size:10px}
</style></head>
<body>
    <div class="header">
        <div>
            <div class="provider"><?= e($providerName) ?></div>
            <?php if ($company): ?>
                <div class="muted"><?= e($company['document'] ?? '') ?></div>
                <div class="muted"><?= e($company['phone'] ?? '') ?> <?= $company['email'] ? '· ' . e($company['email']) : '' ?></div>
                <div class="muted"><?= e(trim(($company['address'] ?? '') . ' ' . ($company['city'] ?? '') . ($company['state'] ? '/' . $company['state'] : ''))) ?></div>
            <?php else: ?>
                <div class="muted"><?= e($user['email'] ?? '') ?></div>
            <?php endif; ?>
        </div>
        <div class="doc-title">
            <div>ORCAMENTO</div>
            <div class="num"><?= e($quote['number']) ?></div>
            <div class="muted">Data: <?= e(date('d/m/Y', strtotime($quote['created_at']))) ?></div>
            <?php if ($quote['valid_until']): ?><div class="muted">Valido ate: <?= e(date('d/m/Y', strtotime($quote['valid_until']))) ?></div><?php endif; ?>
        </div>
    </div>

    <?php if ($customer): ?>
    <div class="box">
        <strong>Cliente:</strong> <?= e($customer['name']) ?>
        <?php if ($customer['document']): ?> · <?= e($customer['document']) ?><?php endif; ?><br>
        <span class="muted"><?= e($customer['phone'] ?? '') ?> <?= $customer['email'] ? '· ' . e($customer['email']) : '' ?></span>
    </div>
    <?php endif; ?>

    <?php if ($quote['title']): ?><h3 style="margin:0 0 8px"><?= e($quote['title']) ?></h3><?php endif; ?>

    <table>
        <thead><tr><th>Descricao</th><th>Qtd</th><th>Valor unit.</th><th class="right">Total</th></tr></thead>
        <tbody>
        <?php foreach ($quote['items'] as $it): ?>
            <tr><td><?= e($it['description']) ?></td>
                <td><?= rtrim(rtrim(number_format($it['quantity'], 2, ',', '.'), '0'), ',') ?></td>
                <td>R$ <?= number_format($it['unit_price'], 2, ',', '.') ?></td>
                <td class="right">R$ <?= number_format($it['total'], 2, ',', '.') ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <div class="totals">
        <div class="row"><span>Subtotal</span><span>R$ <?= number_format($quote['subtotal'], 2, ',', '.') ?></span></div>
        <div class="row"><span>Desconto</span><span>- R$ <?= number_format($discount, 2, ',', '.') ?></span></div>
        <div class="row"><span>Acrescimo</span><span>R$ <?= number_format($quote['surcharge'], 2, ',', '.') ?></span></div>
        <div class="row total"><span>TOTAL</span><span>R$ <?= number_format($quote['total'], 2, ',', '.') ?></span></div>
    </div>

    <div class="terms">
        <?php if ($quote['payment_terms']): ?><p><strong>Condicoes de pagamento:</strong> <?= e($quote['payment_terms']) ?></p><?php endif; ?>
        <?php if ($quote['execution_deadline']): ?><p><strong>Prazo de execucao:</strong> <?= e($quote['execution_deadline']) ?></p><?php endif; ?>
        <?php if ($quote['notes']): ?><p><strong>Observacoes:</strong> <?= e($quote['notes']) ?></p><?php endif; ?>
    </div>

    <div class="sign">
        <div class="line"><?= e($providerName) ?></div>
        <div class="line"><?= e($customer['name'] ?? 'Cliente') ?></div>
    </div>
</body></html>
