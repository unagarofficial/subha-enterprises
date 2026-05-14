@extends('layouts.print')
@section('title', 'Stock Transfer #'.$hdr->iss_no)

@section('content')

{{-- ── Firm Header ── --}}
<div class="print-header">
    @if($firm)
    <h4>{{ $firm->firm_name }}</h4>
    <p>{{ $firm->address }}@if($firm->place), {{ $firm->place }}@endif</p>
    @else
    <h4>Subha Enterprises</h4>
    @endif
    <h5 class="mt-2 mb-0" style="letter-spacing:2px; font-size:0.95rem;">STOCK TRANSFER</h5>
</div>

{{-- ── Transfer Meta ── --}}
<div class="row mb-3">
    <div class="col-6">
        <div class="section-title mb-1">From Branch</div>
        <strong>{{ $hdr->from_br_name }}</strong><br>
        @if($hdr->from_br_address){{ $hdr->from_br_address }}<br>@endif
        @if($hdr->from_br_place){{ $hdr->from_br_place }}@endif
    </div>
    <div class="col-6 text-end">
        <div class="section-title mb-1">To Branch</div>
        <strong>{{ $hdr->to_br_name }}</strong><br>
        @if($hdr->to_br_address){{ $hdr->to_br_address }}<br>@endif
        @if($hdr->to_br_place){{ $hdr->to_br_place }}@endif
    </div>
</div>

<div class="row mb-3">
    <div class="col-12">
        <table class="ms-0" style="font-size:0.82rem;">
            <tr>
                <td class="pe-4 text-muted">Issue No</td>
                <td class="fw-bold pe-5">{{ $hdr->iss_no }}</td>
                <td class="pe-4 text-muted">Date</td>
                <td class="fw-bold">{{ \Carbon\Carbon::parse($hdr->iss_date)->format('d-M-Y') }}</td>
            </tr>
        </table>
    </div>
</div>

{{-- ── Items Table ── --}}
<table class="inv-table">
    <thead>
        <tr>
            <th style="width:35px">Sl</th>
            <th style="width:100px">Item Code</th>
            <th>Item Name</th>
            <th class="text-end" style="width:90px">Order Qty</th>
            <th class="text-end" style="width:90px">Sent Qty</th>
            <th style="width:60px">UOM</th>
            <th style="width:80px">PO No</th>
        </tr>
    </thead>
    <tbody>
        @foreach($dtls as $d)
        <tr>
            <td class="text-center">{{ $d->sl_no }}</td>
            <td>{{ $d->item_code }}</td>
            <td>{{ $d->mat_name }}</td>
            <td class="text-end">{{ $d->order_qty }}</td>
            <td class="text-end fw-bold">{{ $d->sent_qty }}</td>
            <td>{{ $d->uom_name }}</td>
            <td>{{ $d->po_no ?? '—' }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="3" class="text-end">Total</td>
            <td class="text-end">{{ $dtls->sum('order_qty') }}</td>
            <td class="text-end fw-bold">{{ $dtls->sum('sent_qty') }}</td>
            <td colspan="2"></td>
        </tr>
    </tfoot>
</table>

{{-- ── Signature ── --}}
<div class="sign-area d-flex justify-content-between mt-5">
    <div>
        <div class="sign-line">Sent By</div>
    </div>
    <div>
        <div class="sign-line">Received By</div>
    </div>
    <div class="text-end">
        <div class="sign-line">Authorised Signatory</div>
    </div>
</div>

@endsection
