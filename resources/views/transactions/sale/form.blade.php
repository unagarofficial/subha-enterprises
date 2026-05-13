@extends('layouts.app')
@section('title', $hdr ? 'Edit Sale #'.$hdr->inv_no.' ('.$saleLabel.')' : 'New '.$saleLabel)

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
    .party-info-box { background: #e8f4fd; border: 1px solid #b8d8f0; border-radius: 5px; }
    .select2-container--open { z-index: 9999; }
</style>
@endpush

@section('content')

@php
    $isEdit     = !is_null($hdr);
    $formAction = $isEdit
        ? route('transactions.sale.update', [$saleType, $hdr->br_code, $hdr->inv_no])
        : route('transactions.sale.store', ['saleType' => $saleType]);
    $method     = $isEdit ? 'PUT' : 'POST';
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">
        <i class="bi bi-receipt me-1 text-primary"></i>
        {{ $isEdit ? 'Edit Invoice #'.$hdr->inv_no.' — '.$saleLabel : 'New '.$saleLabel }}
        @if($isEdit && $hdr->is_locked)
            <span class="badge bg-danger ms-2"><i class="bi bi-lock-fill me-1"></i>Locked</span>
        @endif
    </h5>
    <a href="{{ route('transactions.sale.index', ['saleType' => $saleType]) }}" class="btn btn-secondary btn-sm">
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

<form method="POST" action="{{ $formAction }}" id="saleForm">
    @csrf
    @if($isEdit) @method('PUT') @endif

    {{-- ── HEADER SECTION ──────────────────────────────────────────── --}}
    <div class="card mb-3">
        <div class="card-header py-2 text-white" style="background:#1a3c5e;">
            <i class="bi bi-receipt me-1"></i>
            {{ $saleLabel }} — Invoice Header
            <span class="badge {{ $saleType == 1 ? 'bg-success' : 'bg-primary' }} ms-2">Type {{ $saleType }}</span>
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
                    <label class="form-label">Inv No</label>
                    <input type="text" class="form-control" value="{{ $nextInvNo }}" readonly
                           style="background:#e9ecef; font-weight:700; color:#1a3c5e;">
                </div>
                {{-- Date --}}
                <div class="col-md-2">
                    <label class="form-label">Invoice Date <span class="text-danger">*</span></label>
                    <input type="date" name="inv_date" class="form-control" required
                           value="{{ old('inv_date', $hdr->inv_date ?? session('login_date')) }}">
                </div>
                {{-- Bill Type --}}
                <div class="col-md-2">
                    <label class="form-label">Bill Type <span class="text-danger">*</span></label>
                    <select name="bill_type" id="bill_type" class="form-select" required>
                        <option value="">-- Select --</option>
                        @foreach(['CASH','CREDIT','COVERING','PLATING'] as $bt)
                        <option value="{{ $bt }}"
                            {{ old('bill_type', $hdr->bill_type ?? '') === $bt ? 'selected' : '' }}>
                            {{ $bt }}
                        </option>
                        @endforeach
                    </select>
                </div>
                {{-- Order No --}}
                <div class="col-md-2">
                    <label class="form-label">Order No</label>
                    <div class="input-group">
                        <input type="number" name="ord_no" id="ord_no" class="form-control" min="1"
                               value="{{ old('ord_no', $hdr->ord_no ?? '') }}" placeholder="Optional">
                        <button type="button" id="loadOrderBtn" class="btn btn-outline-secondary btn-sm" title="Load Order Items">
                            <i class="bi bi-download"></i>
                        </button>
                    </div>
                </div>
                {{-- Customer --}}
                <div class="col-md-9">
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

                {{-- Party Info Panel --}}
                <div class="col-12" id="partyInfoWrap" style="{{ ($isEdit || old('party_code')) ? '' : 'display:none;' }}">
                    <div class="party-info-box px-3 py-2">
                        <div class="row g-2 align-items-center">
                            <div class="col-auto">
                                <small class="text-muted">Place:</small>
                                <strong id="pi_place">{{ $hdr->party_place ?? '' }}</strong>
                            </div>
                            <div class="col-auto">
                                <small class="text-muted">State:</small>
                                <strong id="pi_state">{{ $hdr->party_state ?? '' }}</strong>
                            </div>
                            <div class="col-auto">
                                <small class="text-muted">GST No:</small>
                                <strong id="pi_gst">{{ $hdr->tin_grn_no ?? '—' }}</strong>
                            </div>
                            <div class="col-auto">
                                <span id="pi_type" class="badge {{ isset($hdr) && $hdr->inout_state ? 'bg-warning text-dark' : 'bg-success' }}">
                                    {{ isset($hdr) ? ($hdr->inout_state ? 'Out-State (IGST)' : 'In-State (CGST+SGST)') : '' }}
                                </span>
                            </div>
                        </div>
                        <input type="hidden" id="inout_state" value="{{ $hdr->inout_state ?? 0 }}">
                    </div>
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
                            <label class="form-label text-muted" style="font-size:0.75rem;">Edit Tax% to recalculate</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i> {{ $isEdit ? 'Update Invoice' : 'Save Invoice' }}
                </button>
                <a href="{{ route('transactions.sale.index', ['saleType' => $saleType]) }}" class="btn btn-secondary">Cancel</a>
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
                {{-- GST Breakdown --}}
                <div id="gstBreakdown" class="row mb-1" style="font-size:0.78rem; color:#555;">
                    <div class="col" id="cgstSgstLine" style="display:none;">
                        CGST: <span id="cgstAmt">0.00</span> + SGST: <span id="sgstAmt">0.00</span>
                    </div>
                    <div class="col" id="igstLine" style="display:none;">
                        IGST: <span id="igstAmt">0.00</span>
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
// ── Product data ─────────────────────────────────────────────────────────────
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
        theme        : 'bootstrap-5',
        placeholder  : 'Select Material...',
        dropdownParent: $('body'),
        width        : '100%',
    });

    if (data && data.mat_code) {
        $(`#prodSel-${idx}`).val(data.mat_code).trigger('change.select2');
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
    const row     = $(this).closest('tr');
    const matCode = $(this).val();
    if (!matCode) return;
    const p = productsMap[matCode];
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
    if ($('#detailTbody .detail-row').length <= 1) {
        alert('At least one item row is required.');
        return;
    }
    $(this).closest('tr').remove();
    updateSlNos();
    calcTotals();
});

$('#addRowBtn').on('click', function () { addRow(); });

// ── Calculations ─────────────────────────────────────────────────────────────
function calcRowValue(row) {
    const qty  = parseFloat(row.find('.qty-inp').val()) || 0;
    const rate = parseFloat(row.find('.rate-inp').val()) || 0;
    row.find('.sval-inp').val((qty * rate).toFixed(2));
    calcTotals();
}

function calcTotals() {
    let gross = 0;
    $('#detailTbody .sval-inp').each(function () { gross += parseFloat($(this).val()) || 0; });
    const taxRate   = parseFloat($('#tax_rate').val()) || 0;
    const taxAmt    = gross * taxRate / 100;
    const nett      = gross + taxAmt;
    const halfTax   = taxAmt / 2;
    const inoutState = parseInt($('#inout_state').val()) || 0;

    $('#gross').val(gross.toFixed(2));
    $('#tax_amount').val(taxAmt.toFixed(2));
    $('#nett').val(nett.toFixed(2));

    // GST breakdown
    if (taxAmt > 0) {
        if (inoutState === 0) {
            $('#cgstAmt').text(halfTax.toFixed(2));
            $('#sgstAmt').text(halfTax.toFixed(2));
            $('#cgstSgstLine').show();
            $('#igstLine').hide();
        } else {
            $('#igstAmt').text(taxAmt.toFixed(2));
            $('#igstLine').show();
            $('#cgstSgstLine').hide();
        }
    } else {
        $('#cgstSgstLine').hide();
        $('#igstLine').hide();
    }
}

// ── Tax dropdown → tax_rate ──────────────────────────────────────────────────
$('#taxSelect').on('change', function () {
    $('#tax_rate').val($(this).val() || 0);
    calcTotals();
});
$('#tax_rate').on('input', calcTotals);

// ── Customer select: fetch party details via AJAX ────────────────────────────
$('#party_code').select2({
    theme       : 'bootstrap-5',
    placeholder : 'Search customer...',
    width       : '100%',
});

$('#party_code').on('change', function () {
    const partyCode = $(this).val();
    if (!partyCode) {
        $('#partyInfoWrap').hide();
        $('#inout_state').val(0);
        calcTotals();
        return;
    }
    $.getJSON('{{ route("transactions.sale.getParty", "") }}/' + partyCode, function (data) {
        $('#pi_place').text(data.place || '—');
        $('#pi_state').text(data.state || '—');
        $('#pi_gst').text(data.tin_grn_no || '—');
        $('#inout_state').val(data.inout_state);
        if (data.inout_state) {
            $('#pi_type').removeClass('bg-success').addClass('bg-warning text-dark').text('Out-State (IGST)');
        } else {
            $('#pi_type').removeClass('bg-warning text-dark').addClass('bg-success').text('In-State (CGST+SGST)');
        }
        $('#partyInfoWrap').show();
        calcTotals();
    });
});

// ── Load Order items ─────────────────────────────────────────────────────────
$('#loadOrderBtn').on('click', function () {
    const ordNo  = $('#ord_no').val();
    const brCode = $('#br_code').val();
    if (!ordNo) { alert('Enter an order number first.'); return; }

    $.getJSON('{{ route("transactions.sale.getOrder", "") }}/' + ordNo + '?br_code=' + brCode, function (data) {
        if (!data.rows || data.rows.length === 0) {
            alert('No items found for Order #' + ordNo);
            return;
        }
        if (!confirm('Load ' + data.rows.length + ' item(s) from Order #' + ordNo + '? Current rows will be cleared.')) return;

        $('#detailTbody').empty();
        rowIdx = 0;
        data.rows.forEach(function (r) {
            addRow({
                mat_code : r.mat_code,
                qty      : r.qty,
                uom      : r.uom,
                uom_name : r.uom_name,
                rate     : r.rate,
                s_value  : (parseFloat(r.qty) * parseFloat(r.rate)).toFixed(2),
                narration: r.narration || '',
            });
        });
        calcTotals();
    }).fail(function () {
        alert('Order not found or error loading.');
    });
});

// ── Init: load existing rows ─────────────────────────────────────────────────
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
                addRow({
                    mat_code  : item.mat_code,
                    qty       : item.qty,
                    uom       : item.uom || pData.uom_code || '',
                    uom_name  : pData.uom_name || '',
                    rate      : item.rate,
                    s_value   : item.s_value,
                    narration : item.narration || '',
                });
            });
        } else {
            addRow();
        }
    @elseif(count($dbRows) > 0)
        const dbRows = @json($dbRows);
        dbRows.forEach(function (item) { addRow(item); });
    @else
        addRow();
    @endif

    calcTotals();

    // If edit mode: trigger party info to show
    @if($isEdit)
    $('#party_code').trigger('change');
    @endif
});

// ── Validate before submit ───────────────────────────────────────────────────
$('#saleForm').on('submit', function () {
    if ($('#detailTbody .detail-row').length === 0) {
        alert('Please add at least one item row.');
        return false;
    }
    return true;
});
</script>
@endpush
