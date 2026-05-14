@extends('layouts.app')

@section('title', 'Financial Year Management')

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-calendar-range me-1"></i> Financial Year Management</h5>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal">
        <i class="bi bi-plus-lg"></i> Add Year
    </button>
</div>

<div class="card">
    <div class="card-body p-2">
        <table id="tblYears" class="table table-striped table-hover mb-0 w-100">
            <thead>
                <tr>
                    <th>Year Name</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th class="text-center">Active</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @php $brName = session('br_name'); @endphp
                @foreach($years as $y)
                <tr class="{{ $y->is_active ? 'table-success' : '' }}">
                    <td>
                        {{ $y->year_name }}
                        @if($y->is_active)
                        <span class="badge bg-success ms-1">Active</span>
                        @endif
                    </td>
                    <td>{{ \Carbon\Carbon::parse($y->start_date)->format('d-M-Y') }}</td>
                    <td>{{ \Carbon\Carbon::parse($y->end_date)->format('d-M-Y') }}</td>
                    <td class="text-center">
                        @if($y->is_active)
                            <i class="bi bi-check-circle-fill text-success fs-5"></i>
                        @else
                            <form method="POST" action="{{ route('utilities.financial-year.set-active', $y->id) }}"
                                  class="d-inline" onsubmit="return confirm('Set &quot;{{ $y->year_name }}&quot; as the active financial year?')">
                                @csrf
                                <button type="submit" class="btn btn-xs btn-outline-success">
                                    <i class="bi bi-toggle-off"></i> Set Active
                                </button>
                            </form>
                        @endif
                    </td>
                    <td class="text-center">
                        <form method="POST" action="{{ route('utilities.financial-year.copy-ob', $y->id) }}"
                              class="d-inline"
                              onsubmit="return confirm('Copy closing stock as opening balance for &quot;{{ $y->year_name }}&quot;? This will reset Receipts & Issues to 0 for branch {{ $brName }}.')">
                            @csrf
                            <button type="submit" class="btn btn-xs btn-info me-1" title="Copy Closing Stock as OB">
                                <i class="bi bi-arrow-repeat"></i> Copy OB
                            </button>
                        </form>
                        @if(!$y->is_active)
                        <form method="POST" action="{{ route('utilities.financial-year.destroy', $y->id) }}"
                              class="d-inline" onsubmit="return confirm('Delete financial year &quot;{{ $y->year_name }}&quot;?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-xs btn-danger">
                                <i class="bi bi-trash-fill"></i>
                            </button>
                        </form>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- ADD Modal --}}
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('utilities.financial-year.store') }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title"><i class="bi bi-plus-circle me-1"></i>Add Financial Year</h6>
                    <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label">Year Name <span class="text-danger">*</span></label>
                        <input type="text" name="year_name" class="form-control" maxlength="20"
                               placeholder="e.g. 2025-2026" required value="{{ old('year_name') }}">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Start Date <span class="text-danger">*</span></label>
                        <input type="date" name="start_date" class="form-control" required
                               value="{{ old('start_date') }}">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">End Date <span class="text-danger">*</span></label>
                        <input type="date" name="end_date" class="form-control" required
                               value="{{ old('end_date') }}">
                    </div>
                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1"
                               id="chkActive" {{ old('is_active') ? 'checked' : '' }}>
                        <label class="form-check-label" for="chkActive">
                            Set as Active Year <small class="text-muted">(will deactivate current active year)</small>
                        </label>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-save me-1"></i>Save</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="alert alert-info mt-3 py-2" style="font-size:0.82rem;">
    <i class="bi bi-info-circle me-1"></i>
    <strong>Copy OB:</strong> Copies the current closing stock as the opening balance for the branch
    <strong>{{ session('br_name') }}</strong>, and resets Receipts &amp; Issues to zero. Use this when starting a new financial year.
</div>
@endsection

@push('scripts')
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script>
$(function () {
    $('#tblYears').DataTable({ pageLength: 25, order: [[1, 'asc']], searching: false });

    @if($errors->any())
        $('#addModal').modal('show');
    @endif
});
</script>
@endpush
