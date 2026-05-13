@extends('layouts.app')
@section('title', 'Design Master')

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
@endpush

@section('content')
<div class="d-flex align-items-center justify-content-between mb-3">
    <h5 class="mb-0"><i class="bi bi-palette me-2 text-primary"></i>Design Master</h5>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal">
        <i class="bi bi-plus-circle me-1"></i>Add Design
    </button>
</div>

{{-- Filter --}}
<form method="GET" action="{{ route('masters.design.index') }}" class="card mb-3">
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
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-outline-primary"><i class="bi bi-funnel me-1"></i>Filter</button>
                <a href="{{ route('masters.design.index') }}" class="btn btn-sm btn-outline-secondary ms-1"><i class="bi bi-x-circle me-1"></i>Clear</a>
            </div>
        </div>
    </div>
</form>

<div class="card">
    <div class="card-body p-2">
        <table id="dataTable" class="table table-striped table-hover align-middle w-100" style="font-size:0.81rem;">
            <thead>
                <tr>
                    <th>Design Code</th>
                    <th>Description</th>
                    <th>Category</th>
                    <th>UOM</th>
                    <th class="text-end">Rate</th>
                    <th class="text-end">Y-Rate</th>
                    <th class="text-end">B-Rate</th>
                    <th style="width:100px;" class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($designs as $d)
                <tr>
                    <td><code>{{ $d->design_code }}</code></td>
                    <td>{{ $d->design_desc }}</td>
                    <td><span class="badge bg-secondary">{{ $d->category->cat_name ?? '—' }}</span></td>
                    <td>{{ $d->uomUnit->uom_name ?? '—' }}</td>
                    <td class="text-end">{{ $d->rate > 0 ? number_format($d->rate, 2) : '—' }}</td>
                    <td class="text-end">{{ $d->y_rate > 0 ? number_format($d->y_rate, 2) : '—' }}</td>
                    <td class="text-end">{{ $d->b_rate > 0 ? number_format($d->b_rate, 2) : '—' }}</td>
                    <td class="text-center">
                        <button class="btn btn-xs btn-outline-primary btn-edit me-1"
                            data-id="{{ $d->id }}"
                            data-cat_code="{{ $d->cat_code }}"
                            data-design_code="{{ $d->design_code }}"
                            data-design_desc="{{ $d->design_desc }}"
                            data-uom="{{ $d->uom }}"
                            data-rate="{{ $d->rate }}"
                            data-y_rate="{{ $d->y_rate }}"
                            data-b_rate="{{ $d->b_rate }}">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <form action="{{ route('masters.design.destroy', $d->id) }}"
                              method="POST" class="d-inline form-delete">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-xs btn-outline-danger"><i class="bi bi-trash"></i></button>
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
                <h6 class="modal-title"><i class="bi bi-plus-circle me-1"></i>Add Design</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('masters.design.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Category <span class="text-danger">*</span></label>
                            <select name="cat_code" class="form-select @error('cat_code') is-invalid @enderror" required>
                                <option value="">— Select —</option>
                                @foreach($categories as $c)
                                    <option value="{{ $c->cat_code }}" {{ old('cat_code') == $c->cat_code ? 'selected' : '' }}>{{ $c->cat_name }}</option>
                                @endforeach
                            </select>
                            @error('cat_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Design Code <span class="text-danger">*</span></label>
                            <input type="text" name="design_code" class="form-control @error('design_code') is-invalid @enderror"
                                   value="{{ old('design_code') }}" required maxlength="50" style="text-transform:uppercase;">
                            @error('design_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">UOM <span class="text-danger">*</span></label>
                            <select name="uom" class="form-select @error('uom') is-invalid @enderror" required>
                                <option value="">— Select —</option>
                                @foreach(\App\Models\Uom::orderBy('uom_name')->get() as $u)
                                    <option value="{{ $u->uom_code }}" {{ old('uom') == $u->uom_code ? 'selected' : '' }}>{{ $u->uom_name }}</option>
                                @endforeach
                            </select>
                            @error('uom')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Description <span class="text-danger">*</span></label>
                            <input type="text" name="design_desc" class="form-control @error('design_desc') is-invalid @enderror"
                                   value="{{ old('design_desc') }}" required maxlength="255">
                            @error('design_desc')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Rate</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">₹</span>
                                <input type="number" name="rate" class="form-control" value="{{ old('rate', 0) }}" step="0.01" min="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Y-Rate <small>(Yoshita)</small></label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">₹</span>
                                <input type="number" name="y_rate" class="form-control" value="{{ old('y_rate', 0) }}" step="0.01" min="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">B-Rate <small>(Bangalore)</small></label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">₹</span>
                                <input type="number" name="b_rate" class="form-control" value="{{ old('b_rate', 0) }}" step="0.01" min="0">
                            </div>
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
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h6 class="modal-title"><i class="bi bi-pencil me-1"></i>Edit Design</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editForm" method="POST">
                @csrf @method('PUT')
                <div class="modal-body" id="editBody"></div>
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
<script>
var categories = @json($categories->map(fn($c) => ['cat_code' => $c->cat_code, 'cat_name' => $c->cat_name]));
var uoms       = @json(\App\Models\Uom::orderBy('uom_name')->get()->map(fn($u) => ['uom_code' => $u->uom_code, 'uom_name' => $u->uom_name]));

$(function() {
    $('#dataTable').DataTable({ pageLength: 25, order:[[0,'asc']], columnDefs:[{orderable:false,targets:[7]}] });

    $(document).on('click', '.btn-edit', function() {
        var d = $(this).data();
        $('#editForm').attr('action', '/masters/design/' + d.id);
        var catOpts = categories.map(c => `<option value="${c.cat_code}" ${c.cat_code==d.cat_code?'selected':''}>${c.cat_name}</option>`).join('');
        var uomOpts = uoms.map(u => `<option value="${u.uom_code}" ${u.uom_code==d.uom?'selected':''}>${u.uom_name}</option>`).join('');
        $('#editBody').html(`
            <div class="row g-3">
                <div class="col-md-4"><label class="form-label">Category <span class="text-danger">*</span></label>
                    <select name="cat_code" class="form-select" required>${catOpts}</select></div>
                <div class="col-md-4"><label class="form-label">Design Code <span class="text-danger">*</span></label>
                    <input type="text" name="design_code" class="form-control" value="${d.design_code}" required maxlength="50" style="text-transform:uppercase;"></div>
                <div class="col-md-4"><label class="form-label">UOM <span class="text-danger">*</span></label>
                    <select name="uom" class="form-select" required>${uomOpts}</select></div>
                <div class="col-md-12"><label class="form-label">Description <span class="text-danger">*</span></label>
                    <input type="text" name="design_desc" class="form-control" value="${d.design_desc}" required maxlength="255"></div>
                <div class="col-md-4"><label class="form-label">Rate</label>
                    <div class="input-group input-group-sm"><span class="input-group-text">₹</span>
                    <input type="number" name="rate" class="form-control" value="${d.rate}" step="0.01" min="0"></div></div>
                <div class="col-md-4"><label class="form-label">Y-Rate</label>
                    <div class="input-group input-group-sm"><span class="input-group-text">₹</span>
                    <input type="number" name="y_rate" class="form-control" value="${d.y_rate}" step="0.01" min="0"></div></div>
                <div class="col-md-4"><label class="form-label">B-Rate</label>
                    <div class="input-group input-group-sm"><span class="input-group-text">₹</span>
                    <input type="number" name="b_rate" class="form-control" value="${d.b_rate}" step="0.01" min="0"></div></div>
            </div>`);
        new bootstrap.Modal(document.getElementById('editModal')).show();
    });

    $(document).on('submit', '.form-delete', function(e) {
        e.preventDefault();
        if (confirm('Delete this design?')) this.submit();
    });
});
</script>
@endpush
