@extends('layouts.app')
@section('title', 'Stock Closing Report')

@push('styles')
<style>
.rpt-table th  { background:#1a3c5e; color:#fff; padding:6px 8px; font-size:0.80rem; white-space:nowrap; }
.rpt-table td  { padding:5px 8px; font-size:0.80rem; vertical-align:middle; }
.rpt-table .cat-total   td { background:#fff3cd; font-weight:600; }
.rpt-table .grand-total td { background:#1a3c5e; color:#fff; font-weight:700; }
.cat-header { background:#e8f0fe; font-weight:600; font-size:0.78rem; color:#1a3c5e; padding:4px 8px; }
@media print {
    .no-print, nav, .footer, .alert { display:none !important; }
    .card { box-shadow:none !important; border:1px solid #ccc !important; }
    body { background:#fff !important; font-size:10px; }
    .rpt-table th, .rpt-table .cat-total td, .rpt-table .grand-total td
        { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
}
</style>
@endpush

@section('content')

<div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <h5 class="mb-0"><i class="bi bi-archive me-1 text-primary"></i> Stock Closing Report</h5>
</div>

<div class="card mb-3 no-print">
    <div class="card-header py-2">Filter</div>
    <div class="card-body py-2">
        <form method="GET" action="{{ route('reports.stock.closing') }}">
            <div class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label">As On Date</label>
                    <input type="date" name="as_on_date" value="{{ $asOnDate }}" class="form-control form-control-sm" required>
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
                <div class="col-md-2">
                    <label class="form-label">Category</label>
                    <select name="cat_code" class="form-select form-select-sm">
                        <option value="">All Categories</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->cat_code }}" {{ $catCode == $cat->cat_code ? 'selected' : '' }}>
                                {{ $cat->cat_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" name="show" value="1" class="btn btn-primary btn-sm">
                        <i class="bi bi-search me-1"></i>Show
                    </button>
                    @if($showReport && !empty($catGroups))
                    <button type="button" onclick="window.print()" class="btn btn-secondary btn-sm">
                        <i class="bi bi-printer me-1"></i>Print
                    </button>
                    <a href="{{ route('reports.stock.closing.export') }}?{{ http_build_query(request()->query()) }}"
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
<div class="d-none d-print-block text-center mb-3">
    <h4 class="mb-0">Stock Closing Report</h4>
    <div style="font-size:0.85rem;">As on {{ \Carbon\Carbon::parse($asOnDate)->format('d-M-Y') }}</div>
</div>

<div class="card">
    <div class="card-header py-2 d-flex justify-content-between no-print">
        <span><i class="bi bi-table me-1"></i>Stock Closing</span>
        <span class="text-muted" style="font-size:0.80rem;">
            As on {{ \Carbon\Carbon::parse($asOnDate)->format('d-M-Y') }}
        </span>
    </div>
    <div class="card-body p-0">
        @if(empty($catGroups))
            <div class="text-center py-4 text-muted"><i class="bi bi-inbox me-1"></i>No stock records found.</div>
        @else
        @php $grandOb = 0; $grandRcpts = 0; $grandIssues = 0; $grandCl = 0; @endphp
        <div class="table-responsive">
        <table class="table table-bordered rpt-table mb-0">
            <thead>
                <tr>
                    <th>Mat Code</th>
                    <th>Item Name</th>
                    <th>Category</th>
                    <th>UOM</th>
                    <th class="text-end">Opening</th>
                    <th class="text-end">Receipts</th>
                    <th class="text-end">Issues</th>
                    <th class="text-end">Closing</th>
                </tr>
            </thead>
            <tbody>
            @foreach($catGroups as $catName => $items)
                @php $cOb = 0; $cRcpts = 0; $cIssues = 0; $cCl = 0; @endphp
                <tr>
                    <td colspan="8" class="cat-header">
                        <i class="bi bi-tag-fill me-1"></i>{{ $catName }}
                    </td>
                </tr>
                @foreach($items as $item)
                <tr>
                    <td>{{ $item->mat_code }}</td>
                    <td>{{ $item->mat_name }}</td>
                    <td>{{ $catName }}</td>
                    <td>{{ $item->uom_name }}</td>
                    <td class="text-end">{{ indianFmt($item->ob, 3) }}</td>
                    <td class="text-end">{{ indianFmt($item->receipts, 3) }}</td>
                    <td class="text-end">{{ indianFmt($item->issues, 3) }}</td>
                    <td class="text-end fw-semibold">{{ indianFmt($item->closing, 3) }}</td>
                </tr>
                @php
                    $cOb += $item->ob; $cRcpts += $item->receipts;
                    $cIssues += $item->issues; $cCl += $item->closing;
                @endphp
                @endforeach
                <tr class="cat-total">
                    <td colspan="4"><i class="bi bi-tag me-1"></i>Category Total — {{ $catName }}</td>
                    <td class="text-end">{{ indianFmt($cOb, 3) }}</td>
                    <td class="text-end">{{ indianFmt($cRcpts, 3) }}</td>
                    <td class="text-end">{{ indianFmt($cIssues, 3) }}</td>
                    <td class="text-end fw-bold">{{ indianFmt($cCl, 3) }}</td>
                </tr>
                @php
                    $grandOb += $cOb; $grandRcpts += $cRcpts;
                    $grandIssues += $cIssues; $grandCl += $cCl;
                @endphp
            @endforeach
            </tbody>
            <tfoot>
                <tr class="grand-total">
                    <td colspan="4"><i class="bi bi-check2-all me-1"></i>Grand Total</td>
                    <td class="text-end">{{ indianFmt($grandOb, 3) }}</td>
                    <td class="text-end">{{ indianFmt($grandRcpts, 3) }}</td>
                    <td class="text-end">{{ indianFmt($grandIssues, 3) }}</td>
                    <td class="text-end">{{ indianFmt($grandCl, 3) }}</td>
                </tr>
            </tfoot>
        </table>
        </div>
        @endif
    </div>
</div>
@endif

@endsection
