@extends('layouts.print')
@section('title', 'Sale Invoice #'.$hdr->inv_no)

@push('styles')
<style>
    .yoshita-header { background: linear-gradient(135deg, #b8860b 0%, #ffd700 50%, #b8860b 100%);
                      padding: 12px 16px; text-align:center; border-radius: 4px 4px 0 0; margin-bottom: 8px; }
    .yoshita-header h4 { color: #fff; text-shadow: 1px 1px 2px rgba(0,0,0,0.4); font-size:1.3rem; margin:0; letter-spacing:3px; }
    .yoshita-header p  { color: #fff3cd; font-size:0.78rem; margin:2px 0 0; }
    .plating-header h5 { letter-spacing:4px; border-bottom: 3px double #333; padding-bottom:6px; display:inline-block; }
    .format-picker     { background:#f8f9fa; border:1px solid #dee2e6; border-radius:6px; padding:10px 14px; margin-bottom:16px; }
</style>
@endpush

@section('content')

{{-- ── No-Print: Format picker + Print/Close buttons ── --}}
<div class="no-print format-picker d-flex align-items-center gap-3 flex-wrap">
    <div class="d-flex align-items-center gap-2">
        <label class="fw-bold mb-0" style="font-size:0.83rem;">Print Format:</label>
        <select id="formatSelect" class="form-select form-select-sm" style="width:auto;">
            <option value="1" {{ $format == 1 ? 'selected' : '' }}>Format 1 — Standard Invoice</option>
            <option value="2" {{ $format == 2 ? 'selected' : '' }}>Format 2 — Plating Invoice</option>
            <option value="3" {{ $format == 3 ? 'selected' : '' }}>Format 3 — Yoshita Invoice</option>
        </select>
    </div>
    <button onclick="window.print()" class="btn btn-primary btn-sm">
        <i class="bi bi-printer me-1"></i> Print
    </button>
    <button onclick="window.close()" class="btn btn-secondary btn-sm">Close</button>
    <small class="text-muted ms-2">
        Invoice #{{ $hdr->inv_no }} | {{ \Carbon\Carbon::parse($hdr->inv_date)->format('d-M-Y') }}
        | {{ $hdr->bill_type }}
    </small>
</div>

@php
    $halfTax   = $hdr->tax_amount / 2;
    $isInState = !$hdr->inout_state;
    $amtWords  = \App\Http\Controllers\Transactions\PurchaseController::numberToWords((float)$hdr->nett);
@endphp

{{-- ════════════════════════════════════════════════════════════════════ --}}
{{-- FORMAT 1 — Standard Sale Invoice                                    --}}
{{-- ════════════════════════════════════════════════════════════════════ --}}
<div id="format1" class="print-format">

    <div class="print-header">
        @if($firm)
        <h4>{{ $firm->firm_name }}</h4>
        <p>{{ $firm->address }}@if($firm->place), {{ $firm->place }}@endif</p>
        <p>
            @if($firm->phone)Phone: {{ $firm->phone }}@endif
            @if($firm->mobile) &nbsp;|&nbsp; Mobile: {{ $firm->mobile }}@endif
            @if($firm->tin_no) &nbsp;|&nbsp; GSTIN: {{ $firm->tin_no }}@endif
        </p>
        @else
        <h4>Subha Enterprises</h4>
        @endif
        <h5 class="mt-2 mb-0" style="letter-spacing:3px; font-size:0.92rem;">SALE INVOICE</h5>
    </div>

    <div class="row mb-3">
        <div class="col-6">
            <div class="section-title mb-1">Bill To (Customer)</div>
            <strong>{{ $hdr->party_name }}</strong><br>
            @if($hdr->party_address){{ $hdr->party_address }}<br>@endif
            @if($hdr->party_place){{ $hdr->party_place }}@if($hdr->party_state), {{ $hdr->party_state }}@endif<br>@endif
            @if($hdr->tin_grn_no)GSTIN: {{ $hdr->tin_grn_no }}@endif
        </div>
        <div class="col-6 text-end">
            <table class="ms-auto" style="font-size:0.82rem;">
                <tr><td class="pe-3 text-muted">Invoice No</td><td class="fw-bold">{{ $hdr->inv_no }}</td></tr>
                <tr><td class="pe-3 text-muted">Date</td><td>{{ \Carbon\Carbon::parse($hdr->inv_date)->format('d-M-Y') }}</td></tr>
                <tr><td class="pe-3 text-muted">Bill Type</td><td>{{ $hdr->bill_type }}</td></tr>
                <tr><td class="pe-3 text-muted">Branch</td><td>{{ $hdr->br_name }}</td></tr>
            </table>
        </div>
    </div>

    @include('transactions.sale._items_table')

    <div class="row mt-3">
        <div class="col-7">
            <div class="words-box">
                <span class="text-muted" style="font-size:0.75rem;">Amount in Words: </span>
                <strong>{{ $amtWords }}</strong>
            </div>
        </div>
        <div class="col-5">
            @include('transactions.sale._totals_box', ['isInState' => $isInState, 'halfTax' => $halfTax])
        </div>
    </div>

    @include('transactions.sale._sign_area')
</div>

{{-- ════════════════════════════════════════════════════════════════════ --}}
{{-- FORMAT 2 — Plating Invoice                                          --}}
{{-- ════════════════════════════════════════════════════════════════════ --}}
<div id="format2" class="print-format" style="display:none;">

    <div class="print-header plating-header">
        @if($firm)<h4>{{ $firm->firm_name }}</h4>@else<h4>Subha Enterprises</h4>@endif
        @if($firm && $firm->address)<p>{{ $firm->address }}@if($firm->place), {{ $firm->place }}@endif</p>@endif
        @if($firm && $firm->tin_no)<p>GSTIN: {{ $firm->tin_no }}</p>@endif
        <h5 class="mt-2 mb-0">PLATING INVOICE</h5>
    </div>

    <div class="row mb-3" style="border: 1px solid #ccc; padding: 8px; border-radius:4px; margin: 0 0 12px;">
        <div class="col-7">
            <span class="section-title">Customer: </span>
            <strong>{{ $hdr->party_name }}</strong>
            @if($hdr->party_place) — {{ $hdr->party_place }}@endif<br>
            @if($hdr->tin_grn_no)<small>GSTIN: {{ $hdr->tin_grn_no }}</small>@endif
        </div>
        <div class="col-5 text-end">
            <small class="text-muted">Invoice No: </small><strong>{{ $hdr->inv_no }}</strong><br>
            <small class="text-muted">Date: </small><strong>{{ \Carbon\Carbon::parse($hdr->inv_date)->format('d-M-Y') }}</strong><br>
            <small class="text-muted">Branch: </small>{{ $hdr->br_name }}
        </div>
    </div>

    @include('transactions.sale._items_table')

    <div class="row mt-3">
        <div class="col-7">
            <div class="words-box">
                <span class="text-muted" style="font-size:0.75rem;">Amount in Words: </span>
                <strong>{{ $amtWords }}</strong>
            </div>
        </div>
        <div class="col-5">
            @include('transactions.sale._totals_box', ['isInState' => $isInState, 'halfTax' => $halfTax])
        </div>
    </div>

    @include('transactions.sale._sign_area')
</div>

{{-- ════════════════════════════════════════════════════════════════════ --}}
{{-- FORMAT 3 — Yoshita Invoice                                          --}}
{{-- ════════════════════════════════════════════════════════════════════ --}}
<div id="format3" class="print-format" style="display:none;">

    <div class="yoshita-header mb-0">
        <h4>✦ YOSHITA ✦</h4>
        @if($firm)<p>{{ $firm->firm_name }}@if($firm->place) — {{ $firm->place }}@endif</p>@endif
        @if($firm && $firm->tin_no)<p style="font-size:0.72rem;">GSTIN: {{ $firm->tin_no }}</p>@endif
    </div>
    <div class="print-header" style="border-top:none; padding-top:6px;">
        <h5 style="letter-spacing:2px; font-size:0.90rem;">YOSHITA SALE INVOICE</h5>
    </div>

    <div class="d-flex justify-content-between mb-3 px-1" style="font-size:0.82rem;">
        <div>
            <strong>{{ $hdr->party_name }}</strong><br>
            @if($hdr->party_place){{ $hdr->party_place }}@if($hdr->party_state), {{ $hdr->party_state }}@endif<br>@endif
            @if($hdr->tin_grn_no)GSTIN: {{ $hdr->tin_grn_no }}@endif
        </div>
        <div class="text-end">
            Invoice No: <strong>{{ $hdr->inv_no }}</strong><br>
            Date: <strong>{{ \Carbon\Carbon::parse($hdr->inv_date)->format('d-M-Y') }}</strong><br>
            Bill Type: {{ $hdr->bill_type }}
        </div>
    </div>

    @include('transactions.sale._items_table')

    <div class="row mt-3">
        <div class="col-7">
            <div class="words-box">
                <span class="text-muted" style="font-size:0.75rem;">Amount in Words: </span>
                <strong>{{ $amtWords }}</strong>
            </div>
        </div>
        <div class="col-5">
            @include('transactions.sale._totals_box', ['isInState' => $isInState, 'halfTax' => $halfTax])
        </div>
    </div>

    <div class="sign-area d-flex justify-content-between mt-4">
        <div><div class="sign-line">Customer Signature</div></div>
        <div class="text-center"><small class="text-muted" style="font-size:0.7rem;">✦ YOSHITA — Premium Jewelry ✦</small></div>
        <div class="text-end"><div class="sign-line">Authorised Signatory</div></div>
    </div>
</div>

<script>
document.getElementById('formatSelect').addEventListener('change', function () {
    document.querySelectorAll('.print-format').forEach(function (el) { el.style.display = 'none'; });
    document.getElementById('format' + this.value).style.display = 'block';
});
</script>
@endsection
