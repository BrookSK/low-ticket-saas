<?php
/** @var \App\Core\View $this */
/** @var array $schema */
/** @var string $group */
/** @var array $groupData */
/** @var array $values */
$this->extend('layouts.admin');
?>
<?php $this->start('content'); ?>
<div class="grid" style="grid-template-columns:220px 1fr;gap:1.5rem;align-items:start">
    <div class="card">
        <nav style="padding:.5rem">
            <?php foreach ($schema as $g => $data): ?>
                <a class="nav-link <?= $g === $group ? 'active' : '' ?>" style="color:<?= $g === $group ? '#fff' : 'var(--ink-2)' ?>;background:<?= $g === $group ? 'var(--brand)' : 'transparent' ?>"
                   href="<?= url('/admin/configuracoes/' . $g) ?>"><?= e($data['label']) ?></a>
            <?php endforeach; ?>
        </nav>
    </div>

    <div class="card">
        <div class="card-header"><h2><?= e($groupData['label']) ?></h2></div>
        <div class="card-body">
            <form method="POST" action="<?= url('/admin/configuracoes/' . $group) ?>">
                <?= csrf_field() ?>
                <?php foreach ($groupData['fields'] as $key => $field):
                    [$label, $type] = [$field[0], $field[1]];
                    $sensitive = $field[2] ?? false;
                    $options = $field[3] ?? null;
                    $hint = $field[4] ?? null;
                    $inputName = str_replace('.', '__', $key);
                    $value = $values[$key] ?? '';
                ?>
                    <div class="form-group">
                        <label class="form-label" for="<?= e($inputName) ?>"><?= e($label) ?></label>
                        <?php if ($type === 'select'): ?>
                            <select class="form-control" id="<?= e($inputName) ?>" name="<?= e($inputName) ?>">
                                <?php foreach (($options ?? []) as $optVal => $optLabel): ?>
                                    <option value="<?= e($optVal) ?>" <?= (string) $value === (string) $optVal ? 'selected' : '' ?>><?= e($optLabel) ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php elseif ($type === 'toggle'): ?>
                            <label style="display:inline-flex;align-items:center;gap:.5rem;cursor:pointer">
                                <input type="checkbox" name="<?= e($inputName) ?>" value="1" <?= $value ? 'checked' : '' ?>>
                                <span class="text-muted text-sm">Ativado</span>
                            </label>
                        <?php elseif ($type === 'textarea'): ?>
                            <textarea class="form-control" id="<?= e($inputName) ?>" name="<?= e($inputName) ?>"><?= e($value === '__SET__' ? '' : $value) ?></textarea>
                        <?php else: ?>
                            <input class="form-control"
                                   type="<?= $type === 'password' ? 'password' : ($type === 'number' ? 'number' : ($type === 'email' ? 'email' : 'text')) ?>"
                                   id="<?= e($inputName) ?>" name="<?= e($inputName) ?>"
                                   value="<?= $value === '__SET__' ? '' : e($value) ?>"
                                   placeholder="<?= $value === '__SET__' ? '•••••••• (preenchido - deixe em branco para manter)' : '' ?>">
                        <?php endif; ?>
                        <?php if ($hint): ?><div class="form-hint"><?= e($hint) ?></div><?php endif; ?>
                        <?php if ($sensitive): ?><div class="form-hint">🔒 Armazenado de forma criptografada.</div><?php endif; ?>
                    </div>
                <?php endforeach; ?>
                <button type="submit" class="btn btn-primary">Salvar configuracoes</button>
            </form>
        </div>
    </div>
</div>
<?php $this->stop(); ?>
