@extends('layouts.app')
@section('title', 'Party Ledger')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
<style>
.rpt-table th  { background:#1a3c5e; color:#fff; padding:6px 8px; font-size:0.80rem; white-space:nowrap; }
.rpt-table td  { padding:5px 8px; font-size:0.80rem; vertical-align:middle; }
.rpt-table .ob-row   td { background:#e8f4fd; font-weight:600; }
.rpt-table .debit-row  td.amt { color:#dc3545; }
.rpt-table .credit-row td.amt { color:#198754; }
.rpt-table .closing-row td { background:#1a3c5e; color:#fff; font-weight:700; }
.balance-positive { color:#dc3545; }
.balance-negative { color:#198754; }
@media print {
    .no-print, nav, .footer, .alert { display:none !important; }
    .card { box-shadow:none !important; border:1px solid #ccc !important; }
    body { background:#fff !important; font-size:11px; }
    .rpt-table th  { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
    .rpt-table .ob-row  td { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
    .rpt-table .closing-row td { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
}
</style>
@endpush

@section('content')

<div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <h5 class="mb-0"><i class="bi bi-journal-text me-1 text-primary"></i> Party Ledger</h5>
</div>

{{-- Filter Card --}}
<div class="card mb-3 no-print">
    <div class="card-header py-2">Filter</div>
    <div class="card-body py-2">
        @if($error)
            <div class="alert alert-danger py-1 mb-2"><i class="bi bi-exclamation-triangle me-1"></i>{{ $error }}</div>
        @endif
        <form method="GET" action="{{ route('reports.ledger.party') }}" id="filterForm">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Party <span class="text-danger">*</span></label>
                    <select name="party_code" id="partySelect" class="form-select form-select-sm" required>
                        <option value="">-- Select Party --</option>
                        @foreach($parties as $p)
                            <option value="{{ $p->party_code }}" {{ $partyCode == $p->party_code ? 'selected' : '' }}>
                                {{ $p->party_name }} ({{ $p->party_type === 'C' ? 'Customer' : 'Supplier' }})
                            </option>
                        @endforeach
                    </select>
                </div>
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
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" name="show" value="1" class="btn btn-primary btn-sm">
                        <i class="bi bi-search me-1"></i>Show
                    </button>
                    @if($showReport && $party && empty($error))
                    <button type="button" onclick="window.print()" class="btn btn-secondary btn-sm">
                        <i class="bi bi-printer me-1"></i>Print
                    </button>
                    <a href="{{ route('reports.ledger.party.export') }}?{{ http_build_query(request()->query()) }}"
                       class="btn btn-success btn-sm">
                        <i class="bi bi-file-earmark-excel me-1"></i>Excel
                    </a>
                    @endif
                </div>
            </div>
        </form>
    </div>
</div>

@if($showReport && $party && empty($error))

@php
    $balance = $openingBalance;
    $sno     = 1;
    $totalCredit = 0;
    $totalDebit  = $openingBalance > 0 ? $openingBalance : 0;
    foreach ($ledgerRows as $row) {
        $totalCredit += $row->credit;
        $totalDebit  += $row->debit;
    }
    $closingBalance = $openingBalance;
    foreach ($ledgerRows as $r) { $closingBalance += $r->debit - $r->credit; }
@endphp

{{-- Print Header --}}
<div class="d-none d-print-block text-center mb-2">
    <h5 class="mb-0">Account Ledger Of {{ $party->party_name }}</h5>
    <div style="font-size:0.85rem;">
        From {{ \Carbon\Carbon::parse($dateFrom)->format('d-M-Y') }}
        to {{ \Carbon\Carbon::parse($dateTo)->format('d-M-Y') }}
    </div>
</div>

<div class="card">
    <div class="card-header py-2 d-flex justify-content-between align-items-center no-print">
        <span><i class="bi bi-journal-text me-1"></i>
            Account Ledger of <strong>{{ $party->party_name }}</strong>
        </span>
        <span class="text-muted" style="font-size:0.80rem;">
            {{ \Carbon\Carbon::parse($dateFrom)->format('d-M-Y') }} — {{ \Carbon\Carbon::parse($dateTo)->format('d-M-Y') }}
        </span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-bordered rpt-table mb-0">
            <thead>
                <tr>
                    <th style="width:45px;">S.No</th>
                    <th style="width:95px;">Date</th>
                    <th>Narration</th>
                    <th class="text-end" style="width:120px;">Credit</th>
                    <th class="text-end" style="width:120px;">Debit</th>
                    <th class="text-end" style="width:130px;">Balance</th>
                </tr>
            </thead>
            <tbody>
                {{-- Opening Balance Row --}}
                <tr class="ob-row">
                    <td></td>
                    <td>{{ \Carbon\Carbon::parse($dateFrom)->format('d-M-Y') }}</td>
                    <td><strong>Opening Balance</strong></td>
                    <td class="text-end"></td>
                    <td class="text-end amt">
                        @if($openingBalance != 0)
                            {{ indianFmt(abs($openingBalance)) }}
                        @endif
                    </td>
                    <td class="text-end fw-bold
                        {{ $openingBalance >= 0 ? 'balance-positive' : 'balance-negative' }}">
                        {{ indianFmt($openingBalance) }}
                        <small class="ms-1 fw-normal" style="font-size:0.70rem;">
                            {{ $openingBalance >= 0 ? 'Dr' : 'Cr' }}
                        </small>
                    </td>
                </tr>

                @foreach($ledgerRows as $row)
                @php
                    $balance += $row->debit - $row->credit;
                    $rowClass = $row->debit > 0 ? 'debit-row' : 'credit-row';
                @endphp
                <tr class="{{ $rowClass }}">
                    <td>{{ $sno++ }}</td>
                    <td>{{ \Carbon\Carbon::parse($row->txn_date)->format('d-M-Y') }}</td>
                    <td>{{ $row->narration }}</td>
                    <td class="text-end amt">
                        {{ $row->credit > 0 ? indianFmt($row->credit) : '' }}
                    </td>
                    <td class="text-end amt">
                        {{ $row->debit > 0 ? indianFmt($row->debit) : '' }}
                    </td>
                    <td class="text-end fw-semibold
                        {{ $balance >= 0 ? 'balance-positive' : 'balance-negative' }}">
                        {{ indianFmt($balance) }}
                        <small class="ms-1 fw-normal" style="font-size:0.70rem;">
                            {{ $balance >= 0 ? 'Dr' : 'Cr' }}
                        </small>
                    </td>
                </tr>
                @endforeach

                @if(empty($ledgerRows))
                <tr>
                    <td colspan="6" class="text-center text-muted py-3">
                        <i class="bi bi-inbox me-1"></i>No transactions in this period.
                    </td>
                </tr>
                @endif
            </tbody>
            <tfoot>
                {{-- Totals row --}}
                <tr style="background:#fff3cd; font-weight:600;">
                    <td colspan="3" class="text-end">Total</td>
                    <td class="text-end">{{ indianFmt($totalCredit) }}</td>
                    <td class="text-end">{{ indianFmt($totalDebit) }}</td>
                    <td></td>
                </tr>
                {{-- Closing Balance --}}
                <tr class="closing-row">
                    <td colspan="3">
                        <i class="bi bi-check2-circle me-1"></i>Closing Balance
                    </td>
                    <td colspan="2"></td>
                    <td class="text-end">
                        {{ indianFmt($closingBalance) }}
                        <small class="ms-1 fw-normal" style="font-size:0.70rem;">
                            {{ $closingBalance >= 0 ? 'Dr' : 'Cr' }}
                        </small>
                    </td>
                </tr>
            </tfoot>
        </table>
        </div>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function () {
    $('#partySelect').select2({
        theme: 'bootstrap-5',
        placeholder: '-- Search Party --',
        allowClear: true,
    });
});
</script>
@endpush
