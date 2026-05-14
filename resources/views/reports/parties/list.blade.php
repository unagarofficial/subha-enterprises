@extends('layouts.app')
@section('title', 'Party List')

@push('styles')
<style>
.rpt-table th  { background:#1a3c5e; color:#fff; padding:6px 8px; font-size:0.80rem; white-space:nowrap; }
.rpt-table td  { padding:5px 8px; font-size:0.80rem; vertical-align:middle; }
.badge-c { background:#d1e7dd; color:#0a5235; font-size:0.70rem; padding:2px 6px; border-radius:10px; }
.badge-s { background:#cfe2ff; color:#084298; font-size:0.70rem; padding:2px 6px; border-radius:10px; }
@media print {
    .no-print, nav, .footer { display:none !important; }
    .card { box-shadow:none !important; border:1px solid #ccc !important; }
    body { background:#fff !important; font-size:11px; }
    .rpt-table th { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
}
</style>
@endpush

@section('content')

<div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <h5 class="mb-0"><i class="bi bi-people me-1 text-primary"></i> Party List</h5>
</div>

{{-- Filter Card --}}
<div class="card mb-3 no-print">
    <div class="card-header py-2">Filter</div>
    <div class="card-body py-2">
        <form method="GET" action="{{ route('reports.parties.list') }}" id="filterForm">
            <div class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label">Party Type</label>
                    <select name="party_type" class="form-select form-select-sm">
                        <option value=""  {{ $partyType === ''  ? 'selected' : '' }}>All</option>
                        <option value="C" {{ $partyType === 'C' ? 'selected' : '' }}>Customer</option>
                        <option value="S" {{ $partyType === 'S' ? 'selected' : '' }}>Supplier</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">State</label>
                    <select name="state" class="form-select form-select-sm">
                        <option value="">All States</option>
                        @foreach($states as $st)
                            <option value="{{ $st }}" {{ $state === $st ? 'selected' : '' }}>{{ $st }}</option>
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
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" name="show" value="1" class="btn btn-primary btn-sm">
                        <i class="bi bi-search me-1"></i>Show
                    </button>
                    @if($showReport && $parties->isNotEmpty())
                    <button type="button" onclick="window.print()" class="btn btn-secondary btn-sm">
                        <i class="bi bi-printer me-1"></i>Print
                    </button>
                    <a href="{{ route('reports.parties.list.export') }}?{{ http_build_query(request()->query()) }}"
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

{{-- Print Header --}}
<div class="d-none d-print-block text-center mb-2">
    <h5 class="mb-0">Party List</h5>
    <div style="font-size:0.85rem;">
        @if($partyType === 'C') Customers
        @elseif($partyType === 'S') Suppliers
        @else All Parties @endif
        @if($state) — {{ $state }} @endif
    </div>
</div>

<div class="card">
    <div class="card-header py-2 d-flex justify-content-between align-items-center no-print">
        <span><i class="bi bi-table me-1"></i>Party List</span>
        @if($parties->isNotEmpty())
        <span class="badge bg-secondary">{{ $parties->count() }} parties</span>
        @endif
    </div>
    <div class="card-body p-0">
        @if($parties->isEmpty())
            <div class="text-center py-4 text-muted"><i class="bi bi-inbox me-1"></i>No parties found.</div>
        @else
        <div class="table-responsive">
        <table class="table table-bordered rpt-table mb-0">
            <thead>
                <tr>
                    <th style="width:45px;">S.No</th>
                    <th>Party Code</th>
                    <th>Party Name</th>
                    <th>Address</th>
                    <th>Place</th>
                    <th>State</th>
                    <th>Mobile</th>
                    <th>GST No</th>
                    <th class="text-center">Type</th>
                </tr>
            </thead>
            <tbody>
            @foreach($parties as $i => $p)
            <tr>
                <td class="text-center">{{ $i + 1 }}</td>
                <td>{{ $p->party_code }}</td>
                <td>{{ $p->party_name }}</td>
                <td>{{ $p->address }}</td>
                <td>{{ $p->place }}</td>
                <td>{{ $p->state }}</td>
                <td>{{ $p->mobile }}</td>
                <td>{{ $p->tin_grn_no }}</td>
                <td class="text-center">
                    @if($p->party_type === 'C')
                        <span class="badge-c">Customer</span>
                    @else
                        <span class="badge-s">Supplier</span>
                    @endif
                </td>
            </tr>
            @endforeach
            </tbody>
            <tfoot>
                <tr style="background:#1a3c5e; color:#fff; font-weight:700;">
                    <td colspan="9">
                        <i class="bi bi-check2-all me-1"></i>Total: {{ $parties->count() }} parties
                    </td>
                </tr>
            </tfoot>
        </table>
        </div>
        @endif
    </div>
</div>
@endif

@endsection
