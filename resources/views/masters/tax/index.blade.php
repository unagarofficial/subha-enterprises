@extends('layouts.app')
@section('title', 'Tax Master')

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
@endpush

@section('content')
<div class="d-flex align-items-center justify-content-between mb-3">
    <h5 class="mb-0"><i class="bi bi-percent me-2 text-primary"></i>Tax Master</h5>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal">
        <i class="bi bi-plus-circle me-1"></i>Add Tax
    </button>
</div>

<div class="card">
    <div class="card-body p-2">
        <table id="dataTable" class="table table-striped table-hover align-middle w-100">
            <thead>
                <tr>
                    <th style="width:80px;">Code</th>
                    <th>Tax Name</th>
                    <th style="width:130px;" class="text-end">Tax %</th>
                    <th style="width:120px;" class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($taxes as $t)
                <tr>
                    <td>{{ $t->tax_code }}</td>
                    <td>{{ $t->tax_name }}</td>
                    <td class="text-end">
                        <span class="badge bg-info text-dark">{{ number_format($t->tax_percent, 2) }}%</span>
                    </td>
                    <td class="text-center">
                        <button class="btn btn-xs btn-outline-primary btn-edit me-1"
                            data-id="{{ $t->tax_code }}"
                            data-tax_name="{{ $t->tax_name }}"
                            data-tax_percent="{{ $t->tax_percent }}">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <form action="{{ route('masters.tax.destroy', $t->tax_code) }}" method="POST" class="d-inline form-delete">
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
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h6 class="modal-title"><i class="bi bi-plus-circle me-1"></i>Add Tax</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('masters.tax.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Tax Name <span class="text-danger">*</span></label>
                        <input type="text" name="tax_name" class="form-control @error('tax_name') is-invalid @enderror"
                               value="{{ old('tax_name') }}" required maxlength="100"
                               placeholder="e.g. GST 3%" autofocus>
                        @error('tax_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Tax Percent <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" name="tax_percent" class="form-control @error('tax_percent') is-invalid @enderror"
                                   value="{{ old('tax_percent') }}" required
                                   min="0" max="100" step="0.01" placeholder="0.00">
                            <span class="input-group-text">%</span>
                            @error('tax_percent')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
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
                <h6 class="modal-title"><i class="bi bi-pencil me-1"></i>Edit Tax</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editForm" method="POST">
                @csrf @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Tax Name <span class="text-danger">*</span></label>
                        <input type="text" id="edit_tax_name" name="tax_name" class="form-control"
                               required maxlength="100">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Tax Percent <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" id="edit_tax_percent" name="tax_percent" class="form-control"
                                   required min="0" max="100" step="0.01">
                            <span class="input-group-text">%</span>
                        </div>
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
$(function() {
    $('#dataTable').DataTable({ pageLength: 25 });

    $(document).on('click', '.btn-edit', function() {
        var d = $(this).data();
        $('#editForm').attr('action', '/masters/tax/' + d.id);
        $('#edit_tax_name').val(d.tax_name);
        $('#edit_tax_percent').val(d.tax_percent);
        new bootstrap.Modal(document.getElementById('editModal')).show();
    });

    $(document).on('submit', '.form-delete', function(e) {
        e.preventDefault();
        if (confirm('Delete this tax? This action cannot be undone.')) {
            this.submit();
        }
    });
});
</script>
@endpush
