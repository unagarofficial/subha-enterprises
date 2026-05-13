@extends('layouts.app')
@section('title', $hdr ? 'Edit Purchase #'.$hdr->inv_no : 'New Purchase Entry')

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
    .total-card .net-row { background: #1a3c5e; color: #fff; border-radius: 4px; padding: 4px 8px; }
    .total-card .net-row .val { font-size: 1.0rem; }
    /* Fix Select2 dropdown clipping in table */
    .select2-container--open { z-index: 9999; }
</style>
@endpush

@section('content')

@php
    $isEdit     = !is_null($hdr);
    $formAction = $isEdit
        ? route('transactions.purchase.update', [$hdr->br_code, $hdr->inv_no])
        : route('transactions.purchase.store');
    $method     = $isEdit ? 'PUT' : 'POST';
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">
        <i class="bi bi-cart-plus me-1 text-primary"></i>
        {{ $isEdit ? 'Edit Purchase Invoice #'.$hdr->inv_no : 'New Purchase Entry' }}
    </h5>
    <a href="{{ route('transactions.purchase.index') }}" class="btn btn-secondary btn-sm">
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

<form method="POST" action="{{ $formAction }}" id="purchaseForm">
    @csrf
    @if($isEdit) @method('PUT') @endif

    {{-- ── HEADER SECTION ──────────────────────────────────────────── --}}
    <div class="card mb-3">
        <div class="card-header py-2 text-white" style="background:#1a3c5e;">
            <i class="bi bi-receipt me-1"></i> Invoice Header
        </div>
        <div class="card-body py-3">
            <div class="row g-3">
                {{-- Branch --}}
                <div class="col-md-3">
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
                <div class="col-md-2">
                    <label class="form-label">Invoice No</label>
                    <input type="text" class="form-control" value="{{ $nextInvNo }}" readonly
                           style="background:#e9ecef; font-weight:700; color:#1a3c5e;">
                </div>
                {{-- Date --}}
                <div class="col-md-2">
                    <label class="form-label">Invoice Date <span class="text-danger">*</span></label>
                    <input type="date" name="inv_date" class="form-control" required
                           value="{{ old('inv_date', $hdr->inv_date ?? session('login_date')) }}">
                </div>
                {{-- Supplier --}}
                <div class="col-md-5">
                    <label class="form-label">Supplier <span class="text-danger">*</span></label>
                    <select name="party_code" id="party_code" class="form-select" required>
                        <option value="">-- Select Supplier --</option>
                        @foreach($suppliers as $s)
                        <option value="{{ $s->party_code }}"
                            {{ old('party_code', $hdr->party_code ?? '') == $s->party_code ? 'selected' : '' }}>
                            {{ $s->party_name }} — {{ $s->place }}
                        </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- ── DETAILS SECTION ─────────────────────────────────────────── --}}
    <div class="card mb-3">
        <div class="card-header py-2 d-flex justify-content-between align-items-center" style="background:#1a3c5e; color:#fff;">
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
                            <th style="min-width:190px">Material <span class="text-danger">*</span></th>
                            <th style="width:85px">Qty <span class="text-danger">*</span></th>
                            <th style="width:65px">UOM</th>
                            <th style="width:95px">Rate <span class="text-danger">*</span></th>
                            <th style="width:100px">Amount</th>
                            <th style="min-width:110px">Narration</th>
                            <th style="width:110px">Category</th>
                            <th style="width:80px">PO No</th>
                            <th style="width:36px"></th>
                        </tr>
                    </thead>
                    <tbody id="detailTbody"></tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ── TOTALS + SAVE ────────────────────────────────────────────── --}}
    <div class="row align-items-start">
        {{-- Tax controls --}}
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
                        <div class="col-md-4">
                            <label class="form-label">Note</label>
                            <span class="form-text">Change Tax% to recalculate</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i> {{ $isEdit ? 'Update Invoice' : 'Save Invoice' }}
                </button>
                <a href="{{ route('transactions.purchase.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </div>

        {{-- Totals box --}}
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
                    <span class="fw-600">Net Amount</span>
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
// ── Data from PHP ────────────────────────────────────────────────────────────
const productsMap = {};
@foreach($products as $p)
productsMap[{{ json_encode($p->mat_code) }}] = {
    mat_name : {{ json_encode($p->mat_name) }},
    uom_code : {{ $p->uom }},
    uom_name : {{ json_encode($p->uomUnit->uom_name ?? '') }},
    b_rate   : {{ (float)$p->b_rate }},
    cat_code : {{ $p->cat_code }},
};
@endforeach

const productOptions = (function () {
    let html = '<option value=""></option>';
    @foreach($products as $p)
    html += `<option value="{{ $p->mat_code }}" data-uom="{{ $p->uom }}" data-uomname="{{ addslashes($p->uomUnit->uom_name ?? '') }}" data-rate="{{ $p->b_rate }}" data-cat="{{ $p->cat_code }}">{{ addslashes($p->mat_code.' - '.$p->mat_name) }}</option>`;
    @endforeach
    return html;
})();

const categoryOptions = (function () {
    let html = '';
    @foreach($categories as $c)
    html += `<option value="{{ $c->cat_code }}">{{ addslashes($c->cat_name) }}</option>`;
    @endforeach
    return html;
})();

// ── Row management ───────────────────────────────────────────────────────────
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
            <input type="number" name="items[${idx}][amount]" class="form-control form-control-sm amt-inp"
                   value="${d.amount||''}" readonly style="width:95px; background:#e9ecef;">
        </td>
        <td>
            <input type="text" name="items[${idx}][narration]" class="form-control form-control-sm"
                   value="${d.narration||''}">
        </td>
        <td>
            <select name="items[${idx}][cat_code]" class="form-select form-select-sm cat-sel">
                ${categoryOptions}
            </select>
        </td>
        <td>
            <input type="number" name="items[${idx}][po_no]" class="form-control form-control-sm"
                   value="${d.po_no||''}" min="0" style="width:74px;">
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
    const html = buildRow(idx, data || {});
    $('#detailTbody').append(html);

    // Init Select2 on this row's product select
    $(`#prodSel-${idx}`).select2({
        theme        : 'bootstrap-5',
        placeholder  : 'Select Material...',
        dropdownParent: $('body'),
        width        : '100%',
    });

    // Pre-select if data given
    if (data && data.mat_code) {
        $(`#prodSel-${idx}`).val(data.mat_code).trigger('change.select2');
        // Set category
        const row = $(`#drow-${idx}`);
        row.find('.cat-sel').val(data.cat_code);
        // Set uom (already set in HTML)
    }

    updateSlNos();
}

function updateSlNos() {
    $('#detailTbody .detail-row').each(function (i) {
        $(this).find('.sl-no').text(i + 1);
    });
}

// ── Event delegation ─────────────────────────────────────────────────────────
$('#detailTbody').on('change', '.prod-sel', function () {
    const row    = $(this).closest('tr');
    const matCode = $(this).val();
    if (!matCode) return;

    const p = productsMap[matCode];
    if (!p) return;

    row.find('.uom-name').val(p.uom_name);
    row.find('.uom-val').val(p.uom_code);
    row.find('.rate-inp').val(p.b_rate);
    row.find('.cat-sel').val(p.cat_code);
    calcRowAmount(row);
});

$('#detailTbody').on('input', '.qty-inp, .rate-inp', function () {
    calcRowAmount($(this).closest('tr'));
});

$('#detailTbody').on('click', '.del-row', function () {
    if ($('#detailTbody .detail-row').length <= 1) {
        alert('At least one item row is required.');
        return;
    }
    $(this).closest('tr').remove();
    updateSlNos();
    calcTotals();
});

$('#addRowBtn').on('click', function () { addRow(); });

// ── Amount calculations ──────────────────────────────────────────────────────
function calcRowAmount(row) {
    const qty  = parseFloat(row.find('.qty-inp').val()) || 0;
    const rate = parseFloat(row.find('.rate-inp').val()) || 0;
    row.find('.amt-inp').val((qty * rate).toFixed(2));
    calcTotals();
}

function calcTotals() {
    let gross = 0;
    $('#detailTbody .amt-inp').each(function () {
        gross += parseFloat($(this).val()) || 0;
    });
    const taxRate = parseFloat($('#tax_rate').val()) || 0;
    const taxAmt  = gross * taxRate / 100;
    const nett    = gross + taxAmt;

    $('#gross').val(gross.toFixed(2));
    $('#tax_amount').val(taxAmt.toFixed(2));
    $('#nett').val(nett.toFixed(2));
}

// Tax dropdown changes tax_rate field
$('#taxSelect').on('change', function () {
    $('#tax_rate').val($(this).val() || 0);
    calcTotals();
});

$('#tax_rate').on('input', function () {
    calcTotals();
});

// ── Init Select2 on supplier ─────────────────────────────────────────────────
$('#party_code').select2({
    theme       : 'bootstrap-5',
    placeholder : 'Search supplier...',
    width       : '100%',
});

// ── Load existing rows (edit mode or validation failure) ─────────────────────
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
                    'amount'    => $d->amount,
                    'narration' => $d->narration,
                    'cat_code'  => $d->cat_code,
                    'po_no'     => $d->po_no,
                ];
            }
        }
    @endphp

    @if($oldItems)
        // Re-populate from validation failure
        const oldItems = @json($oldItems);
        if (oldItems && oldItems.length > 0) {
            oldItems.forEach(function (item) {
                if (!item) return;
                const pData = productsMap[item.mat_code] || {};
                addRow({
                    mat_code  : item.mat_code,
                    qty       : item.qty,
                    uom       : item.uom || pData.uom_code || '',
                    uom_name  : pData.uom_name || '',
                    rate      : item.rate,
                    amount    : item.amount,
                    narration : item.narration || '',
                    cat_code  : item.cat_code,
                    po_no     : item.po_no || '',
                });
            });
        } else {
            addRow();
        }
    @elseif(count($dbRows) > 0)
        // Edit mode — load from DB
        const dbRows = @json($dbRows);
        dbRows.forEach(function (item) { addRow(item); });
    @else
        // New entry — one blank row
        addRow();
    @endif

    // Recalculate totals on load
    calcTotals();
});

// ── Validate before submit ───────────────────────────────────────────────────
$('#purchaseForm').on('submit', function () {
    if ($('#detailTbody .detail-row').length === 0) {
        alert('Please add at least one item row.');
        return false;
    }
    return true;
});
</script>
@endpush
