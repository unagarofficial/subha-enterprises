@extends('layouts.app')
@section('title', 'Sale Return Register')

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">
        <i class="bi bi-arrow-return-left me-1 text-danger"></i> Sale Return Register
    </h5>
    <a href="{{ route('transactions.sale-return.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg"></i> New Return
    </a>
</div>

{{-- Filter --}}
<form method="GET" action="{{ route('transactions.sale-return.index') }}" class="card mb-3">
    <div class="card-body py-2 px-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label mb-1" style="font-size:0.78rem;">Date From</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $dateFrom }}">
            </div>
            <div class="col-md-2">
                <label class="form-label mb-1" style="font-size:0.78rem;">Date To</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $dateTo }}">
            </div>
            <div class="col-md-3">
                <label class="form-label mb-1" style="font-size:0.78rem;">Customer</label>
                <select name="party_code" class="form-select form-select-sm">
                    <option value="">-- All Customers --</option>
                    @foreach($customers as $c)
                    <option value="{{ $c->party_code }}" {{ $partyCode == $c->party_code ? 'selected' : '' }}>
                        {{ $c->party_name }}{{ $c->place ? ' — '.$c->place : '' }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label mb-1" style="font-size:0.78rem;">Bill Type</label>
                <select name="bill_type" class="form-select form-select-sm">
                    <option value="">-- All Types --</option>
                    @foreach(['COVERING','PLATING'] as $bt)
                    <option value="{{ $bt }}" {{ $billType === $bt ? 'selected' : '' }}>{{ $bt }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label mb-1" style="font-size:0.78rem;">Branch</label>
                <select name="br_code" class="form-select form-select-sm">
                    @foreach($branches as $b)
                    <option value="{{ $b->br_code }}" {{ $brCode == $b->br_code ? 'selected' : '' }}>{{ $b->br_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-auto">
                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search me-1"></i>Filter</button>
                <a href="{{ route('transactions.sale-return.index') }}" class="btn btn-secondary btn-sm ms-1">Reset</a>
            </div>
        </div>
    </div>
</form>

<div class="card">
    <div class="card-body p-2">
        <table id="tblSaleRtn" class="table table-striped table-hover table-sm mb-0 w-100">
            <thead>
                <tr>
                    <th>Rtn No</th>
                    <th>Date</th>
                    <th>Customer</th>
                    <th class="text-end">Gross</th>
                    <th class="text-end">Tax Amt</th>
                    <th class="text-end">Net Amt</th>
                    <th class="text-center">Bill Type</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($returns as $r)
                <tr>
                    <td class="fw-bold">{{ $r->inv_no }}</td>
                    <td>{{ \Carbon\Carbon::parse($r->inv_date)->format('d-M-Y') }}</td>
                    <td>{{ $r->party_name }} <small class="text-muted">{{ $r->party_place }}</small></td>
                    <td class="text-end">{{ number_format($r->gross, 2) }}</td>
                    <td class="text-end">{{ number_format($r->tax_amount, 2) }}</td>
                    <td class="text-end fw-bold">{{ number_format($r->nett, 2) }}</td>
                    <td class="text-center">
                        <span class="badge {{ $r->bill_type === 'COVERING' ? 'bg-warning text-dark' : 'bg-info text-dark' }}">
                            {{ $r->bill_type }}
                        </span>
                    </td>
                    <td class="text-center text-nowrap">
                        <a href="{{ route('transactions.sale-return.print', [$r->br_code, $r->inv_no]) }}"
                           target="_blank" class="btn btn-xs btn-secondary me-1" title="Print">
                            <i class="bi bi-printer"></i>
                        </a>
                        <a href="{{ route('transactions.sale-return.edit', [$r->br_code, $r->inv_no]) }}"
                           class="btn btn-xs btn-warning me-1" title="Edit">
                            <i class="bi bi-pencil-fill"></i>
                        </a>
                        <form method="POST"
                              action="{{ route('transactions.sale-return.destroy', [$r->br_code, $r->inv_no]) }}"
                              class="d-inline"
                              onsubmit="return confirm('Delete Return #{{ $r->inv_no }}? Stock will be reversed.')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-xs btn-danger" title="Delete">
                                <i class="bi bi-trash-fill"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center text-muted py-3">No returns found for selected filters.</td></tr>
                @endforelse
            </tbody>
            @if($returns->count() > 0)
            <tfoot>
                <tr class="table-danger fw-bold">
                    <td colspan="3">TOTAL ({{ $returns->count() }} returns)</td>
                    <td class="text-end">{{ number_format($returns->sum('gross'), 2) }}</td>
                    <td class="text-end">{{ number_format($returns->sum('tax_amount'), 2) }}</td>
                    <td class="text-end">{{ number_format($returns->sum('nett'), 2) }}</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script>
$(function () {
    $('#tblSaleRtn').DataTable({
        pageLength: 25,
        order: [[0, 'desc']],
        columnDefs: [{ orderable: false, targets: 7 }],
    });
});
</script>
@endpush
