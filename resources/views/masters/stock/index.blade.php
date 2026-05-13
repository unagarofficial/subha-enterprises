@extends('layouts.app')
@section('title', 'Stock Opening Balance')

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
<style>
    .stock-total { background:#e8f4fd; font-weight:600; }
    .neg-stock { color:#dc3545; font-weight:600; }
</style>
@endpush

@section('content')
<div class="d-flex align-items-center justify-content-between mb-3">
    <h5 class="mb-0">
        <i class="bi bi-box-seam me-2 text-primary"></i>Stock Opening Balance
        @if($finYear)
            <span class="badge bg-success ms-2">F.Y. {{ $finYear->year_name }}</span>
        @endif
    </h5>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal">
        <i class="bi bi-plus-circle me-1"></i>Add Stock Entry
    </button>
</div>

{{-- Filter --}}
<form method="GET" action="{{ route('masters.stock.index') }}" class="card mb-3">
    <div class="card-body py-2 px-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label mb-1" style="font-size:0.78rem;">Branch</label>
                <select name="br_code" class="form-select form-select-sm">
                    @foreach($branches as $br)
                        <option value="{{ $br->br_code }}" {{ $brCode == $br->br_code ? 'selected' : '' }}>
                            {{ $br->br_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
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
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-outline-primary"><i class="bi bi-funnel me-1"></i>Filter</button>
                <a href="{{ route('masters.stock.index') }}" class="btn btn-sm btn-outline-secondary ms-1"><i class="bi bi-x-circle me-1"></i>Clear</a>
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
                    <th class="text-end">Opening Bal.</th>
                    <th class="text-end">Receipts</th>
                    <th class="text-end">Issues</th>
                    <th class="text-end">Closing Stock</th>
                    <th style="width:100px;" class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $totOb = 0; $totRcpts = 0; $totIssues = 0; $totCl = 0;
                @endphp
                @foreach($stocks as $s)
                @php
                    $rcpts   = $s->calc_rcpts   ?? 0;
                    $issues  = $s->calc_issues  ?? 0;
                    $closing = $s->calc_closing ?? $s->ob;
                    $totOb += $s->ob; $totRcpts += $rcpts;
                    $totIssues += $issues; $totCl += $closing;
                @endphp
                <tr>
                    <td><code>{{ $s->mat_code }}</code></td>
                    <td>{{ $s->product->mat_name ?? $s->mat_code }}</td>
                    <td><span class="badge bg-secondary">{{ $s->product->category->cat_name ?? '—' }}</span></td>
                    <td>{{ $s->product->uomUnit->uom_name ?? '—' }}</td>
                    <td class="text-end">{{ number_format($s->ob, 3) }}</td>
                    <td class="text-end text-success">{{ number_format($rcpts, 3) }}</td>
                    <td class="text-end text-danger">{{ number_format($issues, 3) }}</td>
                    <td class="text-end {{ $closing < 0 ? 'neg-stock' : 'fw-semibold' }}">
                        {{ number_format($closing, 3) }}
                        @if($closing < 0) <i class="bi bi-exclamation-triangle-fill text-danger ms-1"></i> @endif
                    </td>
                    <td class="text-center">
                        <button class="btn btn-xs btn-outline-primary btn-edit me-1"
                            data-id="{{ $s->id }}"
                            data-mat_code="{{ $s->mat_code }}"
                            data-mat_name="{{ $s->product->mat_name ?? $s->mat_code }}"
                            data-ob="{{ $s->ob }}">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <form action="{{ route('masters.stock.destroy', $s->id) }}"
                              method="POST" class="d-inline form-delete">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-xs btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
            @if($stocks->count() > 0)
            <tfoot>
                <tr class="stock-total">
                    <td colspan="4" class="text-end">TOTAL →</td>
                    <td class="text-end">{{ number_format($totOb, 3) }}</td>
                    <td class="text-end text-success">{{ number_format($totRcpts, 3) }}</td>
                    <td class="text-end text-danger">{{ number_format($totIssues, 3) }}</td>
                    <td class="text-end">{{ number_format($totCl, 3) }}</td>
                    <td></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>

{{-- Add Modal --}}
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h6 class="modal-title"><i class="bi bi-plus-circle me-1"></i>Add Stock Opening Balance</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('masters.stock.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Branch <span class="text-danger">*</span></label>
                        <select name="br_code" id="add_br_code" class="form-select" required>
                            @foreach($branches as $br)
                                <option value="{{ $br->br_code }}"
                                    {{ $brCode == $br->br_code ? 'selected' : '' }}>
                                    {{ $br->br_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Material <span class="text-danger">*</span></label>
                        <select name="mat_code" id="add_mat_code" class="form-select select2-product"
                                required style="width:100%;">
                            <option value="">— Search product... —</option>
                            @foreach($products as $p)
                                <option value="{{ $p->mat_code }}"
                                        data-cat="{{ $p->category->cat_name ?? '' }}"
                                        data-catcode="{{ $p->cat_code }}">
                                    [{{ $p->mat_code }}] {{ $p->mat_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Category <small class="text-muted">(auto-filled)</small></label>
                        <input type="text" id="add_cat_display" class="form-control" disabled
                               placeholder="Select a product first">
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Opening Balance (OB) <span class="text-danger">*</span></label>
                        <input type="number" name="ob" class="form-control @error('ob') is-invalid @enderror"
                               value="{{ old('ob', 0) }}" required min="0" step="0.001">
                        @error('ob')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
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
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h6 class="modal-title"><i class="bi bi-pencil me-1"></i>Edit Opening Balance</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editForm" method="POST">
                @csrf @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Material</label>
                        <input type="text" id="edit_mat_display" class="form-control" disabled>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Opening Balance (OB) <span class="text-danger">*</span></label>
                        <input type="number" name="ob" id="edit_ob" class="form-control"
                               required min="0" step="0.001">
                    </div>
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
<script>document.addEventListener('DOMContentLoaded',function(){ new bootstrap.Modal(document.getElementById('addModal')).show(); });</script>
@endif
@endsection

@push('scripts')
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(function() {
    $('#dataTable').DataTable({
        pageLength: 25,
        order: [[0,'asc']],
        columnDefs: [{ orderable: false, targets: [8] }],
        footerCallback: function() {} // keep tfoot visible
    });

    // Select2 for product search
    $('.select2-product').select2({
        theme: 'bootstrap-5',
        placeholder: 'Search by code or name...',
        allowClear: true,
        dropdownParent: $('#addModal')
    });

    // Auto-fill category when product selected
    $('#add_mat_code').on('change', function() {
        var opt = $(this).find(':selected');
        $('#add_cat_display').val(opt.data('cat') || '');
    });

    // Edit button
    $(document).on('click', '.btn-edit', function() {
        var d = $(this).data();
        $('#editForm').attr('action', '/masters/stock/' + d.id);
        $('#edit_mat_display').val('[' + d.mat_code + '] ' + d.mat_name);
        $('#edit_ob').val(parseFloat(d.ob).toFixed(3));
        new bootstrap.Modal(document.getElementById('editModal')).show();
    });

    // Delete confirm
    $(document).on('submit', '.form-delete', function(e) {
        e.preventDefault();
        var name = $(this).closest('tr').find('td:nth-child(2)').text().trim();
        if (confirm('Remove stock entry for "' + name + '"?')) this.submit();
    });
});
</script>
@endpush
