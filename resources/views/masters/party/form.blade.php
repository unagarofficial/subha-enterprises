@extends('layouts.app')
@section('title', ($party ? 'Edit' : 'Add') . ' Party')

@push('styles')
<style>
    .section-title {
        font-size: 0.78rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        color: #1a3c5e;
        border-bottom: 2px solid #1a3c5e;
        padding-bottom: 4px;
        margin-bottom: 14px;
    }
    .toggle-radio .form-check { display: inline-block; margin-right: 20px; }
    .toggle-radio .form-check-input:checked { background-color: #1a3c5e; border-color: #1a3c5e; }
    #tin_grn_no:disabled { background: #f4f6f9; }
</style>
@endpush

@section('content')

{{-- Breadcrumb --}}
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb mb-0" style="font-size:0.82rem;">
        <li class="breadcrumb-item"><a href="{{ route('masters.party.index') }}">Party Master</a></li>
        <li class="breadcrumb-item active">{{ $party ? 'Edit: ' . $party->party_name : 'Add New Party' }}</li>
    </ol>
</nav>

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between"
         style="background: linear-gradient(90deg,#0d2b47,#1a3c5e); color:#fff;">
        <span>
            <i class="bi bi-{{ $party ? 'pencil' : 'person-plus' }} me-2"></i>
            {{ $party ? 'Edit Party — ' . $party->party_name : 'Add New Party' }}
        </span>
        @if($party)
            <span class="badge {{ $party->party_type == 'C' ? 'bg-primary' : 'bg-warning text-dark' }}">
                {{ $party->party_type == 'C' ? 'Customer' : 'Supplier' }}
                #{{ $party->party_code }}
            </span>
        @endif
    </div>

    <div class="card-body">
        <form action="{{ $party ? route('masters.party.update', $party->party_code) : route('masters.party.store') }}"
              method="POST" id="partyForm">
            @csrf
            @if($party) @method('PUT') @endif

            {{-- ═══ SECTION 1: Basic Info ════════════════════════════════════ --}}
            <div class="section-title"><i class="bi bi-info-circle me-1"></i>Basic Information</div>

            <div class="row g-3 mb-4">

                {{-- Branch --}}
                <div class="col-md-4">
                    <label class="form-label">Branch <span class="text-danger">*</span></label>
                    <select name="br_code" class="form-select @error('br_code') is-invalid @enderror" required>
                        @foreach($branches as $br)
                            <option value="{{ $br->br_code }}"
                                {{ old('br_code', $party->br_code ?? session('br_code')) == $br->br_code ? 'selected' : '' }}>
                                {{ $br->br_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('br_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Party Type --}}
                <div class="col-md-4">
                    <label class="form-label">Party Type <span class="text-danger">*</span></label>
                    <div class="d-flex gap-4 mt-1 toggle-radio">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="party_type"
                                   id="typeC" value="C"
                                   {{ old('party_type', $party->party_type ?? $defaultType) == 'C' ? 'checked' : '' }} required>
                            <label class="form-check-label fw-semibold text-primary" for="typeC">
                                <i class="bi bi-person me-1"></i>Customer
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="party_type"
                                   id="typeS" value="S"
                                   {{ old('party_type', $party->party_type ?? $defaultType) == 'S' ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold text-warning" for="typeS">
                                <i class="bi bi-shop me-1"></i>Supplier
                            </label>
                        </div>
                    </div>
                    @error('party_type')<div class="text-danger" style="font-size:0.80rem;">{{ $message }}</div>@enderror
                </div>

                {{-- Party Code (show only on edit) --}}
                @if($party)
                <div class="col-md-2">
                    <label class="form-label">Party Code</label>
                    <input type="text" class="form-control" value="{{ $party->party_code }}" disabled>
                </div>
                @endif

                {{-- Party Name --}}
                <div class="col-md-{{ $party ? '10' : '12' }}">
                    <label class="form-label">Party Name <span class="text-danger">*</span></label>
                    <input type="text" name="party_name"
                           class="form-control @error('party_name') is-invalid @enderror"
                           value="{{ old('party_name', $party->party_name ?? '') }}"
                           required maxlength="100" placeholder="Full legal name of the party">
                    @error('party_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

            </div>

            {{-- ═══ SECTION 2: Address ════════════════════════════════════════ --}}
            <div class="section-title"><i class="bi bi-geo-alt me-1"></i>Address Details</div>

            <div class="row g-3 mb-4">

                <div class="col-md-12">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control" rows="2"
                              maxlength="150" placeholder="Street / Door No / Colony">{{ old('address', $party->address ?? '') }}</textarea>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Place <span class="text-danger">*</span></label>
                    <input type="text" name="place"
                           class="form-control @error('place') is-invalid @enderror"
                           value="{{ old('place', $party->place ?? '') }}"
                           required maxlength="50" placeholder="City / Town">
                    @error('place')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label">State <span class="text-danger">*</span></label>
                    <select name="state" class="form-select @error('state') is-invalid @enderror" required>
                        <option value="">— Select State —</option>
                        @foreach($states as $st)
                            <option value="{{ $st }}"
                                {{ old('state', $party->state ?? 'Andhra Pradesh') == $st ? 'selected' : '' }}>
                                {{ $st }}
                            </option>
                        @endforeach
                    </select>
                    @error('state')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-2">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone"
                           class="form-control @error('phone') is-invalid @enderror"
                           value="{{ old('phone', $party->phone ?? '') }}"
                           maxlength="10" pattern="\d{10}" placeholder="10 digits">
                    @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-2">
                    <label class="form-label">Mobile</label>
                    <input type="text" name="mobile"
                           class="form-control @error('mobile') is-invalid @enderror"
                           value="{{ old('mobile', $party->mobile ?? '') }}"
                           maxlength="10" pattern="\d{10}" placeholder="10 digits">
                    @error('mobile')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

            </div>

            {{-- ═══ SECTION 3: GST / Tax Info ═════════════════════════════════ --}}
            <div class="section-title"><i class="bi bi-percent me-1"></i>GST / Tax Information</div>

            <div class="row g-3 mb-4">

                {{-- In/Out State --}}
                <div class="col-md-4">
                    <label class="form-label">GST Type <span class="text-danger">*</span></label>
                    <div class="d-flex gap-4 mt-1 toggle-radio">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="inout_state"
                                   id="inState" value="0"
                                   {{ old('inout_state', $party->inout_state ?? '0') == '0' ? 'checked' : '' }} required>
                            <label class="form-check-label" for="inState">
                                <span class="badge bg-success">In-State</span>
                                <small class="text-muted ms-1">CGST + SGST</small>
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="inout_state"
                                   id="outState" value="1"
                                   {{ old('inout_state', $party->inout_state ?? '0') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="outState">
                                <span class="badge bg-warning text-dark">Out-State</span>
                                <small class="text-muted ms-1">IGST</small>
                            </label>
                        </div>
                    </div>
                    @error('inout_state')<div class="text-danger" style="font-size:0.80rem;">{{ $message }}</div>@enderror
                </div>

                {{-- GST Registered Checkbox --}}
                <div class="col-md-3 d-flex align-items-end">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="tin_grn_flag"
                               id="gstFlag" value="1"
                               {{ old('tin_grn_flag', $party->tin_grn_flag ?? 0) == 1 ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="gstFlag">
                            <i class="bi bi-patch-check me-1 text-success"></i>GST Registered
                        </label>
                        <div><small class="text-muted">Tick if party has GSTIN</small></div>
                    </div>
                </div>

                {{-- GST/TIN Number --}}
                <div class="col-md-5">
                    <label class="form-label">
                        GST / TIN No.
                        <small class="text-muted">(exactly 15 chars)</small>
                        <span id="tinRequired" class="text-danger" style="display:none;">*</span>
                    </label>
                    <input type="text" name="tin_grn_no" id="tin_grn_no"
                           class="form-control @error('tin_grn_no') is-invalid @enderror"
                           value="{{ old('tin_grn_no', $party->tin_grn_no ?? '') }}"
                           maxlength="15" minlength="15"
                           style="text-transform:uppercase; letter-spacing:1px;"
                           placeholder="e.g. 37AAAAA0000A1Z5">
                    <div class="d-flex justify-content-between mt-1">
                        @error('tin_grn_no')
                            <div class="text-danger" style="font-size:0.78rem;">{{ $message }}</div>
                        @else
                            <small class="text-muted">15-character GSTIN</small>
                        @enderror
                        <small id="tinCounter" class="text-muted">0 / 15</small>
                    </div>
                </div>

            </div>

            {{-- ═══ Form Buttons ══════════════════════════════════════════════ --}}
            <hr class="my-3">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary px-5">
                    <i class="bi bi-save me-1"></i>{{ $party ? 'Update Party' : 'Save Party' }}
                </button>
                <a href="{{ route('masters.party.index') }}" class="btn btn-secondary px-4">
                    <i class="bi bi-arrow-left me-1"></i>Cancel
                </a>
            </div>

        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {

    // GST flag toggle — enable/disable tin_grn_no field
    function toggleTin() {
        var checked = $('#gstFlag').is(':checked');
        $('#tin_grn_no').prop('disabled', !checked);
        $('#tinRequired').toggle(checked);
        if (!checked) {
            $('#tin_grn_no').val('');
            $('#tinCounter').text('0 / 15');
        }
    }

    $('#gstFlag').on('change', toggleTin);
    toggleTin(); // init on page load

    // Character counter for TIN
    $('#tin_grn_no').on('input', function () {
        var len = $(this).val().length;
        $('#tinCounter').text(len + ' / 15');
        $('#tinCounter').toggleClass('text-danger', len > 0 && len !== 15)
                        .toggleClass('text-success', len === 15)
                        .toggleClass('text-muted', len === 0);
    }).trigger('input');

    // Only allow numbers in phone/mobile
    $('input[name="phone"], input[name="mobile"]').on('input', function () {
        this.value = this.value.replace(/\D/g, '');
    });

    // UPPERCASE the GST number
    $('#tin_grn_no').on('input', function () {
        var pos = this.selectionStart;
        this.value = this.value.toUpperCase();
        this.setSelectionRange(pos, pos);
    });

    // Client-side validation before submit
    $('#partyForm').on('submit', function (e) {
        var gst = $('#gstFlag').is(':checked');
        var tin = $('#tin_grn_no').val().trim();
        if (gst && tin.length !== 15) {
            e.preventDefault();
            $('#tin_grn_no').addClass('is-invalid').focus();
            alert('GST/TIN No. must be exactly 15 characters.');
        }
    });

});
</script>
@endpush
