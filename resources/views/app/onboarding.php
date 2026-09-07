<?php
/** @var \App\Core\View $this */
$this->extend('layouts.auth');
$title = 'Bem-vindo';
$businesses = ['Eletricista','Pintor','Marceneiro','Pedreiro','Tecnico','Designer','Fotografo','Consultor','Outro'];
$goals = ['Fazer orcamentos' => 'orcamentos', 'Controlar financeiro' => 'financeiro', 'Descobrir quanto cobrar' => 'precificador', 'Todos' => 'todos'];
?>
<?php $this->start('content'); ?>
    <h1 style="font-size:1.4rem;text-align:center">Vamos personalizar sua experiencia</h1>
    <p class="text-muted text-center mb-3">Responda 2 perguntas rapidas.</p>
    <form method="POST" action="<?= url('/onboarding') ?>">
        <?= csrf_field() ?>
        <div class="form-group">
            <label class="form-label">Qual e o seu tipo de negocio?</label>
            <select class="form-control" name="business_type">
                <?php foreach ($businesses as $b): ?><option value="<?= e(strtolower($b)) ?>"><?= e($b) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Qual e o seu objetivo?</label>
            <select class="form-control" name="goal">
                <?php foreach ($goals as $label => $v): ?><option value="<?= e($v) ?>"><?= e($label) ?></option><?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary btn-block btn-lg">Comecar a usar</button>
    </form>
<?php $this->stop(); ?>
<?php $this->start('links'); ?>
    <a href="<?= url('/dashboard') ?>">Pular</a>
<?php $this->stop(); ?>
