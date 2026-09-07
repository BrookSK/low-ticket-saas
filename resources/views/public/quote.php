<?php
/** @var \App\Core\View $this */
/** @var array $quote */ /** @var array|null $company */ /** @var array|null $customer */ /** @var array|null $user */
use App\Core\Session;
$labels = ['draft'=>'Rascunho','sent'=>'Enviado','viewed'=>'Visualizado','approved'=>'Aprovado','refused'=>'Recusado','expired'=>'Expirado','canceled'=>'Cancelado'];
$providerName = $company['name'] ?? ($user['name'] ?? 'Prestador');
$canRespond = in_array($quote['status'], ['sent','viewed'], true);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orcamento <?= e($quote['number']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= url('assets/css/app.css') ?>">
    <style>body{background:#f1f5f9}.doc{max-width:800px;margin:2rem auto;padding:0 1rem}</style>
</head>
<body>
<div class="doc">
    <?php foreach (['success','error','info'] as $ft): if (Session::hasFlash($ft)): ?>
        <div class="alert alert-<?= $ft === 'error' ? 'error' : ($ft === 'info' ? 'info' : 'success') ?>"><?= e(Session::getFlash($ft)) ?></div>
    <?php endif; endforeach; ?>

    <div class="card">
        <div class="card-body">
            <div class="flex justify-between items-center mb-3" style="flex-wrap:wrap;gap:1rem">
                <div>
                    <div style="font-weight:800;font-size:1.3rem"><?= e($providerName) ?></div>
                    <?php if ($company): ?><div class="text-sm text-muted"><?= e($company['document'] ?? '') ?> · <?= e($company['phone'] ?? '') ?></div><?php endif; ?>
                </div>
                <div class="text-right">
                    <div class="badge badge-blue">Orcamento</div>
                    <div style="font-weight:700;font-size:1.1rem"><?= e($quote['number']) ?></div>
                    <div class="text-sm text-muted"><?= e(date('d/m/Y', strtotime($quote['created_at']))) ?></div>
                </div>
            </div>

            <?php if ($customer): ?>
                <div class="mb-3"><span class="text-muted text-sm">Para:</span> <strong><?= e($customer['name']) ?></strong></div>
            <?php endif; ?>

            <div class="table-wrap"><table class="table">
                <thead><tr><th>Descricao</th><th>Qtd</th><th>Valor unit.</th><th class="text-right">Total</th></tr></thead>
                <tbody><?php foreach ($quote['items'] as $it): ?>
                    <tr><td><?= e($it['description']) ?></td><td><?= rtrim(rtrim(number_format($it['quantity'], 2, ',', '.'), '0'), ',') ?></td>
                        <td><?= money($it['unit_price']) ?></td><td class="text-right"><?= money($it['total']) ?></td></tr>
                <?php endforeach; ?></tbody>
            </table></div>

            <div style="max-width:300px;margin-left:auto" class="mt-3">
                <div class="flex justify-between mb-1"><span class="text-muted">Subtotal</span><span><?= money($quote['subtotal']) ?></span></div>
                <div class="flex justify-between mb-1"><span class="text-muted">Desconto</span><span>- <?= money($quote['discount_type'] === 'percent' ? $quote['subtotal'] * $quote['discount_value'] / 100 : $quote['discount_value']) ?></span></div>
                <div class="flex justify-between mb-1"><span class="text-muted">Acrescimo</span><span><?= money($quote['surcharge']) ?></span></div>
                <div class="flex justify-between mt-2" style="font-size:1.4rem"><strong>Total</strong><strong style="color:var(--brand-600)"><?= money($quote['total']) ?></strong></div>
            </div>

            <?php if ($quote['payment_terms'] || $quote['execution_deadline'] || $quote['valid_until'] || $quote['notes']): ?>
                <hr style="border:none;border-top:1px solid var(--line);margin:1.5rem 0">
                <?php if ($quote['payment_terms']): ?><p class="text-sm"><strong>Condicoes de pagamento:</strong> <?= e($quote['payment_terms']) ?></p><?php endif; ?>
                <?php if ($quote['execution_deadline']): ?><p class="text-sm"><strong>Prazo de execucao:</strong> <?= e($quote['execution_deadline']) ?></p><?php endif; ?>
                <?php if ($quote['valid_until']): ?><p class="text-sm"><strong>Valido ate:</strong> <?= e(date('d/m/Y', strtotime($quote['valid_until']))) ?></p><?php endif; ?>
                <?php if ($quote['notes']): ?><p class="text-sm"><strong>Observacoes:</strong> <?= e($quote['notes']) ?></p><?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="text-center mt-3">
        <a class="btn btn-ghost" href="<?= url('/orcamento/' . $quote['public_token'] . '/pdf') ?>" target="_blank">Baixar PDF</a>
    </div>

    <?php if ($canRespond): ?>
        <div class="card mt-3"><div class="card-body text-center">
            <h3>O que voce achou deste orcamento?</h3>
            <div class="flex gap-1 justify-between" style="max-width:420px;margin:1rem auto 0">
                <form method="POST" action="<?= url('/orcamento/' . $quote['public_token'] . '/aprovar') ?>" style="flex:1"><?= csrf_field() ?>
                    <button class="btn btn-success btn-block btn-lg" type="submit">APROVAR ORCAMENTO</button></form>
                <form method="POST" action="<?= url('/orcamento/' . $quote['public_token'] . '/recusar') ?>" style="flex:1"><?= csrf_field() ?>
                    <button class="btn btn-ghost btn-block btn-lg" type="submit">Recusar</button></form>
            </div>
        </div></div>
    <?php elseif ($quote['status'] === 'approved'): ?>
        <div class="alert alert-success mt-3 text-center">Este orcamento foi aprovado. Obrigado!</div>
    <?php elseif ($quote['status'] === 'refused'): ?>
        <div class="alert alert-warning mt-3 text-center">Este orcamento foi recusado.</div>
    <?php endif; ?>

    <p class="text-center text-muted text-sm mt-3">Gerado com <?= e(setting('app.name', 'Meu Orçamento')) ?></p>
</div>
</body>
</html>
