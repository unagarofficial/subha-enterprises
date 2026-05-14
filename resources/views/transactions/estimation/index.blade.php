@extends('layouts.app')
@section('title', 'Estimation Invoice')

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
@endpush

@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-file-earmark-text me-1 text-primary"></i> Estimation Invoice</h5>
    <a href="{{ route('transactions.estimation.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-circle me-1"></i> New Estimation
    </a>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show py-2">
    {{ session('success') }}<button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
</div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show py-2">
    {{ session('error') }}<button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Filter --}}
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('transactions.estimation.index') }}" class="row g-2 align-items-end">
            <div class="col-auto">
                <label class="form-label">From</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $dateFrom }}">
            </div>
            <div class="col-auto">
                <label class="form-label">To</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $dateTo }}">
            </div>
            <div class="col-auto" style="min-width:200px">
                <label class="form-label">Party</label>
                <select name="party_code" class="form-select form-select-sm">
                    <option value="">All Parties</option>
                    @foreach($parties as $p)
                    <option value="{{ $p->party_code }}" {{ $partyCode == $p->party_code ? 'selected' : '' }}>
                        {{ $p->party_name }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto" style="min-width:160px">
                <label class="form-label">Branch</label>
                <select name="br_code" class="form-select form-select-sm">
                    @foreach($branches as $b)
                    <option value="{{ $b->br_code }}" {{ $brCode == $b->br_code ? 'selected' : '' }}>{{ $b->br_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search me-1"></i>Filter</button>
                <a href="{{ route('transactions.estimation.index') }}" class="btn btn-secondary btn-sm">Reset</a>
            </div>
        </form>
    </div>
</div>

{{-- Table --}}
<div class="card">
    <div class="card-body p-0">
        <table id="estTable" class="table table-striped table-hover mb-0">
            <thead>
                <tr>
                    <th>Inv No</th>
                    <th>Date</th>
                    <th>Party Name</th>
                    <th class="text-end">Gross</th>
                    <th class="text-end">Tax%</th>
                    <th class="text-end">Net</th>
                    <th class="text-center" style="width:130px">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($estimations as $e)
                <tr>
                    <td>{{ $e->inv_no }}</td>
                    <td>{{ \Carbon\Carbon::parse($e->inv_date)->format('d-M-Y') }}</td>
                    <td>{{ $e->party_name }} @if($e->party_place)<small class="text-muted">({{ $e->party_place }})</small>@endif</td>
                    <td class="text-end">{{ number_format($e->gross, 2) }}</td>
                    <td class="text-end">{{ number_format($e->tax_rate, 2) }}%</td>
                    <td class="text-end fw-bold">{{ number_format($e->nett, 2) }}</td>
                    <td class="text-center">
                        <a href="{{ route('transactions.estimation.print', [$e->br_code, $e->inv_no]) }}"
                           target="_blank" class="btn btn-xs btn-info text-white me-1" title="Print">
                            <i class="bi bi-printer"></i>
                        </a>
                        <a href="{{ route('transactions.estimation.edit', [$e->br_code, $e->inv_no]) }}"
                           class="btn btn-xs btn-warning me-1" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form method="POST"
                              action="{{ route('transactions.estimation.destroy', [$e->br_code, $e->inv_no]) }}"
                              class="d-inline"
                              onsubmit="return confirm('Delete Estimation Invoice #{{ $e->inv_no }}?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-xs btn-danger" title="Delete">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="fw-bold bg-light">
                    <td colspan="3" class="text-end">Total</td>
                    <td class="text-end">{{ number_format($estimations->sum('gross'), 2) }}</td>
                    <td></td>
                    <td class="text-end">{{ number_format($estimations->sum('nett'), 2) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
$('#estTable').DataTable({ pageLength: 25, order: [[0,'desc']], columnDefs: [{orderable:false, targets:6}] });
</script>
@endpush
