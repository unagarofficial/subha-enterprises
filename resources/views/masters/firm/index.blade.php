@extends('layouts.app')
@section('title', 'Firm Information')

@push('styles')
<style>
    .firm-field { margin-bottom: 16px; }
    .firm-label { font-weight: 600; color: #555; font-size: 0.80rem; text-transform: uppercase; letter-spacing: 0.5px; }
    .firm-value { font-size: 0.90rem; color: #222; margin-top: 2px; }
</style>
@endpush

@section('content')
<div class="d-flex align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-building me-2 text-primary"></i>Firm Information</h5>
</div>

@if(!$firm)
{{-- ════ NO FIRM YET — SHOW CREATE FORM ════ --}}
<div class="card">
    <div class="card-header bg-primary text-white">
        <i class="bi bi-plus-circle me-1"></i> Add Firm Information
    </div>
    <div class="card-body">
        <form action="{{ route('masters.firm.store') }}" method="POST">
            @csrf
            @include('masters.firm._form', ['firm' => null])
            <hr>
            <button type="submit" class="btn btn-primary px-4">
                <i class="bi bi-save me-1"></i> Save
            </button>
        </form>
    </div>
</div>

@else
{{-- ════ FIRM EXISTS — SHOW VIEW + EDIT ════ --}}
<div class="row g-3">
    {{-- View Card --}}
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-info-circle me-1 text-primary"></i>Firm Details</span>
                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal">
                    <i class="bi bi-pencil me-1"></i>Edit
                </button>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="firm-field">
                            <div class="firm-label">Firm Code</div>
                            <div class="firm-value">{{ $firm->firm_code }}</div>
                        </div>
                        <div class="firm-field">
                            <div class="firm-label">Firm Name</div>
                            <div class="firm-value fw-bold">{{ $firm->firm_name }}</div>
                        </div>
                        <div class="firm-field">
                            <div class="firm-label">Place</div>
                            <div class="firm-value">{{ $firm->place ?: '—' }}</div>
                        </div>
                        <div class="firm-field">
                            <div class="firm-label">Address</div>
                            <div class="firm-value">{{ $firm->address ?: '—' }}</div>
                        </div>
                        <div class="firm-field">
                            <div class="firm-label">Type</div>
                            <div class="firm-value">
                                <span class="badge {{ $firm->type == 'H' ? 'bg-primary' : 'bg-secondary' }}">
                                    {{ $firm->type == 'H' ? 'Head Office' : 'Branch' }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="firm-field">
                            <div class="firm-label">Phone</div>
                            <div class="firm-value">{{ $firm->phone ?: '—' }}</div>
                        </div>
                        <div class="firm-field">
                            <div class="firm-label">Mobile</div>
                            <div class="firm-value">{{ $firm->mobile ?: '—' }}</div>
                        </div>
                        <div class="firm-field">
                            <div class="firm-label">Website</div>
                            <div class="firm-value">{{ $firm->website ?: '—' }}</div>
                        </div>
                        <div class="firm-field">
                            <div class="firm-label">GST / TIN No.</div>
                            <div class="firm-value">{{ $firm->tin_no ?: '—' }}</div>
                        </div>
                        <div class="firm-field">
                            <div class="firm-label">HO Branch Code</div>
                            <div class="firm-value">{{ $firm->ho_code ?: '—' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Edit Modal --}}
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h6 class="modal-title"><i class="bi bi-pencil me-1"></i>Edit Firm Information</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('masters.firm.update', $firm->firm_code) }}" method="POST">
                @csrf @method('PUT')
                <div class="modal-body">
                    @include('masters.firm._form', ['firm' => $firm])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4">
                        <i class="bi bi-save me-1"></i>Update
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@if($errors->any())
<script>
    document.addEventListener('DOMContentLoaded', function(){
        new bootstrap.Modal(document.getElementById('editModal')).show();
    });
</script>
@endif

@endif
@endsection
