@extends('layouts.app')
@section('title', 'Customer-wise Weekly Sale')

@push('styles')
<style>
.rpt-table th  { background:#1a3c5e; color:#fff; padding:6px 8px; font-size:0.78rem; white-space:nowrap; }
.rpt-table td  { padding:5px 7px; font-size:0.78rem; vertical-align:middle; }
.rpt-table .col-total td { background:#1a3c5e; color:#fff; font-weight:700; }
.rpt-table td.text-end { font-variant-numeric:tabular-nums; }
.cust-name { max-width:170px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }

@media print {
    .no-print, nav, .footer, .alert { display:none !important; }
    .card { box-shadow:none !important; border:1px solid #ccc !important; }
    body { background:#fff !important; font-size:10px; }
    .rpt-table th, .rpt-table .col-total td { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
}
</style>
@endpush

@section('content')

<div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <h5 class="mb-0"><i class="bi bi-grid-3x3-gap me-1 text-primary"></i> Customer-wise Weekly Sale</h5>
</div>

{{-- Filter --}}
<div class="card mb-3 no-print">
    <div class="card-header py-2">Filter</div>
    <div class="card-body py-2">
        @if($error)
            <div class="alert alert-danger py-1 mb-2"><i class="bi bi-exclamation-triangle me-1"></i>{{ $error }}</div>
        @endif
        <form method="GET" action="{{ route('reports.sales.customer-weekly') }}" id="filterForm">
            <div class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label">Week From</label>
                    <input type="date" name="week_from" value="{{ $weekFrom }}" class="form-control form-control-sm"
                           id="weekFrom" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Week To</label>
                    <input type="date" name="week_to" value="{{ $weekTo }}" class="form-control form-control-sm"
                           id="weekTo" required>
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
                    @if($showReport && !empty($pivotData))
                    <button type="button" onclick="window.print()" class="btn btn-secondary btn-sm">
                        <i class="bi bi-printer me-1"></i>Print
                    </button>
                    <a href="{{ route('reports.sales.customer-weekly.export') }}?{{ http_build_query(request()->query()) }}"
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
    <h4 class="mb-0">Customer-wise Weekly Sale</h4>
    <div style="font-size:0.85rem;">
        Week: {{ \Carbon\Carbon::parse($weekFrom)->format('d-M-Y') }} to {{ \Carbon\Carbon::parse($weekTo)->format('d-M-Y') }}
    </div>
</div>

<div class="card">
    <div class="card-header py-2 d-flex justify-content-between no-print">
        <span><i class="bi bi-table me-1"></i>Customer-wise Week Pivot</span>
        <span class="text-muted" style="font-size:0.80rem;">
            {{ \Carbon\Carbon::parse($weekFrom)->format('d-M-Y') }} — {{ \Carbon\Carbon::parse($weekTo)->format('d-M-Y') }}
        </span>
    </div>
    <div class="card-body p-0">
        @if(empty($pivotData))
            <div class="text-center py-4 text-muted"><i class="bi bi-inbox me-1"></i>No records found for the selected week.</div>
        @else
        @php
            $grandTotal = 0;
            foreach ($colTotals as $v) { $grandTotal += $v; }
        @endphp
        <div class="table-responsive">
        <table class="table table-bordered rpt-table mb-0">
            <thead>
                <tr>
                    <th>Customer</th>
                    @foreach($dates as $d)
                    <th class="text-end">
                        {{ \Carbon\Carbon::parse($d)->format('D') }}<br>
                        <small>{{ \Carbon\Carbon::parse($d)->format('d/m') }}</small>
                    </th>
                    @endforeach
                    <th class="text-end">Total</th>
                </tr>
            </thead>
            <tbody>
            @foreach($pivotData as $cust)
            <tr>
                <td class="cust-name" title="{{ $cust['name'] }}">{{ $cust['name'] }}</td>
                @foreach($dates as $d)
                <td class="text-end">
                    @if(($cust['days'][$d] ?? 0) > 0)
                        {{ indianFmt($cust['days'][$d]) }}
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>
                @endforeach
                <td class="text-end fw-bold">{{ indianFmt($cust['total']) }}</td>
            </tr>
            @endforeach
            </tbody>
            <tfoot>
                <tr class="col-total">
                    <td><i class="bi bi-check2-all me-1"></i>Column Total</td>
                    @foreach($dates as $d)
                    <td class="text-end">{{ indianFmt($colTotals[$d] ?? 0) }}</td>
                    @endforeach
                    <td class="text-end">{{ indianFmt($grandTotal) }}</td>
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
<script>
document.getElementById('weekFrom')?.addEventListener('change', function () {
    const from = new Date(this.value);
    if (isNaN(from)) return;
    from.setDate(from.getDate() + 6);
    document.getElementById('weekTo').value = from.toISOString().split('T')[0];
});
</script>
@endpush
