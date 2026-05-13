@extends('layouts.app')
@section('title', 'Purchase Register')

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-cart-plus me-1 text-primary"></i> Purchase Register</h5>
    <a href="{{ route('transactions.purchase.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg"></i> New Purchase
    </a>
</div>

{{-- Filter --}}
<form method="GET" action="{{ route('transactions.purchase.index') }}" class="card mb-3">
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
                <label class="form-label mb-1" style="font-size:0.78rem;">Supplier</label>
                <select name="party_code" class="form-select form-select-sm">
                    <option value="">-- All Suppliers --</option>
                    @foreach($suppliers as $s)
                    <option value="{{ $s->party_code }}" {{ $partyCode == $s->party_code ? 'selected' : '' }}>
                        {{ $s->party_name }} — {{ $s->place }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label mb-1" style="font-size:0.78rem;">Branch</label>
                <select name="br_code" class="form-select form-select-sm">
                    @foreach($branches as $b)
                    <option value="{{ $b->br_code }}" {{ $brCode == $b->br_code ? 'selected' : '' }}>
                        {{ $b->br_name }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-auto">
                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search me-1"></i>Filter</button>
                <a href="{{ route('transactions.purchase.index') }}" class="btn btn-secondary btn-sm ms-1">Reset</a>
            </div>
        </div>
    </div>
</form>

<div class="card">
    <div class="card-body p-2">
        <table id="tblPurchase" class="table table-striped table-hover table-sm mb-0 w-100">
            <thead>
                <tr>
                    <th>Inv No</th>
                    <th>Date</th>
                    <th>Supplier</th>
                    <th class="text-end">Gross</th>
                    <th class="text-end">Tax%</th>
                    <th class="text-end">Tax Amt</th>
                    <th class="text-end">Net Amt</th>
                    <th>Branch</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($purchases as $p)
                <tr>
                    <td class="fw-bold">{{ $p->inv_no }}</td>
                    <td>{{ \Carbon\Carbon::parse($p->inv_date)->format('d-M-Y') }}</td>
                    <td>{{ $p->party_name }} <small class="text-muted">{{ $p->party_place }}</small></td>
                    <td class="text-end">{{ number_format($p->gross, 2) }}</td>
                    <td class="text-end">{{ number_format($p->tax_rate, 2) }}%</td>
                    <td class="text-end">{{ number_format($p->tax_amount, 2) }}</td>
                    <td class="text-end fw-bold">{{ number_format($p->nett, 2) }}</td>
                    <td>{{ $p->br_name }}</td>
                    <td class="text-center">
                        <a href="{{ route('transactions.purchase.print', [$p->br_code, $p->inv_no]) }}"
                           target="_blank" class="btn btn-xs btn-secondary me-1" title="Print Invoice">
                            <i class="bi bi-printer"></i>
                        </a>
                        <a href="{{ route('transactions.purchase.edit', [$p->br_code, $p->inv_no]) }}"
                           class="btn btn-xs btn-warning me-1" title="Edit">
                            <i class="bi bi-pencil-fill"></i>
                        </a>
                        <form method="POST"
                              action="{{ route('transactions.purchase.destroy', [$p->br_code, $p->inv_no]) }}"
                              class="d-inline"
                              onsubmit="return confirm('Delete Purchase Invoice #{{ $p->inv_no }}? Stock will be reversed.')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-xs btn-danger" title="Delete">
                                <i class="bi bi-trash-fill"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="9" class="text-center text-muted py-3">No purchases found for selected filters.</td></tr>
                @endforelse
            </tbody>
            @if($purchases->count() > 0)
            <tfoot>
                <tr class="table-primary fw-bold">
                    <td colspan="3">TOTAL ({{ $purchases->count() }} invoices)</td>
                    <td class="text-end">{{ number_format($purchases->sum('gross'), 2) }}</td>
                    <td></td>
                    <td class="text-end">{{ number_format($purchases->sum('tax_amount'), 2) }}</td>
                    <td class="text-end">{{ number_format($purchases->sum('nett'), 2) }}</td>
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
    $('#tblPurchase').DataTable({
        pageLength: 25,
        order: [[0, 'desc']],
        columnDefs: [{ orderable: false, targets: 8 }],
    });
});
</script>
@endpush
