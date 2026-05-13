@extends('layouts.print')
@section('title', 'Sale Return #'.$hdr->inv_no)

@section('content')

{{-- ── Firm Header ── --}}
<div class="print-header">
    @if($firm)
    <h4>{{ $firm->firm_name }}</h4>
    <p>{{ $firm->address }}@if($firm->place), {{ $firm->place }}@endif</p>
    <p>
        @if($firm->phone)Phone: {{ $firm->phone }}@endif
        @if($firm->mobile) | Mobile: {{ $firm->mobile }}@endif
        @if($firm->tin_no) | GSTIN: {{ $firm->tin_no }}@endif
    </p>
    @else
    <h4>Subha Enterprises</h4>
    @endif
    <h5 class="mt-2 mb-0" style="letter-spacing:2px; font-size:0.95rem;">SALE RETURN NOTE</h5>
</div>

{{-- ── Meta + Customer ── --}}
<div class="row mb-3">
    <div class="col-6">
        <div class="section-title mb-1">Return From (Customer)</div>
        <strong>{{ $hdr->party_name }}</strong><br>
        @if($hdr->party_address){{ $hdr->party_address }}<br>@endif
        @if($hdr->party_place){{ $hdr->party_place }}<br>@endif
        @if($hdr->tin_grn_no)GSTIN: {{ $hdr->tin_grn_no }}@endif
    </div>
    <div class="col-6 text-end">
        <table class="ms-auto" style="font-size:0.82rem;">
            <tr><td class="pe-3 text-muted">Return No</td><td class="fw-bold">{{ $hdr->inv_no }}</td></tr>
            <tr><td class="pe-3 text-muted">Date</td><td>{{ \Carbon\Carbon::parse($hdr->inv_date)->format('d-M-Y') }}</td></tr>
            <tr><td class="pe-3 text-muted">Branch</td><td>{{ $hdr->br_name }}</td></tr>
            <tr><td class="pe-3 text-muted">Bill Type</td><td><strong>{{ $hdr->bill_type }}</strong></td></tr>
        </table>
    </div>
</div>

{{-- ── Items Table ── --}}
<table class="inv-table">
    <thead>
        <tr>
            <th style="width:35px">Sl</th>
            <th>Item Name</th>
            <th class="text-end" style="width:80px">Qty</th>
            <th style="width:55px">UOM</th>
            <th class="text-end" style="width:90px">Rate</th>
            <th class="text-end" style="width:100px">Value</th>
            <th style="width:110px">Narration</th>
        </tr>
    </thead>
    <tbody>
        @foreach($dtls as $d)
        <tr>
            <td class="text-center">{{ $d->sl_no }}</td>
            <td>{{ $d->mat_name }} <small class="text-muted">({{ $d->mat_code }})</small></td>
            <td class="text-end">{{ number_format($d->qty, 3) }}</td>
            <td>{{ $d->uom_name }}</td>
            <td class="text-end">{{ number_format($d->rate, 2) }}</td>
            <td class="text-end">{{ number_format($d->s_value, 2) }}</td>
            <td>{{ $d->narration }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="5" class="text-end">Gross Amount</td>
            <td class="text-end">{{ number_format($hdr->gross, 2) }}</td>
            <td></td>
        </tr>
        @if($hdr->tax_rate > 0)
        <tr>
            <td colspan="5" class="text-end">Tax ({{ number_format($hdr->tax_rate, 2) }}%)</td>
            <td class="text-end">{{ number_format($hdr->tax_amount, 2) }}</td>
            <td></td>
        </tr>
        @endif
        <tr>
            <td colspan="5" class="text-end" style="font-size:0.95rem;">NET AMOUNT</td>
            <td class="text-end fw-bold" style="font-size:0.95rem;">₹ {{ number_format($hdr->nett, 2) }}</td>
            <td></td>
        </tr>
    </tfoot>
</table>

{{-- ── Amount in Words ── --}}
<div class="words-box mt-3">
    <span class="text-muted" style="font-size:0.75rem;">Amount in Words: </span>
    <strong>{{ \App\Http\Controllers\Transactions\PurchaseController::numberToWords((float)$hdr->nett) }}</strong>
</div>

{{-- ── Signature ── --}}
<div class="sign-area d-flex justify-content-between">
    <div>
        <div class="sign-line">Customer's Signature</div>
    </div>
    <div class="text-end">
        <div class="sign-line">Authorised Signatory</div>
    </div>
</div>

@endsection
