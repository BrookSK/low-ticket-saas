<?php
/** @var \App\Core\View $this */
/** @var array $rules */ /** @var array $emailTemplates */ /** @var array $waTemplates */ /** @var array $events */
$this->extend('layouts.admin');
?>
<?php $this->start('content'); ?>
<div class="grid" style="grid-template-columns:1fr 380px;gap:1.5rem;align-items:start">
    <div class="table-wrap">
        <table class="table"><thead><tr><th>Nome</th><th>Evento</th><th>Canal</th><th>Atraso</th><th>Ativo</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rules as $r): ?>
            <tr><td class="fw-600"><?= e($r['name']) ?></td><td class="text-sm"><?= e($events[$r['event']] ?? $r['event']) ?></td>
                <td><span class="badge badge-blue"><?= e($r['channel']) ?></span></td>
                <td class="text-sm"><?= (int) $r['delay_minutes'] ?> min</td>
                <td><?= $r['is_active'] ? '<span class="badge badge-green">Sim</span>' : '<span class="badge badge-gray">Nao</span>' ?></td>
                <td><form method="POST" action="<?= url('/admin/automacoes/' . $r['id'] . '/toggle') ?>" style="display:inline"><?= csrf_field() ?>
                    <button class="btn btn-ghost btn-sm">Alternar</button></form></td></tr>
        <?php endforeach; ?>
        <?php if (empty($rules)): ?><tr><td colspan="6"><div class="empty-state"><h3>Nenhuma automacao</h3></div></td></tr><?php endif; ?>
        </tbody></table>
    </div>
    <div class="card"><div class="card-header"><h3>Nova automacao</h3></div><div class="card-body">
        <form method="POST" action="<?= url('/admin/automacoes') ?>"><?= csrf_field() ?>
            <div class="form-group"><label class="form-label">Nome</label><input class="form-control" name="name" required></div>
            <div class="form-group"><label class="form-label">Evento</label>
                <select class="form-control" name="event"><?php foreach ($events as $v => $l): ?><option value="<?= $v ?>"><?= e($l) ?></option><?php endforeach; ?></select></div>
            <div class="form-group"><label class="form-label">Canal</label>
                <select class="form-control" name="channel"><option value="email">E-mail</option><option value="whatsapp">WhatsApp</option><option value="internal">Notificacao interna</option></select></div>
            <div class="form-group"><label class="form-label">Template</label>
                <select class="form-control" name="template_slug">
                    <option value="">(nenhum)</option>
                    <optgroup label="E-mail"><?php foreach ($emailTemplates as $t): ?><option value="<?= e($t['slug']) ?>"><?= e($t['name']) ?></option><?php endforeach; ?></optgroup>
                    <optgroup label="WhatsApp"><?php foreach ($waTemplates as $t): ?><option value="<?= e($t['slug']) ?>"><?= e($t['name']) ?></option><?php endforeach; ?></optgroup>
                </select></div>
            <div class="form-group"><label class="form-label">Atraso (minutos)</label><input class="form-control" type="number" name="delay_minutes" value="0"></div>
            <div class="form-group"><label><input type="checkbox" name="is_active" value="1" checked> Ativo</label></div>
            <button class="btn btn-primary btn-block" type="submit">Criar</button>
        </form>
    </div></div>
</div>
<?php $this->stop(); ?>
