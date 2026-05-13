<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label">Category <span class="text-danger">*</span></label>
        <select name="cat_code" class="form-select @error('cat_code') is-invalid @enderror" required>
            <option value="">— Select —</option>
            @foreach($categories as $c)
                <option value="{{ $c->cat_code }}"
                    {{ old('cat_code', $product->cat_code ?? request('cat_code')) == $c->cat_code ? 'selected' : '' }}>
                    {{ $c->cat_name }}
                </option>
            @endforeach
        </select>
        @error('cat_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label class="form-label">Material Code <span class="text-danger">*</span></label>
        <input type="text" name="mat_code"
               class="form-control @error('mat_code') is-invalid @enderror"
               value="{{ old('mat_code', $product->mat_code ?? '') }}"
               required maxlength="50" style="text-transform:uppercase;"
               placeholder="e.g. COV-001"
               {{ $product ? 'readonly' : '' }}>
        @error('mat_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
        @if(!$product)<small class="text-muted">Only letters, numbers, hyphens, underscores.</small>@endif
    </div>

    <div class="col-md-4">
        <label class="form-label">UOM <span class="text-danger">*</span></label>
        <select name="uom" class="form-select @error('uom') is-invalid @enderror" required>
            <option value="">— Select —</option>
            @foreach($uoms as $u)
                <option value="{{ $u->uom_code }}"
                    {{ old('uom', $product->uom ?? '') == $u->uom_code ? 'selected' : '' }}>
                    {{ $u->uom_name }}
                </option>
            @endforeach
        </select>
        @error('uom')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-12">
        <label class="form-label">Material Name <span class="text-danger">*</span></label>
        <input type="text" name="mat_name"
               class="form-control @error('mat_name') is-invalid @enderror"
               value="{{ old('mat_name', $product->mat_name ?? '') }}"
               required maxlength="100" placeholder="Full description of the product">
        @error('mat_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-3">
        <label class="form-label">Sale Rate</label>
        <div class="input-group input-group-sm">
            <span class="input-group-text">₹</span>
            <input type="number" name="sale_rate" class="form-control"
                   value="{{ old('sale_rate', $product->sale_rate ?? '') }}"
                   step="0.01" min="0" placeholder="0.00">
        </div>
    </div>

    <div class="col-md-3">
        <label class="form-label">Y-Rate <small class="text-muted">(Yoshita)</small></label>
        <div class="input-group input-group-sm">
            <span class="input-group-text">₹</span>
            <input type="number" name="y_rate" class="form-control"
                   value="{{ old('y_rate', $product->y_rate ?? '') }}"
                   step="0.01" min="0" placeholder="0.00">
        </div>
    </div>

    <div class="col-md-3">
        <label class="form-label">B-Rate <small class="text-muted">(Bangalore)</small></label>
        <div class="input-group input-group-sm">
            <span class="input-group-text">₹</span>
            <input type="number" name="b_rate" class="form-control"
                   value="{{ old('b_rate', $product->b_rate ?? '') }}"
                   step="0.01" min="0" placeholder="0.00">
        </div>
    </div>

    <div class="col-md-3">
        <label class="form-label">Branch <span class="text-danger">*</span></label>
        <select name="br_code" class="form-select @error('br_code') is-invalid @enderror" required>
            @foreach($branches as $br)
                <option value="{{ $br->br_code }}"
                    {{ old('br_code', $product->br_code ?? session('br_code')) == $br->br_code ? 'selected' : '' }}>
                    {{ $br->br_name }}
                </option>
            @endforeach
        </select>
        @error('br_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
