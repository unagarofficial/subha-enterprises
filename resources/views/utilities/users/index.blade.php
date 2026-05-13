@extends('layouts.app')

@section('title', 'User Management')

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-people-fill me-1"></i> User Management</h5>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal">
        <i class="bi bi-plus-lg"></i> Add User
    </button>
</div>

<div class="card">
    <div class="card-body p-2">
        <table id="tblUsers" class="table table-striped table-hover mb-0 w-100">
            <thead>
                <tr>
                    <th>User Code</th>
                    <th>User Name</th>
                    <th>Branch</th>
                    <th>User Type</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $u)
                <tr>
                    <td>{{ $u->id }}</td>
                    <td>{{ $u->user_name }}</td>
                    <td>{{ $u->branch->br_name ?? '-' }}</td>
                    <td>
                        <span class="badge {{ $u->user_type === 'ADMIN' ? 'bg-danger' : 'bg-secondary' }}">
                            {{ $u->user_type }}
                        </span>
                    </td>
                    <td class="text-center">
                        <button class="btn btn-xs btn-warning me-1 btn-edit"
                            data-id="{{ $u->id }}"
                            data-username="{{ $u->user_name }}"
                            data-brcode="{{ $u->br_code }}"
                            data-usertype="{{ $u->user_type }}">
                            <i class="bi bi-pencil-fill"></i> Edit
                        </button>
                        <button class="btn btn-xs btn-info me-1 btn-reset"
                            data-id="{{ $u->id }}"
                            data-username="{{ $u->user_name }}">
                            <i class="bi bi-key-fill"></i> Reset Pw
                        </button>
                        @if($u->id !== session('user_id'))
                        <form method="POST" action="{{ route('utilities.users.destroy', $u->id) }}"
                              class="d-inline" onsubmit="return confirm('Delete user {{ $u->user_name }}?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-xs btn-danger">
                                <i class="bi bi-trash-fill"></i> Delete
                            </button>
                        </form>
                        @else
                        <span class="text-muted small">(current user)</span>
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
        <form method="POST" action="{{ route('utilities.users.store') }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title"><i class="bi bi-person-plus me-1"></i>Add User</h6>
                    <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label">User Name <span class="text-danger">*</span></label>
                        <input type="text" name="user_name" class="form-control" maxlength="50" required
                               value="{{ old('user_name') }}">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" minlength="6" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
                        <input type="password" name="password_confirmation" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Branch <span class="text-danger">*</span></label>
                        <select name="br_code" class="form-select" required>
                            <option value="">-- Select Branch --</option>
                            @foreach($branches as $b)
                            <option value="{{ $b->br_code }}" {{ old('br_code') == $b->br_code ? 'selected' : '' }}>
                                {{ $b->br_name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">User Type <span class="text-danger">*</span></label>
                        <select name="user_type" class="form-select" required>
                            <option value="USER" {{ old('user_type') == 'USER' ? 'selected' : '' }}>USER</option>
                            <option value="ADMIN" {{ old('user_type') == 'ADMIN' ? 'selected' : '' }}>ADMIN</option>
                        </select>
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

{{-- EDIT Modal --}}
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" id="editForm">
            @csrf @method('PUT')
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title"><i class="bi bi-pencil me-1"></i>Edit User</h6>
                    <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label">User Name <span class="text-danger">*</span></label>
                        <input type="text" name="user_name" id="edit_user_name" class="form-control" maxlength="50" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">New Password <small class="text-muted">(blank = no change)</small></label>
                        <input type="password" name="password" id="edit_password" class="form-control" minlength="6">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="password_confirmation" class="form-control">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Branch <span class="text-danger">*</span></label>
                        <select name="br_code" id="edit_br_code" class="form-select" required>
                            @foreach($branches as $b)
                            <option value="{{ $b->br_code }}">{{ $b->br_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">User Type <span class="text-danger">*</span></label>
                        <select name="user_type" id="edit_user_type" class="form-select" required>
                            <option value="USER">USER</option>
                            <option value="ADMIN">ADMIN</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-save me-1"></i>Update</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- RESET PASSWORD Modal --}}
<div class="modal fade" id="resetModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <form method="POST" id="resetForm">
            @csrf
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title"><i class="bi bi-key me-1"></i>Reset Password</h6>
                    <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2 small">Resetting password for: <strong id="reset_username"></strong></p>
                    <div class="mb-2">
                        <label class="form-label">New Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" minlength="6" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
                        <input type="password" name="password_confirmation" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-key me-1"></i>Reset</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script>
$(function () {
    $('#tblUsers').DataTable({ pageLength: 25, order: [[1, 'asc']] });

    // Edit
    $(document).on('click', '.btn-edit', function () {
        const id   = $(this).data('id');
        $('#editForm').attr('action', '/utilities/users/' + id);
        $('#edit_user_name').val($(this).data('username'));
        $('#edit_br_code').val($(this).data('brcode'));
        $('#edit_user_type').val($(this).data('usertype'));
        $('#edit_password').val('');
        $('#editModal').modal('show');
    });

    // Reset Password
    $(document).on('click', '.btn-reset', function () {
        const id = $(this).data('id');
        $('#resetForm').attr('action', '/utilities/users/' + id + '/reset-password');
        $('#reset_username').text($(this).data('username'));
        $('#resetModal find [name=password]').val('');
        $('#resetModal').modal('show');
    });

    @if($errors->any())
        @if(old('_method') === 'PUT')
            $('#editModal').modal('show');
        @else
            $('#addModal').modal('show');
        @endif
    @endif
});
</script>
@endpush
