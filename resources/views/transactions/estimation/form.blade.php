@extends('layouts.app')
@section('title', $hdr ? 'Edit Estimation #'.$hdr->inv_no : 'New Estimation Invoice')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
<style>
    .detail-table { font-size: 0.78rem; }
    .detail-table th { background: #1a3c5e; color: #fff; padding: 5px; white-space: nowrap; }
    .detail-table td { padding: 2px 3px; vertical-align: middle; }
    .detail-table input, .detail-table select { font-size: 0.77rem; }
    .select2-container--open { z-index: 9999; }
    .ro-field { background: #e9ecef !important; font-weight: 600; }
</style>
@endpush

@section('content')

@php
    $isEdit     = !is_null($hdr);
    $formAction = $isEdit
        ? route('transactions.estimation.update', [$hdr->br_code, $hdr->inv_no])
        : route('transactions.estimation.store');
    $method     = $isEdit ? 'PUT' : 'POST';
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">
        <i class="bi bi-file-earmark-text me-1 text-primary"></i>
        {{ $isEdit ? 'Edit Estimation Invoice #'.$hdr->inv_no : 'New Estimation Invoice' }}
    </h5>
    <div class="d-flex gap-2">
        @if($isEdit)
        <a href="{{ route('transactions.estimation.print', [$hdr->br_code, $hdr->inv_no]) }}"
           target="_blank" class="btn btn-info btn-sm text-white">
            <i class="bi bi-printer me-1"></i> Print
        </a>
        @endif
        <a href="{{ route('transactions.estimation.index') }}" class="btn btn-secondary btn-sm">
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

<form method="POST" action="{{ $formAction }}" id="estForm">
    @csrf
    @if($isEdit) @method('PUT') @endif

    {{-- ── Header ─────────────────────────────────────────────────────────── --}}
    <div class="card mb-3">
        <div class="card-header bg-primary text-white py-2">Header</div>
        <div class="card-body">
            <div class="row g-3">

                <div class="col-md-3">
                    <label class="form-label">Branch <span class="text-danger">*</span></label>
                    <select name="br_code" id="br_code" class="form-select form-select-sm">
                        @foreach($branches as $b)
                        <option value="{{ $b->br_code }}"
                            {{ (old('br_code', $hdr->br_code ?? session('br_code')) == $b->br_code) ? 'selected' : '' }}>
                            {{ $b->br_name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">HO Code</label>
                    <input type="text" class="form-control form-control-sm ro-field"
                           value="{{ $firm?->ho_code ?? '—' }}" readonly>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Invoice No</label>
                    <input type="text" class="form-control form-control-sm ro-field"
                           value="{{ $nextInvNo }}" readonly>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Invoice Date <span class="text-danger">*</span></label>
                    <input type="date" name="inv_date" id="inv_date"
                           class="form-control form-control-sm @error('inv_date') is-invalid @enderror"
                           value="{{ old('inv_date', $hdr->inv_date ?? session('login_date')) }}" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Party <span class="text-danger">*</span></label>
                    <select name="party_code" id="party_code"
                            class="form-select form-select-sm @error('party_code') is-invalid @enderror" required>
                        <option value="">— Select Party —</option>
                        @foreach($parties as $p)
                        <option value="{{ $p->party_code }}"
                            {{ old('party_code', $hdr->party_code ?? '') == $p->party_code ? 'selected' : '' }}>
                            {{ $p->party_name }} ({{ $p->party_type }})
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Gross</label>
                    <input type="text" id="grossAmt" class="form-control form-control-sm ro-field text-end"
                           value="{{ number_format($hdr->gross ?? 0, 2) }}" readonly>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Tax</label>
                    <select name="tax_id" id="taxSelect" class="form-select form-select-sm">
                        <option value="">No Tax</option>
                        @foreach($taxes as $t)
                        <option value="{{ $t->id }}"
                                data-rate="{{ $t->tax_rate }}"
                            {{ old('tax_id', '') == $t->id ? 'selected' : ''
                               @if($isEdit) || $t->tax_rate == $hdr->tax_rate @endif }}>
                            {{ $t->tax_name }} ({{ $t->tax_rate }}%)
                        </option>
                        @endforeach
                    </select>
                    <input type="hidden" name="tax_rate" id="taxRate"
                           value="{{ old('tax_rate', $hdr->tax_rate ?? 0) }}">
                </div>

                <div class="col-md-1">
                    <label class="form-label">Tax%</label>
                    <input type="text" id="taxPct" class="form-control form-control-sm ro-field text-end"
                           value="{{ number_format($hdr->tax_rate ?? 0, 2) }}" readonly>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Tax Amount</label>
                    <input type="text" id="taxAmt" class="form-control form-control-sm ro-field text-end"
                           value="{{ number_format($hdr->tax_amount ?? 0, 2) }}" readonly>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Net Amount</label>
                    <input type="text" id="netAmt" class="form-control form-control-sm ro-field text-end fw-bold"
                           value="{{ number_format($hdr->nett ?? 0, 2) }}" readonly>
                </div>

            </div>
        </div>
    </div>

    {{-- ── Detail Rows ──────────────────────────────────────────────────────── --}}
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center py-2">
            <span class="fw-bold">Items</span>
            <button type="button" class="btn btn-sm btn-success" id="addRow">
                <i class="bi bi-plus-circle me-1"></i> Add Row
            </button>
        </div>
        <div class="card-body p-0">
            <table class="table table-bordered mb-0 detail-table" id="detailTable">
                <thead>
                    <tr>
                        <th style="width:35px">Sl</th>
                        <th style="min-width:200px">Material</th>
                        <th style="width:80px">Qty</th>
                        <th style="width:90px">UOM</th>
                        <th style="width:100px">Rate</th>
                        <th style="width:110px">Sale Value</th>
                        <th style="min-width:140px">Narration</th>
                        <th style="width:40px"></th>
                    </tr>
                </thead>
                <tbody id="detailBody">
                @if($isEdit && $dtls->count())
                    @foreach($dtls as $i => $d)
                    <tr>
                        <td class="text-center sl-no">{{ $i + 1 }}</td>
                        <td>
                            <select name="items[{{ $i }}][mat_code]" class="form-select form-select-sm mat-select" required>
                                <option value="{{ $d->mat_code }}" selected>{{ $d->mat_name }} ({{ $d->mat_code }})</option>
                            </select>
                        </td>
                        <td><input type="number" name="items[{{ $i }}][qty]" class="form-control form-control-sm qty-inp text-end"
                                   value="{{ $d->qty }}" step="0.001" min="0.001" required></td>
                        <td>
                            <select name="items[{{ $i }}][uom]" class="form-select form-select-sm uom-sel">
                                @foreach($uoms as $u)
                                <option value="{{ $u->uom_code }}" {{ $d->uom == $u->uom_code ? 'selected' : '' }}>{{ $u->uom_name }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td><input type="number" name="items[{{ $i }}][rate]" class="form-control form-control-sm rate-inp text-end"
                                   value="{{ $d->rate }}" step="0.01" min="0"></td>
                        <td><input type="text" class="form-control form-control-sm sval-inp ro-field text-end"
                                   value="{{ number_format($d->s_value, 2) }}" readonly></td>
                        <td><input type="text" name="items[{{ $i }}][narration]" class="form-control form-control-sm"
                                   value="{{ $d->narration }}"></td>
                        <td class="text-center">
                            <button type="button" class="btn btn-xs btn-danger del-row"><i class="bi bi-trash"></i></button>
                        </td>
                    </tr>
                    @endforeach
                @endif
                </tbody>
            </table>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-save me-1"></i> {{ $isEdit ? 'Update' : 'Save' }} Estimation
        </button>
        <a href="{{ route('transactions.estimation.index') }}" class="btn btn-secondary">Cancel</a>
    </div>

</form>

{{-- Row template (hidden) --}}
<template id="rowTpl">
    <tr>
        <td class="text-center sl-no"></td>
        <td>
            <select name="items[__IDX__][mat_code]" class="form-select form-select-sm mat-select" required>
                <option value="">— Select Material —</option>
                @foreach($products as $pr)
                <option value="{{ $pr->mat_code }}" data-rate="{{ $pr->sale_rate }}" data-uom="{{ $pr->uom }}">
                    {{ $pr->mat_name }} ({{ $pr->mat_code }})
                </option>
                @endforeach
            </select>
        </td>
        <td><input type="number" name="items[__IDX__][qty]" class="form-control form-control-sm qty-inp text-end"
                   value="1" step="0.001" min="0.001" required></td>
        <td>
            <select name="items[__IDX__][uom]" class="form-select form-select-sm uom-sel">
                @foreach($uoms as $u)
                <option value="{{ $u->uom_code }}">{{ $u->uom_name }}</option>
                @endforeach
            </select>
        </td>
        <td><input type="number" name="items[__IDX__][rate]" class="form-control form-control-sm rate-inp text-end"
                   value="0" step="0.01" min="0"></td>
        <td><input type="text" class="form-control form-control-sm sval-inp ro-field text-end" value="0.00" readonly></td>
        <td><input type="text" name="items[__IDX__][narration]" class="form-control form-control-sm"></td>
        <td class="text-center">
            <button type="button" class="btn btn-xs btn-danger del-row"><i class="bi bi-trash"></i></button>
        </td>
    </tr>
</template>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
let rowIdx = {{ $isEdit ? $dtls->count() : 0 }};

// ── Tax dropdown change ───────────────────────────────────────────────────────
function recalcTotals() {
    let gross = 0;
    $('#detailBody .qty-inp').each(function() {
        const row  = $(this).closest('tr');
        const qty  = parseFloat($(this).val()) || 0;
        const rate = parseFloat(row.find('.rate-inp').val()) || 0;
        const sv   = Math.round(qty * rate * 100) / 100;
        row.find('.sval-inp').val(sv.toFixed(2));
        gross += sv;
    });
    gross = Math.round(gross * 100) / 100;
    const taxPct = parseFloat($('#taxRate').val()) || 0;
    const taxAmt = Math.round(gross * taxPct / 100 * 100) / 100;
    const nett   = gross + taxAmt;
    $('#grossAmt').val(gross.toFixed(2));
    $('#taxPct').val(taxPct.toFixed(2));
    $('#taxAmt').val(taxAmt.toFixed(2));
    $('#netAmt').val(nett.toFixed(2));
}

$('#taxSelect').on('change', function() {
    const rate = $(this).find(':selected').data('rate') || 0;
    $('#taxRate').val(rate);
    recalcTotals();
});

// ── Add row ───────────────────────────────────────────────────────────────────
$('#addRow').on('click', function() {
    const tpl = document.getElementById('rowTpl').innerHTML.replaceAll('__IDX__', rowIdx++);
    $('#detailBody').append(tpl);
    const newRow = $('#detailBody tr:last');
    initSelect2(newRow.find('.mat-select'));
    renumberRows();
});

// ── Delete row ────────────────────────────────────────────────────────────────
$(document).on('click', '.del-row', function() {
    $(this).closest('tr').remove();
    renumberRows();
    recalcTotals();
});

// ── Material select change — auto-fill rate + UOM ─────────────────────────────
$(document).on('change', '.mat-select', function() {
    const sel  = $(this).find(':selected');
    const rate = sel.data('rate') || 0;
    const uom  = sel.data('uom') || '';
    const row  = $(this).closest('tr');
    row.find('.rate-inp').val(rate);
    if (uom) row.find('.uom-sel').val(uom);
    recalcTotals();
});

// ── Qty/Rate change ───────────────────────────────────────────────────────────
$(document).on('input', '.qty-inp, .rate-inp', recalcTotals);

// ── Renumber rows ─────────────────────────────────────────────────────────────
function renumberRows() {
    $('#detailBody tr').each(function(i) { $(this).find('.sl-no').text(i + 1); });
}

// ── Select2 init ──────────────────────────────────────────────────────────────
function initSelect2(el) {
    el.select2({ theme: 'bootstrap-5', width: '100%', dropdownParent: $('body') });
}

// Init existing rows' selects on page load
$(function() {
    $('#party_code').select2({ theme: 'bootstrap-5', width: '100%' });

    // For edit mode: init existing mat-select rows (they only have the selected option, no full list)
    // We need to replace them with the full product list
    @if($isEdit)
    const products = @json($products->map(fn($p) => ['id' => $p->mat_code, 'text' => $p->mat_name.' ('.$p->mat_code.')', 'rate' => $p->sale_rate, 'uom' => $p->uom]));
    $('#detailBody .mat-select').each(function() {
        const currentVal = $(this).val();
        $(this).empty();
        $(this).append('<option value="">— Select Material —</option>');
        products.forEach(p => {
            const opt = new Option(p.text, p.id, p.id === currentVal, p.id === currentVal);
            $(opt).data('rate', p.rate).data('uom', p.uom);
            $(this).append(opt);
        });
        initSelect2($(this));
    });
    @endif

    // Set tax dropdown to match saved rate
    @if($isEdit)
    const savedRate = {{ $hdr->tax_rate ?? 0 }};
    $('#taxSelect option').each(function() {
        if (parseFloat($(this).data('rate')) === savedRate) {
            $(this).prop('selected', true);
            $('#taxRate').val(savedRate);
        }
    });
    @endif

    recalcTotals();

    // Add first blank row if new
    @if(!$isEdit)
    $('#addRow').trigger('click');
    @endif
});
</script>
@endpush
