@extends('layouts.app')
@section('title', 'Parcel List')

@push('styles')
<style>
.rpt-table th  { background:#1a3c5e; color:#fff; padding:6px 8px; font-size:0.80rem; white-space:nowrap; }
.rpt-table td  { padding:5px 8px; font-size:0.80rem; vertical-align:middle; }
.rpt-table .date-header td { background:#1a3c5e; color:#fff; font-weight:700; font-size:0.82rem; }
.rpt-table .day-total   td { background:#fff3cd; font-weight:600; }
.rpt-table .grand-total td { background:#1a3c5e; color:#fff; font-weight:700; }
@media print {
    .no-print, nav, .footer, .alert { display:none !important; }
    .card { box-shadow:none !important; border:1px solid #ccc !important; }
    body { background:#fff !important; font-size:11px; }
    .rpt-table th                { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
    .rpt-table .date-header td   { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
    .rpt-table .day-total   td   { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
    .rpt-table .grand-total td   { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
}
</style>
@endpush

@section('content')

<div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <h5 class="mb-0"><i class="bi bi-box me-1 text-primary"></i> Parcel List</h5>
</div>

{{-- Filter Card --}}
<div class="card mb-3 no-print">
    <div class="card-header py-2">Filter</div>
    <div class="card-body py-2">
        @if($error)
            <div class="alert alert-danger py-1 mb-2"><i class="bi bi-exclamation-triangle me-1"></i>{{ $error }}</div>
        @endif
        <form method="GET" action="{{ route('reports.parcel.list') }}" id="filterForm">
            <div class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label">Date From <span class="text-danger">*</span></label>
                    <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control form-control-sm" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Date To <span class="text-danger">*</span></label>
                    <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control form-control-sm" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Branch</label>
                    <select name="br_code" class="form-select form-select-sm">
                        <option value="">All Branches</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->br_code }}" {{ $brCode == $b->br_code ? 'selected' : '' }}>
                                {{ $b->br_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" name="show" value="1" class="btn btn-primary btn-sm">
                        <i class="bi bi-search me-1"></i>Show
                    </button>
                    @if($showReport && !empty($dateGroups) && empty($error))
                    <button type="button" onclick="window.print()" class="btn btn-secondary btn-sm">
                        <i class="bi bi-printer me-1"></i>Print
                    </button>
                    <a href="{{ route('reports.parcel.list.export') }}?{{ http_build_query(request()->query()) }}"
                       class="btn btn-success btn-sm">
                        <i class="bi bi-file-earmark-excel me-1"></i>Excel
                    </a>
                    @endif
                </div>
            </div>
        </form>
    </div>
</div>

@if($showReport && empty($error))

{{-- Print Header --}}
<div class="d-none d-print-block text-center mb-2">
    <h5 class="mb-0">Parcel List</h5>
    <div style="font-size:0.85rem;">
        {{ \Carbon\Carbon::parse($dateFrom)->format('d-M-Y') }} to {{ \Carbon\Carbon::parse($dateTo)->format('d-M-Y') }}
    </div>
</div>

<div class="card">
    <div class="card-header py-2 d-flex justify-content-between align-items-center no-print">
        <span><i class="bi bi-box me-1"></i>Parcel List</span>
        <span class="text-muted" style="font-size:0.80rem;">
            {{ \Carbon\Carbon::parse($dateFrom)->format('d-M-Y') }} — {{ \Carbon\Carbon::parse($dateTo)->format('d-M-Y') }}
        </span>
    </div>
    <div class="card-body p-0">
        @if(empty($dateGroups))
            <div class="text-center py-4 text-muted"><i class="bi bi-inbox me-1"></i>No parcels found.</div>
        @else
        @php
            $grandItems  = 0;
            $grandQty    = 0;
            $grandAmount = 0;
        @endphp
        <div class="table-responsive">
        <table class="table table-bordered rpt-table mb-0">
            <thead>
                <tr>
                    <th>Customer Name</th>
                    <th>Place</th>
                    <th class="text-center">Items</th>
                    <th class="text-end">Qty</th>
                    <th class="text-end">Amount</th>
                </tr>
            </thead>
            <tbody>
            @foreach($dateGroups as $date => $parcels)
                {{-- Date Group Header --}}
                <tr class="date-header">
                    <td colspan="5">
                        <i class="bi bi-calendar-event me-1"></i>
                        Date: {{ \Carbon\Carbon::parse($date)->format('d-M-Y') }}
                        ({{ \Carbon\Carbon::parse($date)->format('l') }})
                    </td>
                </tr>

                @php $dayItems = 0; $dayQty = 0; $dayAmount = 0; @endphp
                @foreach($parcels as $parcel)
                <tr>
                    <td>{{ $parcel->party_name }}</td>
                    <td>{{ $parcel->place }}</td>
                    <td class="text-center">{{ $parcel->item_count }}</td>
                    <td class="text-end">{{ indianFmt($parcel->total_qty) }}</td>
                    <td class="text-end">{{ indianFmt($parcel->total_amount) }}</td>
                </tr>
                @php
                    $dayItems  += $parcel->item_count;
                    $dayQty    += $parcel->total_qty;
                    $dayAmount += $parcel->total_amount;
                @endphp
                @endforeach

                {{-- Day Total --}}
                <tr class="day-total">
                    <td colspan="2">
                        <i class="bi bi-calendar-check me-1"></i>
                        Day Total — {{ \Carbon\Carbon::parse($date)->format('d-M-Y') }}
                        ({{ count($parcels) }} parcels)
                    </td>
                    <td class="text-center">{{ $dayItems }}</td>
                    <td class="text-end">{{ indianFmt($dayQty) }}</td>
                    <td class="text-end">{{ indianFmt($dayAmount) }}</td>
                </tr>

                @php
                    $grandItems  += $dayItems;
                    $grandQty    += $dayQty;
                    $grandAmount += $dayAmount;
                @endphp
            @endforeach
            </tbody>
            <tfoot>
                <tr class="grand-total">
                    <td colspan="2"><i class="bi bi-check2-all me-1"></i>Grand Total</td>
                    <td class="text-center">{{ $grandItems }}</td>
                    <td class="text-end">{{ indianFmt($grandQty) }}</td>
                    <td class="text-end">{{ indianFmt($grandAmount) }}</td>
                </tr>
            </tfoot>
        </table>
        </div>
        @endif
    </div>
</div>
@endif

@endsection
