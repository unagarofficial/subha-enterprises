@extends('layouts.app')
@section('title', $hdr ? 'Edit Order #'.$hdr->ord_no.' ('.$ordLabel.')' : 'New '.$ordLabel)

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
<style>
    .detail-table { font-size: 0.78rem; }
    .detail-table th { background: #1a3c5e; color: #fff; padding: 5px 5px; white-space: nowrap; }
    .detail-table td { padding: 2px 3px; vertical-align: middle; }
    .detail-table input, .detail-table select { font-size: 0.77rem; }
    .select2-container--open { z-index: 9999; }
    .req-qty-inp { background: #fff3cd !important; font-weight: 600; }
</style>
@endpush

@section('content')

@php
    $isEdit     = !is_null($hdr);
    $formAction = $isEdit
        ? route('transactions.order.update', [$ordType, $hdr->br_code, $hdr->ord_no])
        : route('transactions.order.store', ['ordType' => $ordType]);
    $method     = $isEdit ? 'PUT' : 'POST';
    $isLocked   = $isEdit && $hdr->is_locked;
    $isConverted= $isEdit && $hdr->inv_no;
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">
        <i class="bi bi-clipboard-check me-1 text-primary"></i>
        {{ $isEdit ? 'Edit Order #'.$hdr->ord_no.' — '.$ordLabel : 'New '.$ordLabel }}
        @if($isLocked)
            <span class="badge bg-warning text-dark ms-2"><i class="bi bi-lock-fill me-1"></i>Locked</span>
        @endif
        @if($isConverted)
            <span class="badge bg-primary ms-2"><i class="bi bi-arrow-right-circle me-1"></i>Converted → Sale #{{ $hdr->inv_no }}</span>
        @endif
    </h5>
    <div class="d-flex gap-2 align-items-center">
        @if($isEdit && !$isConverted)
        {{-- Lock / Unlock --}}
        <form method="POST"
              action="{{ route('transactions.order.lock', [$ordType, $hdr->br_code, $hdr->ord_no]) }}"
              onsubmit="return confirm('{{ $isLocked ? 'Unlock' : 'Lock' }} this order?')">
            @csrf
            <button type="submit" class="btn btn-sm {{ $isLocked ? 'btn-outline-warning' : 'btn-warning' }}">
                <i class="bi bi-{{ $isLocked ? 'unlock' : 'lock' }}-fill me-1"></i>
                {{ $isLocked ? 'Unlock Order' : 'Lock Order' }}
            </button>
        </form>
        {{-- Convert to Sale --}}
        <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#convertModal">
            <i class="bi bi-arrow-right-circle me-1"></i> Convert to Sale Bill
        </button>
        {{-- Print --}}
        <a href="{{ route('transactions.order.print', [$ordType, $hdr->br_code, $hdr->ord_no]) }}"
           target="_blank" class="btn btn-sm btn-info text-white">
            <i class="bi bi-printer me-1"></i> Print Estimation
        </a>
        @endif
        @if($isEdit && $isConverted)
        <a href="{{ route('transactions.order.print', [$ordType, $hdr->br_code, $hdr->ord_no]) }}"
           target="_blank" class="btn btn-sm btn-info text-white">
            <i class="bi bi-printer me-1"></i> Print Estimation
        </a>
        @endif
        <a href="{{ route('transactions.order.index', ['ordType' => $ordType]) }}" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

@if($errors->any())
<div class="alert alert-danger py-2 mb-3">
    <strong><i class="bi bi-exclamation-triangle me-1"></i>Validation Errors:</strong>
    <ul class="mb-0 mt-1 ps-3">
        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
    </ul>
</div>
@endif

<form method="POST" action="{{ $formAction }}" id="orderForm">
    @csrf
    @if($isEdit) @method('PUT') @endif

    {{-- ── HEADER ─────────────────────────────────────────────────────────── --}}
    <div class="card mb-3">
        <div class="card-header py-2 text-white" style="background:#1a3c5e;">
            <i class="bi bi-clipboard me-1"></i> Order Header
            <span class="badge {{ $ordType == 1 ? 'bg-success' : 'bg-primary' }} ms-2">Type {{ $ordType }}</span>
        </div>
        <div class="card-body py-3">
            <div class="row g-3">
                {{-- Branch --}}
                <div class="col-md-2">
                    <label class="form-label">Branch <span class="text-danger">*</span></label>
                    <select name="br_code" id="br_code" class="form-select" required {{ $isConverted ? 'disabled' : '' }}>
                        @foreach($branches as $b)
                        <option value="{{ $b->br_code }}"
                            {{ old('br_code', $hdr->br_code ?? session('br_code')) == $b->br_code ? 'selected' : '' }}>
                            {{ $b->br_name }}
                        </option>
                        @endforeach
                    </select>
                    @if($isConverted)
                    <input type="hidden" name="br_code" value="{{ $hdr->br_code }}">
                    @endif
                </div>
                {{-- HO Code --}}
                <div class="col-md-1">
                    <label class="form-label">HO Code</label>
                    <input type="text" class="form-control" value="{{ $firm?->ho_code ?? '—' }}" readonly
                           style="background:#e9ecef;">
                </div>
                {{-- Order No --}}
                <div class="col-md-1">
                    <label class="form-label">Order No</label>
                    <input type="text" class="form-control" value="{{ $nextOrdNo }}" readonly
                           style="background:#e9ecef; font-weight:700; color:#1a3c5e;">
                </div>
                {{-- Date --}}
                <div class="col-md-2">
                    <label class="form-label">Order Date <span class="text-danger">*</span></label>
                    <input type="date" name="ord_date" class="form-control" required {{ $isConverted ? 'readonly' : '' }}
                           value="{{ old('ord_date', $hdr->ord_date ?? session('login_date')) }}">
                </div>
                {{-- Lock toggle --}}
                <div class="col-md-2 d-flex align-items-end">
                    <div class="form-check form-switch mb-1">
                        <input class="form-check-input" type="checkbox" name="is_locked" id="isLockedChk" role="switch"
                               {{ old('is_locked', $isLocked ? '1' : '') ? 'checked' : '' }}
                               {{ $isConverted ? 'disabled' : '' }}>
                        <label class="form-check-label" for="isLockedChk">Lock Order</label>
                    </div>
                </div>
                {{-- Customer --}}
                <div class="col-md-8">
                    <label class="form-label">Customer <span class="text-danger">*</span></label>
                    <select name="party_code" id="party_code" class="form-select" required {{ $isConverted ? 'disabled' : '' }}>
                        <option value="">-- Select Customer --</option>
                        @foreach($customers as $c)
                        <option value="{{ $c->party_code }}"
                            {{ old('party_code', $hdr->party_code ?? '') == $c->party_code ? 'selected' : '' }}>
                            {{ $c->party_name }}{{ $c->place ? ' — '.$c->place : '' }}
                        </option>
                        @endforeach
                    </select>
                    @if($isConverted)
                    <input type="hidden" name="party_code" value="{{ $hdr->party_code }}">
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ── DETAILS ──────────────────────────────────────────────────────────── --}}
    <div class="card mb-3">
        <div class="card-header py-2 d-flex justify-content-between align-items-center" style="background:#1a3c5e; color:#fff;">
            <span><i class="bi bi-table me-1"></i> Order Items</span>
            @if(!$isConverted)
            <button type="button" id="addRowBtn" class="btn btn-sm btn-warning">
                <i class="bi bi-plus-circle me-1"></i> Add Row
            </button>
            @endif
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm detail-table mb-0" id="detailTable">
                    <thead>
                        <tr>
                            <th style="width:28px">Sl</th>
                            <th style="min-width:180px">Material <span class="text-danger">*</span></th>
                            <th style="min-width:110px">Narration</th>
                            <th style="width:78px">Ord Qty <span class="text-danger">*</span></th>
                            <th style="width:62px">UOM</th>
                            <th style="width:78px">Bill Qty</th>
                            <th style="width:72px">Extra Qty</th>
                            <th style="width:68px">PO No</th>
                            <th style="width:88px">PO Date</th>
                            <th style="width:68px">P Qty</th>
                            <th style="width:68px">PB No</th>
                            <th style="width:72px">Req Qty</th>
                            @if(!$isConverted)
                            <th style="width:32px"></th>
                            @endif
                        </tr>
                    </thead>
                    <tbody id="detailTbody"></tbody>
                </table>
            </div>
        </div>
    </div>

    @if(!$isConverted)
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-save me-1"></i> {{ $isEdit ? 'Update Order' : 'Save Order' }}
        </button>
        <a href="{{ route('transactions.order.index', ['ordType' => $ordType]) }}" class="btn btn-secondary">Cancel</a>
    </div>
    @endif
</form>

{{-- ── Convert to Sale Modal ───────────────────────────────────────────────── --}}
@if($isEdit && !$isConverted)
<div class="modal fade" id="convertModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form method="POST" action="{{ route('transactions.order.convert', [$ordType, $hdr->br_code, $hdr->ord_no]) }}">
                @csrf
                <div class="modal-header py-2" style="background:#1a3c5e; color:#fff;">
                    <h6 class="modal-title"><i class="bi bi-arrow-right-circle me-1"></i>Convert to Sale Bill</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Sale Type <span class="text-danger">*</span></label>
                        <select name="sale_type" class="form-select" required>
                            <option value="1">Type 1</option>
                            <option value="2">Type 2</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-bold">Bill Type <span class="text-danger">*</span></label>
                        <select name="bill_type" class="form-select" required>
                            <option value="">-- Select --</option>
                            @foreach(['CASH','CREDIT','COVERING','PLATING'] as $bt)
                            <option value="{{ $bt }}">{{ $bt }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="alert alert-warning py-2 mb-0" style="font-size:0.80rem;">
                        <i class="bi bi-info-circle me-1"></i>
                        Bill Qty (if entered) will be used as qty in sale; otherwise Order Qty is used. Rate is pulled from product's sale rate.
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success btn-sm"
                            onclick="return confirm('Convert Order #{{ $hdr->ord_no }} to a Sale Bill? This action cannot be undone.')">
                        <i class="bi bi-arrow-right-circle me-1"></i> Convert
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
// ── Product map ───────────────────────────────────────────────────────────────
const productsMap = {};
@foreach($products as $p)
productsMap[{{ json_encode($p->mat_code) }}] = {
    mat_name : {{ json_encode($p->mat_name) }},
    uom_code : {{ (int)$p->uom }},
    uom_name : {{ json_encode($p->uomUnit->uom_name ?? '') }},
};
@endforeach

const productOptions = (function () {
    let html = '<option value=""></option>';
    @foreach($products as $p)
    html += `<option value="{{ $p->mat_code }}" data-uom="{{ $p->uom }}" data-uomname="{{ addslashes($p->uomUnit->uom_name ?? '') }}">{{ addslashes($p->mat_code.' - '.$p->mat_name) }}</option>`;
    @endforeach
    return html;
})();

const isReadOnly = {{ $isConverted ? 'true' : 'false' }};
let rowIdx = 0;

function buildRow(idx, data) {
    const d    = data || {};
    const ro   = isReadOnly ? 'readonly disabled' : '';
    const roS  = isReadOnly ? 'disabled' : '';

    return `
    <tr id="drow-${idx}" class="detail-row">
        <td class="text-center sl-no fw-bold" style="font-size:0.77rem;"></td>
        <td>
            <select id="prodSel-${idx}" name="items[${idx}][mat_code]"
                    class="form-select form-select-sm prod-sel" ${roS} required style="width:100%;min-width:160px;">
                ${productOptions}
            </select>
        </td>
        <td>
            <input type="text" name="items[${idx}][narration]" class="form-control form-control-sm"
                   value="${d.narration||''}" ${ro} style="width:105px;">
        </td>
        <td>
            <input type="number" name="items[${idx}][ord_qty]" class="form-control form-control-sm ord-qty"
                   value="${d.ord_qty||''}" step="0.001" min="0.001" required ${ro} style="width:72px;">
        </td>
        <td>
            <input type="text" class="form-control form-control-sm uom-name" value="${d.uom_name||''}" readonly style="width:55px;">
            <input type="hidden" name="items[${idx}][uom]" class="uom-val" value="${d.uom||''}">
        </td>
        <td>
            <input type="number" name="items[${idx}][bill_qty]" class="form-control form-control-sm bill-qty"
                   value="${d.bill_qty||0}" step="0.001" min="0" ${ro} style="width:72px;">
        </td>
        <td>
            <input type="number" name="items[${idx}][ex_qty]" class="form-control form-control-sm"
                   value="${d.ex_qty||0}" step="0.001" min="0" ${ro} style="width:66px;">
        </td>
        <td>
            <input type="number" name="items[${idx}][po_no]" class="form-control form-control-sm"
                   value="${d.po_no||''}" min="1" ${ro} style="width:62px;">
        </td>
        <td>
            <input type="date" name="items[${idx}][po_date]" class="form-control form-control-sm"
                   value="${d.po_date||''}" ${ro} style="width:84px;">
        </td>
        <td>
            <input type="number" name="items[${idx}][p_qty]" class="form-control form-control-sm"
                   value="${d.p_qty||0}" step="0.001" min="0" ${ro} style="width:62px;">
        </td>
        <td>
            <input type="number" name="items[${idx}][pb_no]" class="form-control form-control-sm"
                   value="${d.pb_no||''}" min="1" ${ro} style="width:62px;">
        </td>
        <td>
            <input type="number" class="form-control form-control-sm req-qty-inp"
                   value="${calcReq(d.ord_qty, d.bill_qty)}" readonly style="width:66px;">
        </td>
        ${isReadOnly ? '' : `<td>
            <button type="button" class="btn btn-danger btn-xs del-row" title="Remove">
                <i class="bi bi-x-lg"></i>
            </button>
        </td>`}
    </tr>`;
}

function calcReq(ordQty, billQty) {
    const o = parseFloat(ordQty) || 0;
    const b = parseFloat(billQty) || 0;
    return Math.max(0, o - b).toFixed(3);
}

function addRow(data) {
    const idx = rowIdx++;
    $('#detailTbody').append(buildRow(idx, data || {}));

    const $sel = $(`#prodSel-${idx}`);
    $sel.select2({
        theme: 'bootstrap-5',
        placeholder: 'Select Material...',
        dropdownParent: $('body'),
        width: '100%',
    });

    if (data && data.mat_code) {
        $sel.val(data.mat_code).trigger('change.select2');
    }
    updateSlNos();
}

function updateSlNos() {
    $('#detailTbody .detail-row').each(function (i) {
        $(this).find('.sl-no').text(i + 1);
    });
}

// ── Events ────────────────────────────────────────────────────────────────────
$('#detailTbody').on('change', '.prod-sel', function () {
    if (isReadOnly) return;
    const matCode = $(this).val();
    if (!matCode) return;
    const p   = productsMap[matCode];
    if (!p) return;
    const row = $(this).closest('tr');
    row.find('.uom-name').val(p.uom_name);
    row.find('.uom-val').val(p.uom_code);
});

$('#detailTbody').on('input', '.ord-qty, .bill-qty', function () {
    const row     = $(this).closest('tr');
    const ordQty  = row.find('.ord-qty').val();
    const billQty = row.find('.bill-qty').val();
    row.find('.req-qty-inp').val(calcReq(ordQty, billQty));
});

$('#detailTbody').on('click', '.del-row', function () {
    if ($('#detailTbody .detail-row').length <= 1) {
        alert('At least one item row is required.');
        return;
    }
    $(this).closest('tr').remove();
    updateSlNos();
});

$('#addRowBtn').on('click', function () { addRow(); });

// ── Customer Select2 ──────────────────────────────────────────────────────────
$('#party_code').select2({
    theme: 'bootstrap-5',
    placeholder: 'Search customer...',
    width: '100%',
});

// ── Init ──────────────────────────────────────────────────────────────────────
$(function () {
    @php
        $oldItems = old('items');
        $dbRows   = [];
        if ($dtls && $dtls->count() > 0) {
            foreach ($dtls as $d) {
                $dbRows[] = [
                    'mat_code'  => $d->mat_code,
                    'mat_name'  => $d->mat_name,
                    'narration' => $d->narration,
                    'ord_qty'   => $d->ord_qty,
                    'uom'       => $d->uom,
                    'uom_name'  => $d->uom_name,
                    'bill_qty'  => $d->bill_qty,
                    'ex_qty'    => $d->ex_qty,
                    'po_no'     => $d->po_no,
                    'po_date'   => $d->po_date,
                    'p_qty'     => $d->p_qty,
                    'pb_no'     => $d->pb_no,
                    'req_qty'   => $d->req_qty,
                ];
            }
        }
    @endphp

    @if($oldItems)
        const initRows = @json($oldItems);
        if (initRows && initRows.length > 0) {
            initRows.forEach(function (item) {
                if (!item) return;
                const p = productsMap[item.mat_code] || {};
                addRow({
                    mat_code  : item.mat_code,
                    narration : item.narration || '',
                    ord_qty   : item.ord_qty,
                    uom       : item.uom || p.uom_code || '',
                    uom_name  : p.uom_name || '',
                    bill_qty  : item.bill_qty || 0,
                    ex_qty    : item.ex_qty || 0,
                    po_no     : item.po_no || '',
                    po_date   : item.po_date || '',
                    p_qty     : item.p_qty || 0,
                    pb_no     : item.pb_no || '',
                    req_qty   : item.req_qty || 0,
                });
            });
        } else {
            addRow();
        }
    @elseif(count($dbRows) > 0)
        const dbRows = @json($dbRows);
        dbRows.forEach(function (item) { addRow(item); });
    @else
        if (!isReadOnly) addRow();
    @endif
});

// ── Validate submit ───────────────────────────────────────────────────────────
$('#orderForm').on('submit', function () {
    if ($('#detailTbody .detail-row').length === 0) {
        alert('Please add at least one item row.');
        return false;
    }
    return true;
});
</script>
@endpush
