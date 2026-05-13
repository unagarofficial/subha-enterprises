@extends('layouts.app')
@section('title', $saleLabel . ' Register')

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">
        <i class="bi bi-receipt me-1 text-primary"></i>
        {{ $saleLabel }} Register
    </h5>
    <a href="{{ route('transactions.sale.create', ['saleType' => $saleType]) }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg"></i> New Invoice
    </a>
</div>

{{-- Filter --}}
<form method="GET" action="{{ route('transactions.sale.index', ['saleType' => $saleType]) }}" class="card mb-3">
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
                    @foreach(['CASH','CREDIT','COVERING','PLATING'] as $bt)
                    <option value="{{ $bt }}" {{ $billType === $bt ? 'selected' : '' }}>{{ $bt }}</option>
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
                <a href="{{ route('transactions.sale.index', ['saleType' => $saleType]) }}" class="btn btn-secondary btn-sm ms-1">Reset</a>
            </div>
        </div>
    </div>
</form>

<div class="card">
    <div class="card-body p-2">
        <table id="tblSale" class="table table-striped table-hover table-sm mb-0 w-100">
            <thead>
                <tr>
                    <th>Inv No</th>
                    <th>Date</th>
                    <th>Customer</th>
                    <th class="text-end">Gross</th>
                    <th class="text-end">Tax Amt</th>
                    <th class="text-end">Net Amt</th>
                    <th class="text-center">Bill Type</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sales as $s)
                <tr>
                    <td class="fw-bold">{{ $s->inv_no }}</td>
                    <td>{{ \Carbon\Carbon::parse($s->inv_date)->format('d-M-Y') }}</td>
                    <td>{{ $s->party_name }} <small class="text-muted">{{ $s->party_place }}</small></td>
                    <td class="text-end">{{ number_format($s->gross, 2) }}</td>
                    <td class="text-end">{{ number_format($s->tax_amount, 2) }}</td>
                    <td class="text-end fw-bold">{{ number_format($s->nett, 2) }}</td>
                    <td class="text-center">
                        <span class="badge
                            @if($s->bill_type === 'CASH') bg-success
                            @elseif($s->bill_type === 'CREDIT') bg-primary
                            @elseif($s->bill_type === 'COVERING') bg-warning text-dark
                            @else bg-info text-dark
                            @endif">
                            {{ $s->bill_type }}
                        </span>
                    </td>
                    <td class="text-center">
                        @if($s->is_locked)
                            <span class="badge bg-danger"><i class="bi bi-lock-fill me-1"></i>Locked</span>
                        @else
                            <span class="badge bg-success"><i class="bi bi-unlock-fill me-1"></i>Open</span>
                        @endif
                    </td>
                    <td class="text-center text-nowrap">
                        {{-- Print --}}
                        <a href="{{ route('transactions.sale.print', [$saleType, $s->br_code, $s->inv_no]) }}"
                           target="_blank" class="btn btn-xs btn-secondary me-1" title="Print Bill">
                            <i class="bi bi-printer"></i>
                        </a>

                        {{-- Lock/Unlock (Admin only) --}}
                        @if(session('user_type') === 'ADMIN')
                        <form method="POST"
                              action="{{ route('transactions.sale.lock', [$saleType, $s->br_code, $s->inv_no]) }}"
                              class="d-inline">
                            @csrf
                            <button type="submit"
                                    class="btn btn-xs {{ $s->is_locked ? 'btn-warning' : 'btn-secondary' }} me-1"
                                    title="{{ $s->is_locked ? 'Unlock' : 'Lock' }}">
                                <i class="bi bi-{{ $s->is_locked ? 'unlock-fill' : 'lock-fill' }}"></i>
                            </button>
                        </form>
                        @endif

                        {{-- Edit --}}
                        @if(!$s->is_locked)
                        <a href="{{ route('transactions.sale.edit', [$saleType, $s->br_code, $s->inv_no]) }}"
                           class="btn btn-xs btn-warning me-1" title="Edit">
                            <i class="bi bi-pencil-fill"></i>
                        </a>
                        @endif

                        {{-- Delete --}}
                        @if(!$s->is_locked)
                        <form method="POST"
                              action="{{ route('transactions.sale.destroy', [$saleType, $s->br_code, $s->inv_no]) }}"
                              class="d-inline"
                              onsubmit="return confirm('Delete Invoice #{{ $s->inv_no }}? Stock will be reversed.')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-xs btn-danger" title="Delete">
                                <i class="bi bi-trash-fill"></i>
                            </button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="9" class="text-center text-muted py-3">No invoices found for selected filters.</td></tr>
                @endforelse
            </tbody>
            @if($sales->count() > 0)
            <tfoot>
                <tr class="table-primary fw-bold">
                    <td colspan="3">TOTAL ({{ $sales->count() }} invoices)</td>
                    <td class="text-end">{{ number_format($sales->sum('gross'), 2) }}</td>
                    <td class="text-end">{{ number_format($sales->sum('tax_amount'), 2) }}</td>
                    <td class="text-end">{{ number_format($sales->sum('nett'), 2) }}</td>
                    <td colspan="3"></td>
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
    $('#tblSale').DataTable({
        pageLength: 25,
        order: [[0, 'desc']],
        columnDefs: [{ orderable: false, targets: 8 }],
    });
});
</script>
@endpush
