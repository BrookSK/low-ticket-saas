<?php
/** @var \App\Core\View $this */
/** @var array $template */
$this->extend('layouts.admin');
?>
<?php $this->start('content'); ?>
<div class="card" style="max-width:820px">
    <div class="card-header"><h2><?= e($template['name']) ?></h2><span class="badge badge-gray"><?= e($template['slug']) ?></span></div>
    <div class="card-body">
        <form method="POST" action="<?= url('/admin/emails/' . $template['id']) ?>">
            <?= csrf_field() ?><input type="hidden" name="_method" value="PUT">
            <div class="form-group"><label class="form-label">Assunto</label>
                <input class="form-control" name="subject" value="<?= e($template['subject']) ?>" required></div>
            <div class="form-group"><label class="form-label">Conteudo (HTML)</label>
                <textarea class="form-control" name="body" style="min-height:280px;font-family:monospace"><?= e($template['body']) ?></textarea>
                <div class="form-hint">Variaveis disponiveis: {{name}}, {{app_name}}, {{dashboard_url}}, {{confirm_url}}, {{reset_url}}, {{quote_url}}, {{quote_total}}, etc.</div></div>
            <div class="form-group"><label><input type="checkbox" name="is_active" value="1" <?= $template['is_active'] ? 'checked' : '' ?>> Ativo</label></div>
            <button class="btn btn-primary" type="submit">Salvar</button>
            <a class="btn btn-ghost" href="<?= url('/admin/emails') ?>">Voltar</a>
        </form>
    </div>
</div>
<?php $this->stop(); ?>
