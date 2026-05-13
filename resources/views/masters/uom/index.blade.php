@extends('layouts.app')
@section('title', 'UOM Master')

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
@endpush

@section('content')
<div class="d-flex align-items-center justify-content-between mb-3">
    <h5 class="mb-0"><i class="bi bi-rulers me-2 text-primary"></i>Unit of Measurement (UOM)</h5>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal">
        <i class="bi bi-plus-circle me-1"></i>Add UOM
    </button>
</div>

<div class="card">
    <div class="card-body p-2">
        <table id="dataTable" class="table table-striped table-hover align-middle w-100">
            <thead>
                <tr>
                    <th style="width:100px;">Code</th>
                    <th>UOM Name</th>
                    <th style="width:120px;" class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($uoms as $u)
                <tr>
                    <td>{{ $u->uom_code }}</td>
                    <td>{{ $u->uom_name }}</td>
                    <td class="text-center">
                        <button class="btn btn-xs btn-outline-primary btn-edit me-1"
                            data-id="{{ $u->uom_code }}"
                            data-uom_name="{{ $u->uom_name }}">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <form action="{{ route('masters.uom.destroy', $u->uom_code) }}" method="POST" class="d-inline form-delete">
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
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h6 class="modal-title"><i class="bi bi-plus-circle me-1"></i>Add UOM</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('masters.uom.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <label class="form-label">UOM Name <span class="text-danger">*</span></label>
                    <input type="text" name="uom_name" class="form-control @error('uom_name') is-invalid @enderror"
                           value="{{ old('uom_name') }}" required maxlength="20"
                           placeholder="e.g. PCS, GMS" style="text-transform:uppercase;" autofocus>
                    @error('uom_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <small class="text-muted">Max 20 characters.</small>
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
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h6 class="modal-title"><i class="bi bi-pencil me-1"></i>Edit UOM</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editForm" method="POST">
                @csrf @method('PUT')
                <div class="modal-body">
                    <label class="form-label">UOM Name <span class="text-danger">*</span></label>
                    <input type="text" id="edit_uom_name" name="uom_name" class="form-control"
                           required maxlength="20" style="text-transform:uppercase;">
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
        $('#editForm').attr('action', '/masters/uom/' + d.id);
        $('#edit_uom_name').val(d.uom_name);
        new bootstrap.Modal(document.getElementById('editModal')).show();
    });

    $(document).on('submit', '.form-delete', function(e) {
        e.preventDefault();
        if (confirm('Delete this UOM? This action cannot be undone.')) {
            this.submit();
        }
    });
});
</script>
@endpush
