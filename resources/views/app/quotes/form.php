<?php
/** @var \App\Core\View $this */
/** @var array|null $quote */ /** @var array $items */ /** @var array $customers */ /** @var array $services */ /** @var int $selectedCustomer */
$this->extend('layouts.app');
$isEdit = $quote !== null;
$action = $isEdit ? url('/orcamentos/' . $quote['id']) : url('/orcamentos');
$val = fn($k, $d = '') => $isEdit ? e($quote[$k] ?? $d) : e($d);
$servicesJson = json_encode(array_map(fn($s) => [
    'id' => (int) $s['id'], 'name' => $s['name'], 'price' => (float) ($s['suggested_price'] ?? 0),
], $services), JSON_UNESCAPED_UNICODE);
?>
<?php $this->start('content'); ?>
<form method="POST" action="<?= $action ?>" id="quoteForm">
    <?= csrf_field() ?>
    <?php if ($isEdit): ?><input type="hidden" name="_method" value="PUT"><?php endif; ?>
    <div class="grid" style="grid-template-columns:1fr 320px;gap:1.5rem;align-items:start">
        <div>
            <div class="card mb-3"><div class="card-body">
                <div class="form-row">
                    <div class="form-group"><label class="form-label">Cliente</label>
                        <select class="form-control" name="customer_id">
                            <option value="">— Sem cliente —</option>
                            <?php foreach ($customers as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= (int) $selectedCustomer === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select></div>
                    <div class="form-group"><label class="form-label">Titulo (opcional)</label>
                        <input class="form-control" name="title" value="<?= $val('title') ?>" placeholder="Ex: Instalacao eletrica"></div>
                </div>
            </div></div>

            <div class="card mb-3">
                <div class="card-header"><h3>Itens</h3><button type="button" class="btn btn-ghost btn-sm" id="addItem">+ Adicionar item</button></div>
                <div class="card-body">
                    <div class="table-wrap" style="border:none">
                        <table class="table" id="itemsTable">
                            <thead><tr><th style="width:44%">Descricao</th><th>Qtd</th><th>Valor unit.</th><th>Total</th><th></th></tr></thead>
                            <tbody id="itemsBody"></tbody>
                        </table>
                    </div>
                    <p class="form-hint">Selecione um servico cadastrado ou digite manualmente.</p>
                </div>
            </div>

            <div class="card"><div class="card-body">
                <div class="form-group"><label class="form-label">Observacoes</label><textarea class="form-control" name="notes"><?= $val('notes') ?></textarea></div>
                <div class="form-row">
                    <div class="form-group"><label class="form-label">Condicoes de pagamento</label><input class="form-control" name="payment_terms" value="<?= $val('payment_terms') ?>" placeholder="Ex: 50% entrada, 50% na entrega"></div>
                    <div class="form-group"><label class="form-label">Prazo de execucao</label><input class="form-control" name="execution_deadline" value="<?= $val('execution_deadline') ?>" placeholder="Ex: 5 dias uteis"></div>
                </div>
            </div></div>
        </div>

        <div class="card" style="position:sticky;top:80px">
            <div class="card-header"><h3>Resumo</h3></div>
            <div class="card-body">
                <div class="form-group"><label class="form-label">Validade</label>
                    <input class="form-control" type="date" name="valid_until" value="<?= $val('valid_until') ?>"></div>
                <div class="form-group"><label class="form-label">Desconto</label>
                    <div class="flex gap-1">
                        <input class="form-control" name="discount_value" id="discountValue" value="<?= $isEdit ? e($quote['discount_value']) : '0' ?>">
                        <select class="form-control" name="discount_type" id="discountType" style="max-width:90px">
                            <option value="value" <?= $isEdit && $quote['discount_type'] === 'value' ? 'selected' : '' ?>>R$</option>
                            <option value="percent" <?= $isEdit && $quote['discount_type'] === 'percent' ? 'selected' : '' ?>>%</option>
                        </select>
                    </div></div>
                <div class="form-group"><label class="form-label">Acrescimo (R$)</label>
                    <input class="form-control" name="surcharge" id="surcharge" value="<?= $isEdit ? e($quote['surcharge']) : '0' ?>"></div>
                <hr style="border:none;border-top:1px solid var(--line);margin:1rem 0">
                <div class="flex justify-between mb-1"><span class="text-muted">Subtotal</span><strong id="sumSubtotal">R$ 0,00</strong></div>
                <div class="flex justify-between mb-1"><span class="text-muted">Desconto</span><span id="sumDiscount">- R$ 0,00</span></div>
                <div class="flex justify-between mb-1"><span class="text-muted">Acrescimo</span><span id="sumSurcharge">R$ 0,00</span></div>
                <div class="flex justify-between mt-2" style="font-size:1.2rem"><strong>Total</strong><strong id="sumTotal" style="color:var(--brand-600)">R$ 0,00</strong></div>
                <button class="btn btn-primary btn-block mt-3" type="submit">Salvar orcamento</button>
            </div>
        </div>
    </div>
</form>
<?php $this->stop(); ?>

<?php $this->start('scripts'); ?>
<script>
var SERVICES = <?= $servicesJson ?>;
var INITIAL = <?= json_encode(array_map(fn($it) => [
    'service_id' => (int) ($it['service_id'] ?? 0),
    'description' => $it['description'],
    'quantity' => (float) $it['quantity'],
    'unit_price' => (float) $it['unit_price'],
], $items), JSON_UNESCAPED_UNICODE) ?>;

function brl(v){ return (Number(v)||0).toLocaleString('pt-BR',{minimumFractionDigits:2,maximumFractionDigits:2}); }

function rowTemplate(item){
    item = item || {service_id:0,description:'',quantity:1,unit_price:0};
    var opts = '<option value="">— manual —</option>';
    SERVICES.forEach(function(s){ opts += '<option value="'+s.id+'" data-price="'+s.price+'"'+(s.id===item.service_id?' selected':'')+'>'+s.name+'</option>'; });
    var tr = document.createElement('tr');
    tr.innerHTML =
        '<td><input class="form-control i-desc" name="item_description[]" value="'+ (item.description||'').replace(/"/g,'&quot;') +'" placeholder="Descricao">' +
        '<select class="form-control i-service mt-1" name="item_service_id[]" style="font-size:.8rem">'+opts+'</select></td>' +
        '<td><input class="form-control i-qty" name="item_quantity[]" value="'+item.quantity+'" style="width:70px"></td>' +
        '<td><input class="form-control i-price" name="item_unit_price[]" value="'+item.unit_price+'" style="width:100px"></td>' +
        '<td class="i-total text-right">R$ 0,00</td>' +
        '<td><button type="button" class="btn btn-ghost btn-sm i-remove">×</button></td>';
    return tr;
}

function recalc(){
    var subtotal = 0;
    document.querySelectorAll('#itemsBody tr').forEach(function(tr){
        var q = parseFloat((tr.querySelector('.i-qty').value||'0').replace(',','.'))||0;
        var p = parseFloat((tr.querySelector('.i-price').value||'0').replace(',','.'))||0;
        var t = q*p; subtotal += t;
        tr.querySelector('.i-total').textContent = 'R$ '+brl(t);
    });
    var dtype = document.getElementById('discountType').value;
    var dval = parseFloat((document.getElementById('discountValue').value||'0').replace(',','.'))||0;
    var discount = dtype==='percent' ? subtotal*(dval/100) : dval;
    if(discount>subtotal) discount = subtotal;
    var surcharge = parseFloat((document.getElementById('surcharge').value||'0').replace(',','.'))||0;
    var total = subtotal - discount + surcharge;
    document.getElementById('sumSubtotal').textContent = 'R$ '+brl(subtotal);
    document.getElementById('sumDiscount').textContent = '- R$ '+brl(discount);
    document.getElementById('sumSurcharge').textContent = 'R$ '+brl(surcharge);
    document.getElementById('sumTotal').textContent = 'R$ '+brl(total);
}

var body = document.getElementById('itemsBody');
function addRow(item){ body.appendChild(rowTemplate(item)); recalc(); }

document.getElementById('addItem').addEventListener('click', function(){ addRow(); });
body.addEventListener('input', recalc);
body.addEventListener('change', function(e){
    if(e.target.classList.contains('i-service')){
        var opt = e.target.selectedOptions[0];
        var price = opt ? opt.getAttribute('data-price') : null;
        var tr = e.target.closest('tr');
        if(opt && opt.value){ tr.querySelector('.i-desc').value = opt.textContent; if(price) tr.querySelector('.i-price').value = price; }
        recalc();
    }
});
body.addEventListener('click', function(e){
    if(e.target.classList.contains('i-remove')){ e.target.closest('tr').remove(); recalc(); }
});

if(INITIAL.length){ INITIAL.forEach(addRow); } else { addRow(); }
</script>
<?php $this->stop(); ?>
