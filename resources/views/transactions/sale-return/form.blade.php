@extends('layouts.app')
@section('title', $hdr ? 'Edit Sale Return #'.$hdr->inv_no : 'New Sale Return')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
<style>
    .detail-table { font-size: 0.80rem; }
    .detail-table th { background: #1a3c5e; color: #fff; padding: 6px 6px; white-space: nowrap; }
    .detail-table td { padding: 3px 4px; vertical-align: middle; }
    .detail-table input, .detail-table select { font-size: 0.79rem; }
    .total-card { background: #f0f4f8; border: 1px solid #c8d8e8; border-radius: 6px; }
    .total-card .lbl { color: #555; font-size: 0.80rem; }
    .total-card .val { font-weight: 600; font-size: 0.90rem; text-align: right; }
    .total-card .net-row { background: #c0392b; color: #fff; border-radius: 4px; padding: 4px 8px; }
    .total-card .net-row .val { font-size: 1.0rem; }
    .ref-invoice-box { background: #fff8e1; border: 1px solid #ffe082; border-radius: 5px; }
    .select2-container--open { z-index: 9999; }
</style>
@endpush

@section('content')

@php
    $isEdit     = !is_null($hdr);
    $formAction = $isEdit
        ? route('transactions.sale-return.update', [$hdr->br_code, $hdr->inv_no])
        : route('transactions.sale-return.store');
    $method     = $isEdit ? 'PUT' : 'POST';
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">
        <i class="bi bi-arrow-return-left me-1 text-danger"></i>
        {{ $isEdit ? 'Edit Sale Return #'.$hdr->inv_no : 'New Sale Return' }}
    </h5>
    <a href="{{ route('transactions.sale-return.index') }}" class="btn btn-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to List
    </a>
</div>

@if($errors->any())
<div class="alert alert-danger py-2 mb-3">
    <strong><i class="bi bi-exclamation-triangle me-1"></i>Validation Errors:</strong>
    <ul class="mb-0 mt-1 ps-3">
        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
    </ul>
</div>
@endif

<form method="POST" action="{{ $formAction }}" id="rtnForm">
    @csrf
    @if($isEdit) @method('PUT') @endif

    {{-- ── HEADER ──────────────────────────────────────────────────────── --}}
    <div class="card mb-3">
        <div class="card-header py-2 text-white" style="background:#c0392b;">
            <i class="bi bi-arrow-return-left me-1"></i> Sale Return — Invoice Header
        </div>
        <div class="card-body py-3">
            <div class="row g-3">

                {{-- Branch --}}
                <div class="col-md-2">
                    <label class="form-label">Branch <span class="text-danger">*</span></label>
                    <select name="br_code" id="br_code" class="form-select" required>
                        @foreach($branches as $b)
                        <option value="{{ $b->br_code }}"
                            {{ old('br_code', $hdr->br_code ?? session('br_code')) == $b->br_code ? 'selected' : '' }}>
                            {{ $b->br_name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                {{-- Inv No --}}
                <div class="col-md-1">
                    <label class="form-label">Rtn No</label>
                    <input type="text" class="form-control" value="{{ $nextInvNo }}" readonly
                           style="background:#e9ecef; font-weight:700; color:#c0392b;">
                </div>

                {{-- Date --}}
                <div class="col-md-2">
                    <label class="form-label">Return Date <span class="text-danger">*</span></label>
                    <input type="date" name="inv_date" class="form-control" required
                           value="{{ old('inv_date', $hdr->inv_date ?? session('login_date')) }}">
                </div>

                {{-- Bill Type --}}
                <div class="col-md-2">
                    <label class="form-label">Bill Type <span class="text-danger">*</span></label>
                    <select name="bill_type" id="bill_type" class="form-select" required>
                        <option value="">-- Select --</option>
                        @foreach(['COVERING','PLATING'] as $bt)
                        <option value="{{ $bt }}"
                            {{ old('bill_type', $hdr->bill_type ?? '') === $bt ? 'selected' : '' }}>
                            {{ $bt }}
                        </option>
                        @endforeach
                    </select>
                </div>

                {{-- Customer --}}
                <div class="col-md-5">
                    <label class="form-label">Customer <span class="text-danger">*</span></label>
                    <select name="party_code" id="party_code" class="form-select" required>
                        <option value="">-- Select Customer --</option>
                        @foreach($customers as $c)
                        <option value="{{ $c->party_code }}"
                            {{ old('party_code', $hdr->party_code ?? '') == $c->party_code ? 'selected' : '' }}>
                            {{ $c->party_name }}{{ $c->place ? ' — '.$c->place : '' }}
                        </option>
                        @endforeach
                    </select>
                </div>

                {{-- Reference Sale Invoice (Optional) --}}
                <div class="col-12">
                    <div class="ref-invoice-box px-3 py-2">
                        <div class="row g-2 align-items-end">
                            <div class="col-auto">
                                <small class="fw-bold text-warning-emphasis">
                                    <i class="bi bi-link-45deg me-1"></i>Reference Sale Invoice (Optional)
                                </small>
                            </div>
                            <div class="col-md-3">
                                <select id="refInvSelect" class="form-select form-select-sm">
                                    <option value="">-- Select to auto-fill items --</option>
                                </select>
                            </div>
                            <div class="col-auto">
                                <button type="button" id="loadRefBtn" class="btn btn-warning btn-sm">
                                    <i class="bi bi-download me-1"></i>Load Items
                                </button>
                            </div>
                            <div class="col-auto">
                                <small class="text-muted">Select customer first, then choose reference invoice</small>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- ── DETAIL ROWS ──────────────────────────────────────────────────── --}}
    <div class="card mb-3">
        <div class="card-header py-2 d-flex justify-content-between align-items-center" style="background:#c0392b; color:#fff;">
            <span><i class="bi bi-table me-1"></i> Item Details</span>
            <button type="button" id="addRowBtn" class="btn btn-sm btn-warning">
                <i class="bi bi-plus-circle me-1"></i> Add Row
            </button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm detail-table mb-0" id="detailTable">
                    <thead>
                        <tr>
                            <th style="width:32px">Sl</th>
                            <th style="min-width:200px">Material <span class="text-danger">*</span></th>
                            <th style="width:85px">Qty <span class="text-danger">*</span></th>
                            <th style="width:65px">UOM</th>
                            <th style="width:95px">Rate <span class="text-danger">*</span></th>
                            <th style="width:105px">Sale Value</th>
                            <th style="min-width:120px">Narration</th>
                            <th style="width:36px"></th>
                        </tr>
                    </thead>
                    <tbody id="detailTbody"></tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ── TOTALS + SAVE ────────────────────────────────────────────────── --}}
    <div class="row align-items-start">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body py-2">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-5">
                            <label class="form-label">Tax</label>
                            <select id="taxSelect" class="form-select form-select-sm">
                                <option value="">-- None --</option>
                                @foreach($taxes as $t)
                                <option value="{{ $t->tax_percent }}"
                                    {{ old('tax_rate', $hdr->tax_rate ?? 0) == $t->tax_percent ? 'selected' : '' }}>
                                    {{ $t->tax_name }} ({{ $t->tax_percent }}%)
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tax %</label>
                            <input type="number" name="tax_rate" id="tax_rate" class="form-control form-control-sm"
                                   step="0.01" min="0" value="{{ old('tax_rate', $hdr->tax_rate ?? 0) }}"
                                   placeholder="0.00">
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-3 d-flex gap-2">
                <button type="submit" class="btn btn-danger">
                    <i class="bi bi-save me-1"></i> {{ $isEdit ? 'Update Return' : 'Save Return' }}
                </button>
                <a href="{{ route('transactions.sale-return.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </div>

        <div class="col-md-4 offset-md-2">
            <div class="total-card p-3">
                <div class="row mb-1">
                    <div class="col lbl">Gross Amount</div>
                    <div class="col val">
                        <input type="number" name="gross" id="gross" class="form-control form-control-sm text-end"
                               readonly step="0.01" value="{{ old('gross', $hdr->gross ?? 0) }}"
                               style="background:#fff;">
                    </div>
                </div>
                <div class="row mb-1">
                    <div class="col lbl">Tax Amount</div>
                    <div class="col val">
                        <input type="number" name="tax_amount" id="tax_amount" class="form-control form-control-sm text-end"
                               readonly step="0.01" value="{{ old('tax_amount', $hdr->tax_amount ?? 0) }}"
                               style="background:#fff;">
                    </div>
                </div>
                <div class="net-row d-flex justify-content-between align-items-center mt-2">
                    <span class="fw-bold">Net Amount</span>
                    <input type="number" name="nett" id="nett" class="form-control form-control-sm text-end fw-bold ms-2"
                           readonly step="0.01" value="{{ old('nett', $hdr->nett ?? 0) }}"
                           style="background:transparent; border:none; color:#fff; font-size:1rem; width:140px;">
                </div>
            </div>
        </div>
    </div>

</form>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
const productsMap = {};
@foreach($products as $p)
productsMap[{{ json_encode($p->mat_code) }}] = {
    mat_name : {{ json_encode($p->mat_name) }},
    uom_code : {{ $p->uom }},
    uom_name : {{ json_encode($p->uomUnit->uom_name ?? '') }},
    sale_rate : {{ (float)$p->sale_rate }},
};
@endforeach

const productOptions = (function () {
    let html = '<option value=""></option>';
    @foreach($products as $p)
    html += `<option value="{{ $p->mat_code }}" data-uom="{{ $p->uom }}" data-uomname="{{ addslashes($p->uomUnit->uom_name ?? '') }}" data-rate="{{ $p->sale_rate }}">{{ addslashes($p->mat_code.' - '.$p->mat_name) }}</option>`;
    @endforeach
    return html;
})();

let rowIdx = 0;

function buildRow(idx, data) {
    const d = data || {};
    return `
    <tr id="drow-${idx}" class="detail-row">
        <td class="text-center sl-no fw-bold" style="font-size:0.78rem;"></td>
        <td>
            <select id="prodSel-${idx}" name="items[${idx}][mat_code]"
                    class="form-select form-select-sm prod-sel" required style="width:100%;">
                ${productOptions}
            </select>
        </td>
        <td>
            <input type="number" name="items[${idx}][qty]" class="form-control form-control-sm qty-inp"
                   value="${d.qty||''}" step="0.001" min="0.001" required style="width:80px;">
        </td>
        <td>
            <input type="text" class="form-control form-control-sm uom-name" value="${d.uom_name||''}" readonly style="width:60px;">
            <input type="hidden" name="items[${idx}][uom]" class="uom-val" value="${d.uom||''}">
        </td>
        <td>
            <input type="number" name="items[${idx}][rate]" class="form-control form-control-sm rate-inp"
                   value="${d.rate||''}" step="0.01" min="0" required style="width:90px;">
        </td>
        <td>
            <input type="number" name="items[${idx}][s_value]" class="form-control form-control-sm sval-inp"
                   value="${d.s_value||''}" readonly style="width:100px; background:#e9ecef;">
        </td>
        <td>
            <input type="text" name="items[${idx}][narration]" class="form-control form-control-sm"
                   value="${d.narration||''}">
        </td>
        <td>
            <button type="button" class="btn btn-danger btn-xs del-row" title="Remove">
                <i class="bi bi-x-lg"></i>
            </button>
        </td>
    </tr>`;
}

function addRow(data) {
    const idx = rowIdx++;
    $('#detailTbody').append(buildRow(idx, data || {}));
    $(`#prodSel-${idx}`).select2({
        theme: 'bootstrap-5', placeholder: 'Select Material...', dropdownParent: $('body'), width: '100%',
    });
    if (data && data.mat_code) {
        $(`#prodSel-${idx}`).val(data.mat_code).trigger('change.select2');
    }
    updateSlNos();
}

function updateSlNos() {
    $('#detailTbody .detail-row').each(function (i) { $(this).find('.sl-no').text(i + 1); });
}

$('#detailTbody').on('change', '.prod-sel', function () {
    const row = $(this).closest('tr');
    const p = productsMap[$(this).val()];
    if (!p) return;
    row.find('.uom-name').val(p.uom_name);
    row.find('.uom-val').val(p.uom_code);
    row.find('.rate-inp').val(p.sale_rate);
    calcRowValue(row);
});

$('#detailTbody').on('input', '.qty-inp, .rate-inp', function () {
    calcRowValue($(this).closest('tr'));
});

$('#detailTbody').on('click', '.del-row', function () {
    if ($('#detailTbody .detail-row').length <= 1) { alert('At least one item row is required.'); return; }
    $(this).closest('tr').remove();
    updateSlNos();
    calcTotals();
});

$('#addRowBtn').on('click', function () { addRow(); });

function calcRowValue(row) {
    const qty  = parseFloat(row.find('.qty-inp').val()) || 0;
    const rate = parseFloat(row.find('.rate-inp').val()) || 0;
    row.find('.sval-inp').val((qty * rate).toFixed(2));
    calcTotals();
}

function calcTotals() {
    let gross = 0;
    $('#detailTbody .sval-inp').each(function () { gross += parseFloat($(this).val()) || 0; });
    const taxRate = parseFloat($('#tax_rate').val()) || 0;
    const taxAmt  = gross * taxRate / 100;
    $('#gross').val(gross.toFixed(2));
    $('#tax_amount').val(taxAmt.toFixed(2));
    $('#nett').val((gross + taxAmt).toFixed(2));
}

$('#taxSelect').on('change', function () { $('#tax_rate').val($(this).val() || 0); calcTotals(); });
$('#tax_rate').on('input', calcTotals);

// Customer select2
$('#party_code').select2({ theme: 'bootstrap-5', placeholder: 'Search customer...', width: '100%' });

// When customer changes, load their sale invoices for reference dropdown
$('#party_code').on('change', function () {
    const partyCode = $(this).val();
    const brCode    = $('#br_code').val();
    $('#refInvSelect').html('<option value="">-- Select to auto-fill items --</option>');
    if (!partyCode) return;

    $.getJSON('{{ route("transactions.sale-return.customerSales", "") }}/' + partyCode + '?br_code=' + brCode,
        function (data) {
            data.forEach(function (inv) {
                const date = new Date(inv.inv_date).toLocaleDateString('en-IN', {day:'2-digit', month:'short', year:'numeric'});
                $('#refInvSelect').append(
                    `<option value="${inv.inv_no}" data-sale-type="${inv.sale_type}">
                        #${inv.inv_no} — ${date} — ₹${parseFloat(inv.nett).toFixed(2)} (${inv.bill_type || ''})
                    </option>`
                );
            });
        }
    );
});

// Load reference invoice items
$('#loadRefBtn').on('click', function () {
    const invNo    = $('#refInvSelect').val();
    const saleType = $('#refInvSelect option:selected').data('sale-type');
    const brCode   = $('#br_code').val();
    if (!invNo) { alert('Select a reference sale invoice first.'); return; }

    $.getJSON('{{ route("transactions.sale-return.saleItems", ["__BR__", "__INV__", "__ST__"]) }}'
        .replace('__BR__', brCode).replace('__INV__', invNo).replace('__ST__', saleType || 1),
        function (data) {
            if (!data.rows || data.rows.length === 0) { alert('No items found in this invoice.'); return; }
            if (!confirm('Load ' + data.rows.length + ' item(s) from Invoice #' + invNo + '? Current rows will be cleared.')) return;
            $('#detailTbody').empty(); rowIdx = 0;
            data.rows.forEach(function (r) {
                addRow({
                    mat_code : r.mat_code,
                    qty      : r.qty,
                    uom      : r.uom,
                    uom_name : r.uom_name,
                    rate     : r.rate,
                    s_value  : r.s_value,
                    narration: r.narration || '',
                });
            });
            calcTotals();
        }
    ).fail(function () { alert('Error loading invoice items.'); });
});

$(function () {
    @php
        $oldItems = old('items');
        $dbRows   = [];
        if ($dtls && $dtls->count() > 0) {
            foreach ($dtls as $d) {
                $dbRows[] = [
                    'mat_code'  => $d->mat_code,
                    'qty'       => $d->qty,
                    'uom'       => $d->uom,
                    'uom_name'  => $d->uom_name,
                    'rate'      => $d->rate,
                    's_value'   => $d->s_value,
                    'narration' => $d->narration,
                ];
            }
        }
    @endphp

    @if($oldItems)
        const oldItems = @json($oldItems);
        if (oldItems && oldItems.length > 0) {
            oldItems.forEach(function (item) {
                if (!item) return;
                const pData = productsMap[item.mat_code] || {};
                addRow({ mat_code: item.mat_code, qty: item.qty, uom: item.uom || pData.uom_code || '',
                         uom_name: pData.uom_name || '', rate: item.rate, s_value: item.s_value, narration: item.narration || '' });
            });
        } else { addRow(); }
    @elseif(count($dbRows) > 0)
        const dbRows = @json($dbRows);
        dbRows.forEach(function (item) { addRow(item); });
    @else
        addRow();
    @endif

    calcTotals();

    @if($isEdit)
    // Trigger customer invoice load if editing (populate ref dropdown silently)
    const pc = $('#party_code').val();
    if (pc) $('#party_code').trigger('change');
    @endif
});

$('#rtnForm').on('submit', function () {
    if ($('#detailTbody .detail-row').length === 0) { alert('Please add at least one item row.'); return false; }
    return true;
});
</script>
@endpush
