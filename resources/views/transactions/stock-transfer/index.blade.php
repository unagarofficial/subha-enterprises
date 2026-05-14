@extends('layouts.app')
@section('title', 'Stock Transfer')

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
@endpush

@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-arrow-left-right me-1 text-primary"></i> Stock Transfer (Branch Issue)</h5>
    <a href="{{ route('transactions.stock-transfer.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-circle me-1"></i> New Transfer
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
        <form method="GET" action="{{ route('transactions.stock-transfer.index') }}" class="row g-2 align-items-end">
            <div class="col-auto">
                <label class="form-label">From</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $dateFrom }}">
            </div>
            <div class="col-auto">
                <label class="form-label">To</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $dateTo }}">
            </div>
            <div class="col-auto" style="min-width:160px">
                <label class="form-label">From Branch</label>
                <select name="br_code" class="form-select form-select-sm">
                    @foreach($branches as $b)
                    <option value="{{ $b->br_code }}" {{ $brCode == $b->br_code ? 'selected' : '' }}>{{ $b->br_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search me-1"></i>Filter</button>
                <a href="{{ route('transactions.stock-transfer.index') }}" class="btn btn-secondary btn-sm">Reset</a>
            </div>
        </form>
    </div>
</div>

{{-- Table --}}
<div class="card">
    <div class="card-body p-0">
        <table id="stTable" class="table table-striped table-hover mb-0">
            <thead>
                <tr>
                    <th>Issue No</th>
                    <th>Date</th>
                    <th>From Branch</th>
                    <th>To Branch</th>
                    <th class="text-center">Total Items</th>
                    <th class="text-center" style="width:120px">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transfers as $t)
                <tr>
                    <td>{{ $t->iss_no }}</td>
                    <td>{{ \Carbon\Carbon::parse($t->iss_date)->format('d-M-Y') }}</td>
                    <td>{{ $t->from_br_name }}</td>
                    <td>{{ $t->to_br_name }}</td>
                    <td class="text-center">{{ $t->total_items }}</td>
                    <td class="text-center">
                        <a href="{{ route('transactions.stock-transfer.print', $t->iss_no) }}"
                           target="_blank" class="btn btn-xs btn-info text-white me-1" title="Print">
                            <i class="bi bi-printer"></i>
                        </a>
                        <a href="{{ route('transactions.stock-transfer.edit', $t->iss_no) }}"
                           class="btn btn-xs btn-warning me-1" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form method="POST"
                              action="{{ route('transactions.stock-transfer.destroy', $t->iss_no) }}"
                              class="d-inline"
                              onsubmit="return confirm('Delete Transfer #{{ $t->iss_no }}? Stock will be reversed.')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-xs btn-danger" title="Delete">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
$('#stTable').DataTable({ pageLength: 25, order: [[0,'desc']], columnDefs: [{orderable:false, targets:5}] });
</script>
@endpush
