@extends('layouts.app')
@section('title', $hdr ? 'Edit Stock Transfer #'.$hdr->iss_no : 'New Stock Transfer')

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
        ? route('transactions.stock-transfer.update', $hdr->iss_no)
        : route('transactions.stock-transfer.store');
    $method     = $isEdit ? 'PUT' : 'POST';
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">
        <i class="bi bi-arrow-left-right me-1 text-primary"></i>
        {{ $isEdit ? 'Edit Stock Transfer #'.$hdr->iss_no : 'New Stock Transfer' }}
    </h5>
    <div class="d-flex gap-2">
        @if($isEdit)
        <a href="{{ route('transactions.stock-transfer.print', $hdr->iss_no) }}"
           target="_blank" class="btn btn-info btn-sm text-white">
            <i class="bi bi-printer me-1"></i> Print
        </a>
        @endif
        <a href="{{ route('transactions.stock-transfer.index') }}" class="btn btn-secondary btn-sm">
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

<form method="POST" action="{{ $formAction }}" id="stForm">
    @csrf
    @if($isEdit) @method('PUT') @endif

    {{-- ── Header ─────────────────────────────────────────────────────────── --}}
    <div class="card mb-3">
        <div class="card-header bg-primary text-white py-2">Transfer Header</div>
        <div class="card-body">
            <div class="row g-3">

                <div class="col-md-2">
                    <label class="form-label">Issue No</label>
                    <input type="text" class="form-control form-control-sm ro-field"
                           value="{{ $nextIssNo }}" readonly>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Issue Date <span class="text-danger">*</span></label>
                    <input type="date" name="iss_date"
                           class="form-control form-control-sm @error('iss_date') is-invalid @enderror"
                           value="{{ old('iss_date', $hdr->iss_date ?? session('login_date')) }}" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label">From Branch</label>
                    <input type="text" class="form-control form-control-sm ro-field"
                           value="{{ session('br_name') }}" readonly>
                </div>

                <div class="col-md-3">
                    <label class="form-label">To Branch <span class="text-danger">*</span></label>
                    <select name="to_br_code" id="to_br_code"
                            class="form-select form-select-sm @error('to_br_code') is-invalid @enderror" required>
                        <option value="">— Select Branch —</option>
                        @foreach($branches as $b)
                        <option value="{{ $b->br_code }}"
                            {{ old('to_br_code', $hdr->to_br_code ?? '') == $b->br_code ? 'selected' : '' }}>
                            {{ $b->br_name }}
                        </option>
                        @endforeach
                    </select>
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
                        <th style="width:120px">Item Code</th>
                        <th style="min-width:180px">Item Name</th>
                        <th style="width:100px">Order Qty</th>
                        <th style="width:100px">Sent Qty</th>
                        <th style="width:100px">PO No</th>
                        <th style="width:40px"></th>
                    </tr>
                </thead>
                <tbody id="detailBody">
                @if($isEdit && $dtls->count())
                    @foreach($dtls as $i => $d)
                    <tr>
                        <td class="text-center sl-no">{{ $i + 1 }}</td>
                        <td>
                            <select name="items[{{ $i }}][item_code]" class="form-select form-select-sm item-select" required>
                                <option value="{{ $d->item_code }}" selected>{{ $d->item_code }}</option>
                            </select>
                        </td>
                        <td>
                            <input type="text" class="form-control form-control-sm ro-field item-name"
                                   value="{{ $d->mat_name }}" readonly>
                        </td>
                        <td>
                            <input type="number" name="items[{{ $i }}][order_qty]" class="form-control form-control-sm text-end"
                                   value="{{ $d->order_qty }}" min="0">
                        </td>
                        <td>
                            <input type="number" name="items[{{ $i }}][sent_qty]" class="form-control form-control-sm text-end"
                                   value="{{ $d->sent_qty }}" min="1" required>
                        </td>
                        <td>
                            <input type="number" name="items[{{ $i }}][po_no]" class="form-control form-control-sm text-end"
                                   value="{{ $d->po_no }}">
                        </td>
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

    <div class="alert alert-warning py-2 mb-3" style="font-size:0.82rem;">
        <i class="bi bi-info-circle me-1"></i>
        <strong>Stock Update:</strong> On save, <em>Sent Qty</em> will be added to Issues of <strong>From Branch</strong>
        and to Receipts of <strong>To Branch</strong>.
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-save me-1"></i> {{ $isEdit ? 'Update' : 'Save' }} Transfer
        </button>
        <a href="{{ route('transactions.stock-transfer.index') }}" class="btn btn-secondary">Cancel</a>
    </div>

</form>

{{-- Row template (hidden) --}}
<template id="rowTpl">
    <tr>
        <td class="text-center sl-no"></td>
        <td>
            <select name="items[__IDX__][item_code]" class="form-select form-select-sm item-select" required>
                <option value="">— Select Item —</option>
                @foreach($products as $pr)
                <option value="{{ $pr->mat_code }}" data-name="{{ $pr->mat_name }}">
                    {{ $pr->mat_code }}
                </option>
                @endforeach
            </select>
        </td>
        <td>
            <input type="text" class="form-control form-control-sm ro-field item-name" placeholder="Item Name" readonly>
        </td>
        <td>
            <input type="number" name="items[__IDX__][order_qty]" class="form-control form-control-sm text-end" value="0" min="0">
        </td>
        <td>
            <input type="number" name="items[__IDX__][sent_qty]" class="form-control form-control-sm text-end" value="1" min="1" required>
        </td>
        <td>
            <input type="number" name="items[__IDX__][po_no]" class="form-control form-control-sm text-end">
        </td>
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

// ── Add row ───────────────────────────────────────────────────────────────────
$('#addRow').on('click', function() {
    const tpl = document.getElementById('rowTpl').innerHTML.replaceAll('__IDX__', rowIdx++);
    $('#detailBody').append(tpl);
    const newRow = $('#detailBody tr:last');
    initSelect2(newRow.find('.item-select'));
    renumberRows();
});

// ── Delete row ────────────────────────────────────────────────────────────────
$(document).on('click', '.del-row', function() {
    $(this).closest('tr').remove();
    renumberRows();
});

// ── Item select change — auto-fill name ───────────────────────────────────────
$(document).on('change', '.item-select', function() {
    const name = $(this).find(':selected').data('name') || '';
    $(this).closest('tr').find('.item-name').val(name);
});

// ── Renumber rows ─────────────────────────────────────────────────────────────
function renumberRows() {
    $('#detailBody tr').each(function(i) { $(this).find('.sl-no').text(i + 1); });
}

// ── Select2 init ──────────────────────────────────────────────────────────────
function initSelect2(el) {
    el.select2({ theme: 'bootstrap-5', width: '100%', dropdownParent: $('body') });
}

$(function() {
    initSelect2($('#to_br_code'));

    // For edit mode: rebuild item selects with full product list
    @if($isEdit)
    const products = @json($products->map(fn($p) => ['id' => $p->mat_code, 'text' => $p->mat_code, 'name' => $p->mat_name]));
    $('#detailBody .item-select').each(function() {
        const currentVal  = $(this).val();
        const currentName = $(this).closest('tr').find('.item-name').val();
        $(this).empty();
        $(this).append('<option value="">— Select Item —</option>');
        products.forEach(p => {
            const opt = new Option(p.text, p.id, p.id === currentVal, p.id === currentVal);
            $(opt).data('name', p.name);
            $(this).append(opt);
        });
        initSelect2($(this));
        if (currentName) $(this).closest('tr').find('.item-name').val(currentName);
    });
    @endif

    // Add first blank row if new
    @if(!$isEdit)
    $('#addRow').trigger('click');
    @endif
});
</script>
@endpush
