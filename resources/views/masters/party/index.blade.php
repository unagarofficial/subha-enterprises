@extends('layouts.app')
@section('title', 'Party Master')

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
<style>
    .badge-type-c { background:#0d6efd; }
    .badge-type-s { background:#fd7e14; }
    .nav-tabs .nav-link { font-size: 0.83rem; padding: 6px 16px; }
    .tab-count { font-size: 0.70rem; background: rgba(0,0,0,0.15); border-radius: 10px; padding: 1px 7px; margin-left: 4px; }
</style>
@endpush

@section('content')
<div class="d-flex align-items-center justify-content-between mb-3">
    <h5 class="mb-0"><i class="bi bi-people me-2 text-primary"></i>Party Master</h5>
    <div>
        <a href="{{ route('masters.party.create', ['type' => 'C']) }}" class="btn btn-primary btn-sm me-1">
            <i class="bi bi-person-plus me-1"></i>Add Customer
        </a>
        <a href="{{ route('masters.party.create', ['type' => 'S']) }}" class="btn btn-warning btn-sm">
            <i class="bi bi-shop me-1"></i>Add Supplier
        </a>
    </div>
</div>

{{-- Type Tabs --}}
<ul class="nav nav-tabs mb-0" id="partyTabs">
    <li class="nav-item">
        <a class="nav-link {{ $type == 'ALL' ? 'active' : '' }}"
           href="{{ route('masters.party.index') }}">
            <i class="bi bi-list-ul me-1"></i>All
            <span class="tab-count">{{ $parties->count() }}</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link text-primary {{ $type == 'C' ? 'active' : '' }}"
           href="{{ route('masters.party.index', ['type' => 'C']) }}">
            <i class="bi bi-person me-1"></i>Customers
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link text-warning {{ $type == 'S' ? 'active' : '' }}"
           href="{{ route('masters.party.index', ['type' => 'S']) }}">
            <i class="bi bi-shop me-1"></i>Suppliers
        </a>
    </li>
</ul>

<div class="card" style="border-top-left-radius:0;">
    <div class="card-body p-2">
        <table id="dataTable" class="table table-striped table-hover align-middle w-100">
            <thead>
                <tr>
                    <th style="width:75px;">Code</th>
                    <th>Party Name</th>
                    <th>Place</th>
                    <th>State</th>
                    <th style="width:105px;">Mobile</th>
                    <th style="width:60px;" class="text-center">Type</th>
                    <th style="width:80px;" class="text-center">GST</th>
                    <th>GST/TIN No.</th>
                    <th style="width:110px;" class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($parties as $p)
                <tr>
                    <td>{{ $p->party_code }}</td>
                    <td>
                        <span class="fw-semibold">{{ $p->party_name }}</span>
                        @if($p->inout_state)
                            <span class="badge bg-secondary ms-1" style="font-size:0.68rem;">Out-State</span>
                        @endif
                    </td>
                    <td>{{ $p->place }}</td>
                    <td>{{ $p->state }}</td>
                    <td>{{ $p->mobile ?: ($p->phone ?: '—') }}</td>
                    <td class="text-center">
                        @if($p->party_type == 'C')
                            <span class="badge badge-type-c">Cust.</span>
                        @else
                            <span class="badge badge-type-s">Supp.</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($p->tin_grn_flag)
                            <i class="bi bi-check-circle-fill text-success"></i>
                        @else
                            <i class="bi bi-dash text-muted"></i>
                        @endif
                    </td>
                    <td>{{ $p->tin_grn_no ?: '—' }}</td>
                    <td class="text-center">
                        <a href="{{ route('masters.party.edit', $p->party_code) }}"
                           class="btn btn-xs btn-outline-primary me-1">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form action="{{ route('masters.party.destroy', $p->party_code) }}"
                              method="POST" class="d-inline form-delete">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-xs btn-outline-danger">
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
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script>
$(function() {
    $('#dataTable').DataTable({
        pageLength: 25,
        order: [[1, 'asc']],
        columnDefs: [{ orderable: false, targets: [8] }],
        language: { search: 'Search:' }
    });

    $(document).on('submit', '.form-delete', function(e) {
        e.preventDefault();
        var name = $(this).closest('tr').find('td:nth-child(2)').text().trim();
        if (confirm('Delete party "' + name + '"?\nThis will fail if the party has any transactions.')) {
            this.submit();
        }
    });
});
</script>
@endpush
