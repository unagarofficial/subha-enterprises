<div class="row g-3">
    <div class="col-md-8">
        <label class="form-label">Firm Name <span class="text-danger">*</span></label>
        <input type="text" name="firm_name" class="form-control @error('firm_name') is-invalid @enderror"
               value="{{ old('firm_name', $firm->firm_name ?? '') }}" required maxlength="100">
        @error('firm_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label class="form-label">Type <span class="text-danger">*</span></label>
        <select name="type" class="form-select @error('type') is-invalid @enderror" required>
            <option value="H" {{ old('type', $firm->type ?? '') == 'H' ? 'selected' : '' }}>H — Head Office</option>
            <option value="B" {{ old('type', $firm->type ?? '') == 'B' ? 'selected' : '' }}>B — Branch</option>
        </select>
        @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-12">
        <label class="form-label">Address</label>
        <textarea name="address" class="form-control" rows="2" maxlength="255">{{ old('address', $firm->address ?? '') }}</textarea>
    </div>

    <div class="col-md-6">
        <label class="form-label">Place <span class="text-danger">*</span></label>
        <input type="text" name="place" class="form-control @error('place') is-invalid @enderror"
               value="{{ old('place', $firm->place ?? '') }}" required maxlength="100">
        @error('place')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-3">
        <label class="form-label">Phone</label>
        <input type="text" name="phone" class="form-control"
               value="{{ old('phone', $firm->phone ?? '') }}" maxlength="15" pattern="\d*">
    </div>

    <div class="col-md-3">
        <label class="form-label">Mobile</label>
        <input type="text" name="mobile" class="form-control"
               value="{{ old('mobile', $firm->mobile ?? '') }}" maxlength="15" pattern="\d*">
    </div>

    <div class="col-md-6">
        <label class="form-label">Website</label>
        <input type="text" name="website" class="form-control"
               value="{{ old('website', $firm->website ?? '') }}" maxlength="100" placeholder="www.example.com">
    </div>

    <div class="col-md-4">
        <label class="form-label">GST / TIN No. <small class="text-muted">(max 15)</small></label>
        <input type="text" name="tin_no" class="form-control @error('tin_no') is-invalid @enderror"
               value="{{ old('tin_no', $firm->tin_no ?? '') }}" maxlength="15"
               style="text-transform:uppercase;" placeholder="15-digit GST number">
        @error('tin_no')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-2">
        <label class="form-label">HO Code</label>
        <input type="number" name="ho_code" class="form-control"
               value="{{ old('ho_code', $firm->ho_code ?? '') }}" min="1">
    </div>
</div>
