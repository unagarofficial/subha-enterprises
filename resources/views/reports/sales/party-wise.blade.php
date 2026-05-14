@extends('layouts.app')
@section('title', 'Party-wise Sales Report')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
<style>
.rpt-table th  { background:#1a3c5e; color:#fff; padding:6px 8px; font-size:0.80rem; white-space:nowrap; }
.rpt-table td  { padding:5px 8px; font-size:0.80rem; vertical-align:middle; }
.rpt-table .cust-hdr   td { background:#e8f0fe; font-weight:700; color:#1a3c5e; }
.rpt-table .cust-total td { background:#fff3cd; font-weight:600; }
.rpt-table .grand-total td { background:#1a3c5e; color:#fff; font-weight:700; }
.select2-container--open { z-index:9999; }

@media print {
    .no-print, nav, .footer, .alert { display:none !important; }
    .card { box-shadow:none !important; border:1px solid #ccc !important; }
    body { background:#fff !important; font-size:11px; }
    .rpt-table th,
    .rpt-table .cust-hdr td,
    .rpt-table .cust-total td,
    .rpt-table .grand-total td { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
}
</style>
@endpush

@section('content')

<div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <h5 class="mb-0"><i class="bi bi-people me-1 text-primary"></i> Party-wise Sales Report</h5>
</div>

{{-- Filter --}}
<div class="card mb-3 no-print">
    <div class="card-header py-2">Filter</div>
    <div class="card-body py-2">
        @if($error)
            <div class="alert alert-danger py-1 mb-2"><i class="bi bi-exclamation-triangle me-1"></i>{{ $error }}</div>
        @endif
        <form method="GET" action="{{ route('reports.sales.party-wise') }}" id="filterForm">
            <div class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label">Date From</label>
                    <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control form-control-sm" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Date To</label>
                    <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control form-control-sm" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Party</label>
                    <select name="party_code" class="form-select form-select-sm" id="partySelect">
                        <option value="">All Customers</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->party_code }}" {{ $partyCode == $c->party_code ? 'selected' : '' }}>
                                {{ $c->party_name }} — {{ $c->place }}
                            </option>
                        @endforeach
                    </select>
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
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" name="show" value="1" class="btn btn-primary btn-sm">
                        <i class="bi bi-search me-1"></i>Show
                    </button>
                    @if($showReport && !empty($partyGroups))
                    <button type="button" onclick="window.print()" class="btn btn-secondary btn-sm">
                        <i class="bi bi-printer me-1"></i>Print
                    </button>
                    <a href="{{ route('reports.sales.party-wise.export') }}?{{ http_build_query(request()->query()) }}"
                       class="btn btn-success btn-sm">
                        <i class="bi bi-file-earmark-excel me-1"></i>Excel
                    </a>
                    @endif
                </div>
            </div>
        </form>
    </div>
</div>

@if($showReport)
{{-- Print header --}}
<div class="d-none d-print-block text-center mb-3">
    <h4 class="mb-0">Party-wise Sales Report</h4>
    <div style="font-size:0.85rem;">
        Period: {{ \Carbon\Carbon::parse($dateFrom)->format('d-M-Y') }} to {{ \Carbon\Carbon::parse($dateTo)->format('d-M-Y') }}
    </div>
</div>

<div class="card">
    <div class="card-header py-2 d-flex justify-content-between no-print">
        <span><i class="bi bi-table me-1"></i>Party-wise Sales</span>
        <span class="text-muted" style="font-size:0.80rem;">
            {{ \Carbon\Carbon::parse($dateFrom)->format('d-M-Y') }} — {{ \Carbon\Carbon::parse($dateTo)->format('d-M-Y') }}
        </span>
    </div>
    <div class="card-body p-0">
        @if(empty($partyGroups))
            <div class="text-center py-4 text-muted"><i class="bi bi-inbox me-1"></i>No records found for the selected criteria.</div>
        @else
        @php $grandGross = 0; $grandTax = 0; $grandNet = 0; @endphp
        <div class="table-responsive">
        <table class="table table-bordered rpt-table mb-0">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Invoice No</th>
                    <th>Items</th>
                    <th class="text-end">Gross</th>
                    <th class="text-end">Tax</th>
                    <th class="text-end">Net</th>
                </tr>
            </thead>
            <tbody>
            @foreach($partyGroups as $pCode => $invoices)
                @php
                    $first   = $invoices[0];
                    $cGross  = 0; $cTax = 0; $cNet = 0;
                @endphp
                {{-- Customer header row --}}
                <tr class="cust-hdr">
                    <td colspan="6">
                        <i class="bi bi-person-fill me-1"></i>
                        {{ $first->party_name }}
                        <span class="fw-normal ms-2 text-muted">{{ $first->place }}, {{ $first->state }}</span>
                    </td>
                </tr>
                @foreach($invoices as $inv)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($inv->inv_date)->format('d-M-Y') }}</td>
                    <td>{{ $inv->inv_no }}</td>
                    <td class="text-center">{{ $inv->item_count }}</td>
                    <td class="text-end">{{ indianFmt($inv->gross) }}</td>
                    <td class="text-end">{{ indianFmt($inv->tax_amount) }}</td>
                    <td class="text-end">{{ indianFmt($inv->nett) }}</td>
                </tr>
                @php $cGross += $inv->gross; $cTax += $inv->tax_amount; $cNet += $inv->nett; @endphp
                @endforeach
                {{-- Customer Total --}}
                <tr class="cust-total">
                    <td colspan="3" class="text-end pe-3">Customer Total</td>
                    <td class="text-end">{{ indianFmt($cGross) }}</td>
                    <td class="text-end">{{ indianFmt($cTax) }}</td>
                    <td class="text-end">{{ indianFmt($cNet) }}</td>
                </tr>
                @php $grandGross += $cGross; $grandTax += $cTax; $grandNet += $cNet; @endphp
            @endforeach
            </tbody>
            <tfoot>
                <tr class="grand-total">
                    <td colspan="3"><i class="bi bi-check2-all me-1"></i>Grand Total</td>
                    <td class="text-end">{{ indianFmt($grandGross) }}</td>
                    <td class="text-end">{{ indianFmt($grandTax) }}</td>
                    <td class="text-end">{{ indianFmt($grandNet) }}</td>
                </tr>
            </tfoot>
        </table>
        </div>
        @endif
    </div>
</div>
@endif

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(function () {
    $('#partySelect').select2({
        theme: 'bootstrap-5',
        placeholder: 'All Customers',
        allowClear: true,
        width: '100%',
    });
});
</script>
@endpush
