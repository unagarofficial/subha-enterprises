@extends('layouts.app')
@section('title', 'Product Master')

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
@endpush

@section('content')
<div class="d-flex align-items-center justify-content-between mb-3">
    <h5 class="mb-0"><i class="bi bi-gem me-2 text-primary"></i>Product Master</h5>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal">
        <i class="bi bi-plus-circle me-1"></i>Add Product
    </button>
</div>

{{-- Filter Bar --}}
<form method="GET" action="{{ route('masters.product.index') }}" class="card mb-3">
    <div class="card-body py-2 px-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label mb-1" style="font-size:0.78rem;">Category</label>
                <select name="cat_code" class="form-select form-select-sm">
                    <option value="">All Categories</option>
                    @foreach($categories as $c)
                        <option value="{{ $c->cat_code }}" {{ $catCode == $c->cat_code ? 'selected' : '' }}>
                            {{ $c->cat_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label mb-1" style="font-size:0.78rem;">Branch</label>
                <select name="br_code" class="form-select form-select-sm">
                    <option value="">All Branches</option>
                    @foreach($branches as $br)
                        <option value="{{ $br->br_code }}" {{ $brCode == $br->br_code ? 'selected' : '' }}>
                            {{ $br->br_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-funnel me-1"></i>Filter
                </button>
                <a href="{{ route('masters.product.index') }}" class="btn btn-sm btn-outline-secondary ms-1">
                    <i class="bi bi-x-circle me-1"></i>Clear
                </a>
            </div>
        </div>
    </div>
</form>

<div class="card">
    <div class="card-body p-2">
        <table id="dataTable" class="table table-striped table-hover align-middle w-100" style="font-size:0.81rem;">
            <thead>
                <tr>
                    <th>Mat Code</th>
                    <th>Material Name</th>
                    <th>Category</th>
                    <th>UOM</th>
                    <th class="text-end">Sale Rate</th>
                    <th class="text-end">Y-Rate</th>
                    <th class="text-end">B-Rate</th>
                    <th>Branch</th>
                    <th style="width:100px;" class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($products as $p)
                <tr>
                    <td><code>{{ $p->mat_code }}</code></td>
                    <td>{{ $p->mat_name }}</td>
                    <td><span class="badge bg-secondary">{{ $p->category->cat_name ?? '—' }}</span></td>
                    <td>{{ $p->uomUnit->uom_name ?? '—' }}</td>
                    <td class="text-end">{{ $p->sale_rate > 0 ? number_format($p->sale_rate, 2) : '—' }}</td>
                    <td class="text-end">{{ $p->y_rate > 0 ? number_format($p->y_rate, 2) : '—' }}</td>
                    <td class="text-end">{{ $p->b_rate > 0 ? number_format($p->b_rate, 2) : '—' }}</td>
                    <td>{{ $p->branch->br_name ?? '—' }}</td>
                    <td class="text-center">
                        <button class="btn btn-xs btn-outline-primary btn-edit me-1"
                            data-mat_code="{{ $p->mat_code }}"
                            data-cat_code="{{ $p->cat_code }}"
                            data-mat_name="{{ $p->mat_name }}"
                            data-uom="{{ $p->uom }}"
                            data-sale_rate="{{ $p->sale_rate }}"
                            data-y_rate="{{ $p->y_rate }}"
                            data-b_rate="{{ $p->b_rate }}"
                            data-br_code="{{ $p->br_code }}">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <form action="{{ route('masters.product.destroy', $p->mat_code) }}"
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

{{-- Add Modal --}}
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h6 class="modal-title"><i class="bi bi-plus-circle me-1"></i>Add Product</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('masters.product.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    @include('masters.product._form', ['product' => null, 'categories' => $categories, 'uoms' => \App\Models\Uom::all(), 'branches' => $branches])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4"><i class="bi bi-save me-1"></i>Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit Modal --}}
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h6 class="modal-title"><i class="bi bi-pencil me-1"></i>Edit Product — <span id="editTitle"></span></h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editForm" method="POST">
                @csrf @method('PUT')
                <div class="modal-body" id="editBody">
                    {{-- Filled by JS --}}
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning btn-sm px-4"><i class="bi bi-save me-1"></i>Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

@if($errors->any())
<script>
    document.addEventListener('DOMContentLoaded', function(){
        new bootstrap.Modal(document.getElementById('addModal')).show();
    });
</script>
@endif
@endsection

@push('scripts')
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script>
var categories = @json($categories->map(fn($c) => ['cat_code' => $c->cat_code, 'cat_name' => $c->cat_name]));
var uoms       = @json(\App\Models\Uom::orderBy('uom_name')->get()->map(fn($u) => ['uom_code' => $u->uom_code, 'uom_name' => $u->uom_name]));
var branches   = @json($branches->map(fn($b) => ['br_code' => $b->br_code, 'br_name' => $b->br_name]));
var sessionBr  = {{ session('br_code') }};

$(function() {
    $('#dataTable').DataTable({ pageLength: 25, order: [[0,'asc']], columnDefs:[{orderable:false,targets:[8]}] });

    $(document).on('click', '.btn-edit', function() {
        var d = $(this).data();
        $('#editTitle').text(d.mat_code);
        $('#editForm').attr('action', '/masters/product/' + d.mat_code);

        var catOpts = categories.map(c =>
            `<option value="${c.cat_code}" ${c.cat_code == d.cat_code ? 'selected' : ''}>${c.cat_name}</option>`
        ).join('');
        var uomOpts = uoms.map(u =>
            `<option value="${u.uom_code}" ${u.uom_code == d.uom ? 'selected' : ''}>${u.uom_name}</option>`
        ).join('');
        var brOpts = branches.map(b =>
            `<option value="${b.br_code}" ${b.br_code == d.br_code ? 'selected' : ''}>${b.br_name}</option>`
        ).join('');

        $('#editBody').html(`
            <div class="row g-3">
                <div class="col-md-12">
                    <label class="form-label">Material Code</label>
                    <input type="text" class="form-control" value="${d.mat_code}" disabled>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Category <span class="text-danger">*</span></label>
                    <select name="cat_code" class="form-select" required>${catOpts}</select>
                </div>
                <div class="col-md-5">
                    <label class="form-label">Material Name <span class="text-danger">*</span></label>
                    <input type="text" name="mat_name" class="form-control" value="${d.mat_name}" required maxlength="100">
                </div>
                <div class="col-md-3">
                    <label class="form-label">UOM <span class="text-danger">*</span></label>
                    <select name="uom" class="form-select" required>${uomOpts}</select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Sale Rate</label>
                    <input type="number" name="sale_rate" class="form-control" value="${d.sale_rate}" step="0.01" min="0">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Y-Rate (Yoshita)</label>
                    <input type="number" name="y_rate" class="form-control" value="${d.y_rate}" step="0.01" min="0">
                </div>
                <div class="col-md-3">
                    <label class="form-label">B-Rate (Bangalore)</label>
                    <input type="number" name="b_rate" class="form-control" value="${d.b_rate}" step="0.01" min="0">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Branch <span class="text-danger">*</span></label>
                    <select name="br_code" class="form-select" required>${brOpts}</select>
                </div>
            </div>`);

        new bootstrap.Modal(document.getElementById('editModal')).show();
    });

    $(document).on('submit', '.form-delete', function(e) {
        e.preventDefault();
        var name = $(this).closest('tr').find('td:nth-child(2)').text().trim();
        if (confirm('Delete product "' + name + '"?\nThis will fail if product has stock or transactions.')) {
            this.submit();
        }
    });
});
</script>
@endpush
