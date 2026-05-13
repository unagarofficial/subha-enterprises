@extends('layouts.app')

@section('title', 'System Parameters')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-sliders me-1"></i> System Parameters
            </div>
            <div class="card-body">
                @if($errors->any())
                <div class="alert alert-danger py-2">
                    <ul class="mb-0 ps-3">
                        @foreach($errors->all() as $e)
                        <li>{{ $e }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <form method="POST" action="{{ route('utilities.system-parameters.store') }}">
                    @csrf

                    <h6 class="text-muted border-bottom pb-1 mb-3">Admin Settings</h6>
                    <div class="row mb-3">
                        <div class="col-md-6 mb-2">
                            <label class="form-label">Admin Name</label>
                            <input type="text" name="admin_name" class="form-control" maxlength="50"
                                   value="{{ old('admin_name', $para->admin_name) }}">
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="form-label">Admin Password
                                @if($para->admin_pw) <small class="text-success">(set)</small> @endif
                            </label>
                            <input type="password" name="admin_pw" class="form-control"
                                   placeholder="Leave blank to keep current">
                        </div>
                    </div>

                    <h6 class="text-muted border-bottom pb-1 mb-3">User Settings</h6>
                    <div class="row mb-3">
                        <div class="col-md-6 mb-2">
                            <label class="form-label">User Name</label>
                            <input type="text" name="user_name" class="form-control" maxlength="50"
                                   value="{{ old('user_name', $para->user_name) }}">
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="form-label">User Password
                                @if($para->user_pw) <small class="text-success">(set)</small> @endif
                            </label>
                            <input type="password" name="user_pw" class="form-control"
                                   placeholder="Leave blank to keep current">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> Save Parameters
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
