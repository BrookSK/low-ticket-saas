<?php
/** @var \App\Core\View $this */
/** @var array $quote */ /** @var array|null $customer */ /** @var array $history */
$this->extend('layouts.app');
$badge = ['draft'=>'badge-gray','sent'=>'badge-blue','viewed'=>'badge-purple','approved'=>'badge-green','refused'=>'badge-red','expired'=>'badge-yellow','canceled'=>'badge-gray'];
$labels = ['draft'=>'Rascunho','sent'=>'Enviado','viewed'=>'Visualizado','approved'=>'Aprovado','refused'=>'Recusado','expired'=>'Expirado','canceled'=>'Cancelado'];
$publicUrl = url('/orcamento/' . $quote['public_token']);
$waText = rawurlencode('Ola! Segue seu orcamento ' . $quote['number'] . ': ' . $publicUrl);
$waPhone = $customer ? preg_replace('/\D+/', '', (string) ($customer['whatsapp'] ?? $customer['phone'] ?? '')) : '';
?>
<?php $this->start('content'); ?>
<div class="flex justify-between items-center mb-3" style="flex-wrap:wrap;gap:.75rem">
    <div class="flex items-center gap-2">
        <h1 style="margin:0"><?= e($quote['number']) ?></h1>
        <span class="badge <?= $badge[$quote['status']] ?? 'badge-gray' ?>"><?= e($labels[$quote['status']] ?? $quote['status']) ?></span>
    </div>
    <div class="flex gap-1" style="flex-wrap:wrap">
        <a class="btn btn-ghost btn-sm" href="<?= url('/orcamentos/' . $quote['id'] . '/pdf') ?>" target="_blank">PDF</a>
        <a class="btn btn-ghost btn-sm" href="<?= url('/orcamentos/' . $quote['id'] . '/editar') ?>">Editar</a>
        <form method="POST" action="<?= url('/orcamentos/' . $quote['id'] . '/duplicar') ?>" style="display:inline"><?= csrf_field() ?><button class="btn btn-ghost btn-sm">Duplicar</button></form>
        <?php if (in_array($quote['status'], ['draft','sent','viewed'], true)): ?>
            <form method="POST" action="<?= url('/orcamentos/' . $quote['id'] . '/enviar') ?>" style="display:inline"><?= csrf_field() ?><button class="btn btn-primary btn-sm">Marcar como enviado</button></form>
        <?php endif; ?>
    </div>
</div>

<div class="grid" style="grid-template-columns:1fr 320px;gap:1.5rem;align-items:start">
    <div class="card"><div class="card-body">
        <?php if ($customer): ?><p class="text-muted mb-2"><strong>Cliente:</strong> <?= e($customer['name']) ?></p><?php endif; ?>
        <?php if ($quote['title']): ?><h3><?= e($quote['title']) ?></h3><?php endif; ?>
        <div class="table-wrap" style="border:none"><table class="table">
            <thead><tr><th>Descricao</th><th>Qtd</th><th>Unit.</th><th class="text-right">Total</th></tr></thead>
            <tbody><?php foreach ($quote['items'] as $it): ?>
                <tr><td><?= e($it['description']) ?></td><td><?= rtrim(rtrim(number_format($it['quantity'], 2, ',', '.'), '0'), ',') ?></td>
                    <td><?= money($it['unit_price']) ?></td><td class="text-right"><?= money($it['total']) ?></td></tr>
            <?php endforeach; ?></tbody>
        </table></div>
        <div style="max-width:280px;margin-left:auto" class="mt-2">
            <div class="flex justify-between mb-1"><span class="text-muted">Subtotal</span><span><?= money($quote['subtotal']) ?></span></div>
            <div class="flex justify-between mb-1"><span class="text-muted">Desconto</span><span>- <?= money($quote['discount_type'] === 'percent' ? $quote['subtotal'] * $quote['discount_value'] / 100 : $quote['discount_value']) ?></span></div>
            <div class="flex justify-between mb-1"><span class="text-muted">Acrescimo</span><span><?= money($quote['surcharge']) ?></span></div>
            <div class="flex justify-between mt-2" style="font-size:1.2rem"><strong>Total</strong><strong style="color:var(--brand-600)"><?= money($quote['total']) ?></strong></div>
        </div>
        <?php if ($quote['notes'] || $quote['payment_terms'] || $quote['execution_deadline']): ?>
            <hr style="border:none;border-top:1px solid var(--line);margin:1.25rem 0">
            <?php if ($quote['payment_terms']): ?><p class="text-sm"><strong>Pagamento:</strong> <?= e($quote['payment_terms']) ?></p><?php endif; ?>
            <?php if ($quote['execution_deadline']): ?><p class="text-sm"><strong>Prazo:</strong> <?= e($quote['execution_deadline']) ?></p><?php endif; ?>
            <?php if ($quote['notes']): ?><p class="text-sm"><strong>Obs:</strong> <?= e($quote['notes']) ?></p><?php endif; ?>
        <?php endif; ?>
    </div></div>

    <div>
        <div class="card mb-3"><div class="card-header"><h3>Compartilhar</h3></div><div class="card-body">
            <label class="form-label">Link publico</label>
            <div class="flex gap-1 mb-2">
                <input class="form-control" id="publicLink" value="<?= e($publicUrl) ?>" readonly style="font-size:.8rem">
                <button class="btn btn-ghost btn-sm" type="button" onclick="navigator.clipboard.writeText(document.getElementById('publicLink').value);this.textContent='✓'">Copiar</button>
            </div>
            <a class="btn btn-success btn-block mb-1" target="_blank" href="https://wa.me/<?= e($waPhone) ?>?text=<?= $waText ?>">Enviar pelo WhatsApp</a>
            <a class="btn btn-ghost btn-block" target="_blank" href="<?= e($publicUrl) ?>">Abrir visualizacao publica</a>
        </div></div>

        <div class="card"><div class="card-header"><h3>Historico</h3></div><div class="card-body">
            <?php foreach ($history as $h): ?>
                <div class="mb-2 text-sm">
                    <span class="badge <?= $badge[$h['to_status']] ?? 'badge-gray' ?>"><?= e($labels[$h['to_status']] ?? $h['to_status']) ?></span>
                    <span class="text-muted"><?= e(date('d/m/Y H:i', strtotime($h['created_at']))) ?> · <?= e($h['actor']) ?></span>
                </div>
            <?php endforeach; ?>
        </div></div>
    </div>
</div>
<?php $this->stop(); ?>
