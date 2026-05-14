@extends('layouts.app')
@section('title', 'Weekly Purchase Report')

@push('styles')
<style>
.rpt-table th  { background:#1a3c5e; color:#fff; padding:6px 8px; font-size:0.80rem; white-space:nowrap; }
.rpt-table td  { padding:5px 8px; font-size:0.80rem; vertical-align:middle; }
.rpt-table .week-total td { background:#1a3c5e; color:#fff; font-weight:700; }
.day-badge { font-size:0.72rem; background:#e8f0fe; color:#1a3c5e; padding:2px 7px; border-radius:10px; }
@media print {
    .no-print, nav, .footer, .alert { display:none !important; }
    .card { box-shadow:none !important; border:1px solid #ccc !important; }
    body { background:#fff !important; font-size:11px; }
    .rpt-table th, .rpt-table .week-total td { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
}
</style>
@endpush

@section('content')

<div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <h5 class="mb-0"><i class="bi bi-calendar3-week me-1 text-primary"></i> Weekly Purchase Report</h5>
</div>

<div class="card mb-3 no-print">
    <div class="card-header py-2">Filter</div>
    <div class="card-body py-2">
        @if($error)
            <div class="alert alert-danger py-1 mb-2"><i class="bi bi-exclamation-triangle me-1"></i>{{ $error }}</div>
        @endif
        <form method="GET" action="{{ route('reports.purchase.weekly') }}">
            <div class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label">Week Start</label>
                    <input type="date" name="week_from" value="{{ $weekFrom }}" class="form-control form-control-sm"
                           id="weekFrom" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Week End</label>
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
                    @if($showReport && $weekRows->isNotEmpty())
                    <button type="button" onclick="window.print()" class="btn btn-secondary btn-sm">
                        <i class="bi bi-printer me-1"></i>Print
                    </button>
                    <a href="{{ route('reports.purchase.weekly.export') }}?{{ http_build_query(request()->query()) }}"
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
    <h4 class="mb-0">Weekly Purchase Report</h4>
    <div style="font-size:0.85rem;">
        Week: {{ \Carbon\Carbon::parse($weekFrom)->format('d-M-Y') }} to {{ \Carbon\Carbon::parse($weekTo)->format('d-M-Y') }}
    </div>
</div>

<div class="card">
    <div class="card-header py-2 d-flex justify-content-between no-print">
        <span><i class="bi bi-table me-1"></i>Weekly Purchase Summary</span>
        <span class="text-muted" style="font-size:0.80rem;">
            {{ \Carbon\Carbon::parse($weekFrom)->format('d-M-Y') }} — {{ \Carbon\Carbon::parse($weekTo)->format('d-M-Y') }}
        </span>
    </div>
    <div class="card-body p-0">
        @if($weekRows->isEmpty())
            <div class="text-center py-4 text-muted"><i class="bi bi-inbox me-1"></i>No records found for the selected week.</div>
        @else
        @php $tInv = 0; $tGross = 0; $tTax = 0; $tNet = 0; @endphp
        <div class="table-responsive">
        <table class="table table-bordered rpt-table mb-0">
            <thead>
                <tr>
                    <th>Day</th>
                    <th>Date</th>
                    <th class="text-center">Invoices</th>
                    <th class="text-end">Gross</th>
                    <th class="text-end">Tax Amt</th>
                    <th class="text-end">Net</th>
                </tr>
            </thead>
            <tbody>
            @foreach($weekRows as $row)
            @php
                $day = \Carbon\Carbon::parse($row->inv_date);
                $tInv += $row->invoice_count; $tGross += $row->total_gross;
                $tTax += $row->total_tax;     $tNet   += $row->total_nett;
            @endphp
            <tr>
                <td><span class="day-badge">{{ $day->format('l') }}</span></td>
                <td>{{ $day->format('d-M-Y') }}</td>
                <td class="text-center">{{ $row->invoice_count }}</td>
                <td class="text-end">{{ indianFmt($row->total_gross) }}</td>
                <td class="text-end">{{ indianFmt($row->total_tax) }}</td>
                <td class="text-end">{{ indianFmt($row->total_nett) }}</td>
            </tr>
            @endforeach
            </tbody>
            <tfoot>
                <tr class="week-total">
                    <td colspan="2"><i class="bi bi-check2-all me-1"></i>Week Total</td>
                    <td class="text-center">{{ $tInv }}</td>
                    <td class="text-end">{{ indianFmt($tGross) }}</td>
                    <td class="text-end">{{ indianFmt($tTax) }}</td>
                    <td class="text-end">{{ indianFmt($tNet) }}</td>
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
