@extends('layouts.app')
@section('title', 'Self Purchase Report')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
<style>
.rpt-table th  { background:#1a3c5e; color:#fff; padding:6px 8px; font-size:0.80rem; white-space:nowrap; }
.rpt-table td  { padding:5px 8px; font-size:0.80rem; vertical-align:middle; }
.rpt-table .supplier-total td { background:#fff3cd; font-weight:600; }
.rpt-table .grand-total   td { background:#1a3c5e; color:#fff; font-weight:700; }
.supplier-header { background:#e8f5e9; font-weight:600; font-size:0.78rem; color:#1b5e20; padding:4px 8px; }
/* covering = rose tint rows, plating = blue tint rows for easy differentiation */
.rpt-table tr[data-cat-type="covering"] td { background:#fff8f8; }
.rpt-table tr[data-cat-type="plating"]  td { background:#f8f8ff; }
@media print {
    .no-print, nav, .footer, .alert { display:none !important; }
    .card { box-shadow:none !important; border:1px solid #ccc !important; }
    body { background:#fff !important; font-size:10px; }
    .rpt-table th, .rpt-table .supplier-total td, .rpt-table .grand-total td
        { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
}
</style>
@endpush

@section('content')

<div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <h5 class="mb-0"><i class="bi bi-person-workspace me-1 text-success"></i> Self Purchase Report</h5>
</div>

<div class="card mb-3 no-print">
    <div class="card-header py-2">Filter</div>
    <div class="card-body py-2">
        @if($error)
            <div class="alert alert-danger py-1 mb-2"><i class="bi bi-exclamation-triangle me-1"></i>{{ $error }}</div>
        @endif
        <form method="GET" action="{{ route('reports.purchase.self-purchase') }}" id="filterForm">
            <div class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label">Date From</label>
                    <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control form-control-sm" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Date To</label>
                    <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control form-control-sm" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Supplier (Artisan)</label>
                    <select name="party_code" class="form-select form-select-sm select2-supplier">
                        <option value="">All Artisans</option>
                        @foreach($suppliers as $s)
                            <option value="{{ $s->party_code }}" {{ $partyCode == $s->party_code ? 'selected' : '' }}>
                                {{ $s->party_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Type</label>
                    <select name="print_type" class="form-select form-select-sm">
                        <option value="all"      {{ $printType === 'all'      ? 'selected' : '' }}>All</option>
                        <option value="covering" {{ $printType === 'covering' ? 'selected' : '' }}>Covering</option>
                        <option value="plating"  {{ $printType === 'plating'  ? 'selected' : '' }}>Plating</option>
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
                <div class="col-md-2 d-flex gap-1 flex-wrap">
                    <button type="submit" name="show" value="1" class="btn btn-primary btn-sm">
                        <i class="bi bi-search me-1"></i>Show
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

@if($showReport && !empty($supplierGroups))
{{-- Print action buttons --}}
<div class="mb-2 d-flex gap-2 no-print">
    <button type="button" onclick="doPrint('covering')" class="btn btn-outline-danger btn-sm">
        <i class="bi bi-printer me-1"></i>Print Covering
    </button>
    <button type="button" onclick="doPrint('plating')" class="btn btn-outline-primary btn-sm">
        <i class="bi bi-printer me-1"></i>Print Plating
    </button>
    <button type="button" onclick="doPrint('all')" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-printer me-1"></i>Print Voucher
    </button>
    <a href="{{ route('reports.purchase.self-purchase.export') }}?{{ http_build_query(request()->query()) }}"
       class="btn btn-success btn-sm">
        <i class="bi bi-file-earmark-excel me-1"></i>Excel
    </a>
</div>
@endif

@if($showReport)
<div class="d-none d-print-block text-center mb-3">
    <h4 class="mb-0">Self Purchase Report</h4>
    <div style="font-size:0.85rem;">
        Period: {{ \Carbon\Carbon::parse($dateFrom)->format('d-M-Y') }} to {{ \Carbon\Carbon::parse($dateTo)->format('d-M-Y') }}
        @if($printType !== 'all') | Type: {{ ucfirst($printType) }} @endif
    </div>
</div>

<div class="card">
    <div class="card-header py-2 d-flex justify-content-between no-print">
        <span><i class="bi bi-table me-1"></i>Self Purchase</span>
        <span class="text-muted" style="font-size:0.80rem;">
            {{ \Carbon\Carbon::parse($dateFrom)->format('d-M-Y') }} — {{ \Carbon\Carbon::parse($dateTo)->format('d-M-Y') }}
        </span>
    </div>
    <div class="card-body p-0">
        @if(empty($supplierGroups))
            <div class="text-center py-4 text-muted"><i class="bi bi-inbox me-1"></i>No records found for the selected criteria.</div>
        @else
        @php $grandAmt = 0; $grandTax = 0; $grandNet = 0; @endphp
        <div class="table-responsive">
        <table class="table table-bordered rpt-table mb-0" id="selfPurchaseTable">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Inv No</th>
                    <th>Supplier</th>
                    <th>Place</th>
                    <th>Category</th>
                    <th>Item</th>
                    <th class="text-end">Qty</th>
                    <th>UOM</th>
                    <th class="text-end">Rate</th>
                    <th class="text-end">Amount</th>
                    <th class="text-end">Tax</th>
                    <th class="text-end">Net</th>
                </tr>
            </thead>
            <tbody>
            @foreach($supplierGroups as $pCode => $items)
                @php
                    $first = $items[0];
                    $sAmt = 0; $sTax = 0; $sNet = 0;
                @endphp
                <tr class="supplier-hdr-row">
                    <td colspan="12" class="supplier-header">
                        <i class="bi bi-person-fill me-1"></i>{{ $first->party_name }}
                        @if($first->place) — {{ $first->place }} @endif
                    </td>
                </tr>
                @foreach($items as $item)
                @php
                    $catType = str_contains(strtolower($item->cat_name), 'covering') ? 'covering'
                             : (str_contains(strtolower($item->cat_name), 'plating') ? 'plating' : 'other');
                @endphp
                <tr data-cat-type="{{ $catType }}">
                    <td>{{ \Carbon\Carbon::parse($item->inv_date)->format('d-M-Y') }}</td>
                    <td>{{ $item->inv_no }}</td>
                    <td>{{ $item->party_name }}</td>
                    <td>{{ $item->place }}</td>
                    <td>{{ $item->cat_name }}</td>
                    <td>{{ $item->mat_name }}</td>
                    <td class="text-end">{{ indianFmt($item->qty, 3) }}</td>
                    <td>{{ $item->uom_name }}</td>
                    <td class="text-end">{{ indianFmt($item->rate) }}</td>
                    <td class="text-end">{{ indianFmt($item->amount) }}</td>
                    <td class="text-end">{{ indianFmt($item->line_tax) }}</td>
                    <td class="text-end">{{ indianFmt($item->line_net) }}</td>
                </tr>
                @php
                    $sAmt += $item->amount; $sTax += $item->line_tax; $sNet += $item->line_net;
                @endphp
                @endforeach
                <tr class="supplier-total supplier-subtotal-row">
                    <td colspan="9"><i class="bi bi-person-check me-1"></i>Supplier Total — {{ $first->party_name }}</td>
                    <td class="text-end">{{ indianFmt($sAmt) }}</td>
                    <td class="text-end">{{ indianFmt($sTax) }}</td>
                    <td class="text-end">{{ indianFmt($sNet) }}</td>
                </tr>
                @php $grandAmt += $sAmt; $grandTax += $sTax; $grandNet += $sNet; @endphp
            @endforeach
            </tbody>
            <tfoot>
                <tr class="grand-total">
                    <td colspan="9"><i class="bi bi-check2-all me-1"></i>Grand Total</td>
                    <td class="text-end">{{ indianFmt($grandAmt) }}</td>
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
$(document).ready(function () {
    $('.select2-supplier').select2({
        theme: 'bootstrap-5',
        placeholder: 'All Artisans',
        allowClear: true,
        width: '100%',
    });
});

function doPrint(filterType) {
    const rows  = document.querySelectorAll('#selfPurchaseTable tbody tr[data-cat-type]');
    const hdrRows    = document.querySelectorAll('#selfPurchaseTable tbody .supplier-hdr-row');
    const totalRows  = document.querySelectorAll('#selfPurchaseTable tbody .supplier-subtotal-row');

    if (filterType === 'all') {
        rows.forEach(r => r.style.display = '');
        hdrRows.forEach(r => r.style.display = '');
        totalRows.forEach(r => r.style.display = '');
    } else {
        rows.forEach(r => {
            r.style.display = r.dataset.catType === filterType ? '' : 'none';
        });
        // Show supplier header/total only if it has at least one visible row
        document.querySelectorAll('#selfPurchaseTable tbody').forEach(tbody => {
            let prevHdr = null;
            tbody.querySelectorAll('tr').forEach(tr => {
                if (tr.classList.contains('supplier-hdr-row')) {
                    prevHdr = tr;
                } else if (tr.classList.contains('supplier-subtotal-row')) {
                    if (prevHdr) {
                        const hasVisible = Array.from(
                            tbody.querySelectorAll('tr[data-cat-type]')
                        ).some(r => r.style.display !== 'none' && r.previousVisibleHdr === prevHdr);
                        // Simpler: hide header/total if no visible data rows between them
                    }
                }
            });
        });
    }
    window.print();
    // Restore all rows
    rows.forEach(r => r.style.display = '');
    hdrRows.forEach(r => r.style.display = '');
    totalRows.forEach(r => r.style.display = '');
}
</script>
@endpush
