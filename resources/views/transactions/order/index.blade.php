@extends('layouts.app')
@section('title', $ordLabel . ' — List')

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
<style>
    .badge-open     { background: #198754; }
    .badge-locked   { background: #fd7e14; }
    .badge-conv     { background: #0d6efd; }
</style>
@endpush

@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">
        <i class="bi bi-clipboard-check me-1 text-primary"></i>
        {{ $ordLabel }}
        <span class="badge {{ $ordType == 1 ? 'bg-success' : 'bg-primary' }} ms-1">Type {{ $ordType }}</span>
    </h5>
    <a href="{{ route('transactions.order.create', ['ordType' => $ordType]) }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-circle me-1"></i> New Order
    </a>
</div>

{{-- Filter --}}
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('transactions.order.index', ['ordType' => $ordType]) }}" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label">From Date</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $dateFrom }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">To Date</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $dateTo }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Customer</label>
                <select name="party_code" class="form-select form-select-sm" id="filterParty">
                    <option value="">-- All Customers --</option>
                    @foreach($customers as $c)
                    <option value="{{ $c->party_code }}" {{ $partyCode == $c->party_code ? 'selected' : '' }}>
                        {{ $c->party_name }}{{ $c->place ? ' — '.$c->place : '' }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="" {{ $status === '' ? 'selected' : '' }}>All</option>
                    <option value="open"      {{ $status === 'open'      ? 'selected' : '' }}>Open</option>
                    <option value="locked"    {{ $status === 'locked'    ? 'selected' : '' }}>Locked</option>
                    <option value="converted" {{ $status === 'converted' ? 'selected' : '' }}>Converted</option>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search me-1"></i>Filter</button>
                <a href="{{ route('transactions.order.index', ['ordType' => $ordType]) }}" class="btn btn-secondary btn-sm ms-1">Reset</a>
            </div>
        </form>
    </div>
</div>

{{-- Table --}}
<div class="card">
    <div class="card-body p-0">
        <table id="ordTable" class="table table-sm table-striped mb-0">
            <thead>
                <tr>
                    <th>Ord No</th>
                    <th>Date</th>
                    <th>Customer</th>
                    <th>Place</th>
                    <th>Status</th>
                    <th>Sale Inv</th>
                    <th style="width:160px">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $o)
                @php
                    if ($o->inv_no)          $statusBadge = '<span class="badge badge-conv">CONVERTED</span>';
                    elseif ($o->is_locked)   $statusBadge = '<span class="badge badge-locked">LOCKED</span>';
                    else                     $statusBadge = '<span class="badge badge-open">OPEN</span>';
                    $canEdit = !$o->inv_no;
                    $canDel  = !$o->inv_no && !$o->is_locked;
                @endphp
                <tr>
                    <td class="fw-bold">{{ $o->ord_no }}</td>
                    <td>{{ \Carbon\Carbon::parse($o->ord_date)->format('d-M-Y') }}</td>
                    <td>{{ $o->party_name }}</td>
                    <td>{{ $o->party_place }}</td>
                    <td>{!! $statusBadge !!}</td>
                    <td>
                        @if($o->inv_no)
                            <a href="{{ route('transactions.sale.edit', [1, $o->br_code, $o->inv_no]) }}" class="text-primary small">#{{ $o->inv_no }}</a>
                        @else
                            <span class="text-muted small">—</span>
                        @endif
                    </td>
                    <td>
                        @if($canEdit)
                        <a href="{{ route('transactions.order.edit', [$ordType, $o->br_code, $o->ord_no]) }}"
                           class="btn btn-warning btn-xs" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </a>
                        @endif
                        <a href="{{ route('transactions.order.print', [$ordType, $o->br_code, $o->ord_no]) }}"
                           target="_blank" class="btn btn-info btn-xs text-white" title="Print Estimation">
                            <i class="bi bi-printer"></i>
                        </a>
                        @if($canDel)
                        <form method="POST"
                              action="{{ route('transactions.order.destroy', [$ordType, $o->br_code, $o->ord_no]) }}"
                              class="d-inline"
                              onsubmit="return confirm('Delete Order #{{ $o->ord_no }}?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-danger btn-xs" title="Delete"><i class="bi bi-trash"></i></button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center text-muted py-3">No orders found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-2 text-muted small">{{ count($orders) }} record(s) found.</div>

@endsection

@push('scripts')
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script>
$(function () {
    $('#ordTable').DataTable({ pageLength: 25, order: [[0, 'desc']] });

    $('#filterParty').select2 && $('#filterParty').select2({ theme: 'bootstrap-5', width: '100%' });
});
</script>
@endpush
