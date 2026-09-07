<?php
/** @var \App\Core\View $this */
/** @var array $user */ /** @var array|null $company */
$this->extend('layouts.app');
?>
<?php $this->start('content'); ?>
<div class="grid grid-2" style="align-items:start">
    <div class="card"><div class="card-header"><h3>Perfil</h3></div><div class="card-body">
        <form method="POST" action="<?= url('/configuracoes/perfil') ?>"><?= csrf_field() ?>
            <div class="form-group"><label class="form-label">Nome</label><input class="form-control" name="name" value="<?= e($user['name']) ?>" required></div>
            <div class="form-group"><label class="form-label">E-mail</label><input class="form-control" value="<?= e($user['email']) ?>" disabled></div>
            <div class="form-row">
                <div class="form-group"><label class="form-label">Telefone</label><input class="form-control" name="phone" value="<?= e($user['phone']) ?>"></div>
                <div class="form-group"><label class="form-label">WhatsApp</label><input class="form-control" name="whatsapp" value="<?= e($user['whatsapp']) ?>"></div>
            </div>
            <button class="btn btn-primary" type="submit">Salvar perfil</button>
        </form>
    </div></div>

    <div class="card"><div class="card-header"><h3>Alterar senha</h3></div><div class="card-body">
        <form method="POST" action="<?= url('/configuracoes/senha') ?>"><?= csrf_field() ?>
            <div class="form-group"><label class="form-label">Senha atual</label><input class="form-control" type="password" name="current_password" required></div>
            <div class="form-group"><label class="form-label">Nova senha</label><input class="form-control" type="password" name="password" required></div>
            <div class="form-group"><label class="form-label">Confirmar nova senha</label><input class="form-control" type="password" name="password_confirmation" required></div>
            <button class="btn btn-primary" type="submit">Alterar senha</button>
        </form>
    </div></div>
</div>

<div class="card mt-3"><div class="card-header"><h3>Dados da empresa (aparecem no PDF do orcamento)</h3></div><div class="card-body">
    <form method="POST" action="<?= url('/configuracoes/empresa') ?>"><?= csrf_field() ?>
        <div class="form-row">
            <div class="form-group"><label class="form-label">Nome / Razao social</label><input class="form-control" name="company_name" value="<?= e($company['name'] ?? '') ?>"></div>
            <div class="form-group"><label class="form-label">CPF / CNPJ</label><input class="form-control" name="company_document" value="<?= e($company['document'] ?? '') ?>"></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label class="form-label">E-mail</label><input class="form-control" name="company_email" value="<?= e($company['email'] ?? '') ?>"></div>
            <div class="form-group"><label class="form-label">Telefone</label><input class="form-control" name="company_phone" value="<?= e($company['phone'] ?? '') ?>"></div>
        </div>
        <div class="form-group"><label class="form-label">Endereco</label><input class="form-control" name="company_address" value="<?= e($company['address'] ?? '') ?>"></div>
        <div class="form-row-3">
            <div class="form-group"><label class="form-label">Cidade</label><input class="form-control" name="company_city" value="<?= e($company['city'] ?? '') ?>"></div>
            <div class="form-group"><label class="form-label">UF</label><input class="form-control" name="company_state" value="<?= e($company['state'] ?? '') ?>"></div>
            <div class="form-group"><label class="form-label">CEP</label><input class="form-control" name="company_zipcode" value="<?= e($company['zipcode'] ?? '') ?>"></div>
        </div>
        <button class="btn btn-primary" type="submit">Salvar empresa</button>
    </form>
</div></div>

<div class="card mt-3" style="border-color:#fecaca"><div class="card-header"><h3 style="color:var(--danger)">Excluir conta</h3></div><div class="card-body">
    <p class="text-muted">Esta acao e permanente e remove todos os seus dados (LGPD). Digite <strong>EXCLUIR</strong> para confirmar.</p>
    <form method="POST" action="<?= url('/conta/excluir') ?>" data-confirm="Tem certeza? Esta acao nao pode ser desfeita."><?= csrf_field() ?>
        <div class="flex gap-1">
            <input class="form-control" name="confirm" placeholder="EXCLUIR" style="max-width:200px">
            <button class="btn btn-danger" type="submit">Excluir minha conta</button>
        </div>
    </form>
</div></div>
<?php $this->stop(); ?>
