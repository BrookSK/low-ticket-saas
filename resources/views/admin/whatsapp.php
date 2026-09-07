<?php
/** @var \App\Core\View $this */
/** @var array $templates */ /** @var bool $configured */
$this->extend('layouts.admin');
?>
<?php $this->start('content'); ?>
<?php if (!$configured): ?>
    <div class="alert alert-warning">WhatsApp nao configurado. Defina o provedor e o token em
        <a href="<?= url('/admin/configuracoes/whatsapp') ?>">Configuracoes &gt; WhatsApp</a>.</div>
<?php else: ?>
    <div class="card mb-3"><div class="card-body">
        <form method="POST" action="<?= url('/admin/whatsapp/testar') ?>" class="flex gap-1 items-center">
            <?= csrf_field() ?>
            <input class="form-control" name="to" placeholder="Numero (E.164) para teste" style="max-width:280px">
            <button class="btn btn-ghost" type="submit">Enviar teste</button>
        </form>
    </div></div>
<?php endif; ?>

<div class="grid" style="grid-template-columns:1fr 360px;gap:1.5rem;align-items:start">
    <div class="table-wrap">
        <table class="table"><thead><tr><th>Template</th><th>Slug</th><th>Ativo</th></tr></thead>
        <tbody><?php foreach ($templates as $t): ?><tr><td class="fw-600"><?= e($t['name']) ?></td><td class="text-sm text-muted"><?= e($t['slug']) ?></td>
            <td><?= $t['is_active'] ? '<span class="badge badge-green">Sim</span>' : '<span class="badge badge-gray">Nao</span>' ?></td></tr><?php endforeach; ?></tbody></table>
    </div>
    <div class="card"><div class="card-header"><h3>Novo template</h3></div><div class="card-body">
        <form method="POST" action="<?= url('/admin/whatsapp/templates') ?>"><?= csrf_field() ?>
            <div class="form-group"><label class="form-label">Nome</label><input class="form-control" name="name" required></div>
            <div class="form-group"><label class="form-label">Slug</label><input class="form-control" name="slug"></div>
            <div class="form-group"><label class="form-label">Mensagem</label><textarea class="form-control" name="body" required></textarea>
                <div class="form-hint">Variaveis: {{name}}, {{customer_name}}, {{quote_total}}, {{quote_url}}, {{checkout_url}}</div></div>
            <div class="form-group"><label><input type="checkbox" name="is_active" value="1" checked> Ativo</label></div>
            <button class="btn btn-primary btn-block" type="submit">Salvar</button>
        </form>
    </div></div>
</div>
<?php $this->stop(); ?>
