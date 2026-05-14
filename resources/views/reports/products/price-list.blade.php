@extends('layouts.app')
@section('title', 'Price List')

@push('styles')
<style>
.price-list-header { text-align:center; border-bottom:2px solid #1a3c5e; padding-bottom:8px; margin-bottom:16px; }
.price-list-header h4 { margin:0; color:#1a3c5e; font-weight:700; }
.price-list-header p  { margin:0; font-size:0.85rem; color:#555; }
.cat-heading { background:#1a3c5e; color:#fff; padding:5px 10px; font-weight:700;
               font-size:0.85rem; margin-top:12px; border-radius:3px; }
.price-item { display:flex; justify-content:space-between; padding:4px 8px;
              border-bottom:1px dotted #ddd; font-size:0.82rem; }
.price-item:nth-child(even) { background:#f9f9f9; }
.price-item .item-name { flex:1; }
.price-item .item-rate { font-weight:600; color:#1a3c5e; text-align:right; min-width:100px; }

.rpt-table th { background:#1a3c5e; color:#fff; padding:6px 8px; font-size:0.80rem; }
.rpt-table td { padding:5px 8px; font-size:0.80rem; vertical-align:middle; }
.rpt-table .cat-header td { background:#d1e7dd; font-weight:700; }
@media print {
    .no-print, nav, .footer { display:none !important; }
    .card { box-shadow:none !important; border:1px solid #ccc !important; }
    body { background:#fff !important; font-size:11px; }
    .cat-heading { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
    .price-item:nth-child(even) { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
}
</style>
@endpush

@section('content')

<div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <h5 class="mb-0"><i class="bi bi-tag me-1 text-primary"></i> Price List</h5>
</div>

{{-- Filter Card --}}
<div class="card mb-3 no-print">
    <div class="card-header py-2">Filter</div>
    <div class="card-body py-2">
        <form method="GET" action="{{ route('reports.products.price-list') }}" id="filterForm">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Category</label>
                    <select name="cat_code" class="form-select form-select-sm">
                        <option value="">All Categories</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->cat_code }}" {{ $catCode == $cat->cat_code ? 'selected' : '' }}>
                                {{ $cat->cat_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Rate Type</label>
                    <select name="rate_type" class="form-select form-select-sm">
                        <option value="sale_rate" {{ $rateType === 'sale_rate' ? 'selected' : '' }}>Sale Rate</option>
                        <option value="y_rate"    {{ $rateType === 'y_rate'    ? 'selected' : '' }}>Yoshita Rate</option>
                        <option value="b_rate"    {{ $rateType === 'b_rate'    ? 'selected' : '' }}>Bangalore Rate</option>
                        <option value="all"       {{ $rateType === 'all'       ? 'selected' : '' }}>All Rates</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" name="show" value="1" class="btn btn-primary btn-sm">
                        <i class="bi bi-search me-1"></i>Show
                    </button>
                    @if($showReport && !empty($catGroups))
                    <button type="button" onclick="window.print()" class="btn btn-secondary btn-sm">
                        <i class="bi bi-printer me-1"></i>Print
                    </button>
                    <a href="{{ route('reports.products.price-list.export') }}?{{ http_build_query(request()->query()) }}"
                       class="btn btn-success btn-sm">
                        <i class="bi bi-file-earmark-excel me-1"></i>Excel
                    </a>
                    @endif
                </div>
            </div>
        </form>
    </div>
</div>

@if($showReport)
<div class="card">
    <div class="card-body">
        @if(empty($catGroups))
            <div class="text-center py-4 text-muted"><i class="bi bi-inbox me-1"></i>No products found.</div>
        @else

        {{-- Firm Header (print) --}}
        <div class="price-list-header">
            <h4>{{ $firm?->firm_name ?? 'Price List' }}</h4>
            @if($firm?->address)
            <p>{{ $firm->address }}@if($firm->place), {{ $firm->place }}@endif</p>
            @endif
            @if($firm?->phone || $firm?->mobile)
            <p>
                @if($firm->phone) Ph: {{ $firm->phone }} @endif
                @if($firm->mobile) | Mob: {{ $firm->mobile }} @endif
            </p>
            @endif
            <p style="margin-top:4px; font-weight:600; color:#1a3c5e;">
                @if($rateType === 'all') Rate List (Sale / Y-Rate / B-Rate)
                @elseif($rateType === 'y_rate') Yoshita Rate List
                @elseif($rateType === 'b_rate') Bangalore Rate List
                @else Price List @endif
            </p>
        </div>

        @foreach($catGroups as $catName => $products)
        <div class="cat-heading">{{ $catName }}</div>
        <div class="mb-2">
            @foreach($products as $prod)
            <div class="price-item">
                <span class="item-name">{{ $prod->mat_name }}</span>
                <span class="item-rate">
                    @if($rateType === 'all')
                        {{ indianFmt($prod->sale_rate) }} / {{ indianFmt($prod->y_rate) }} / {{ indianFmt($prod->b_rate) }}
                    @else
                        {{ indianFmt($prod->$rateType) }}
                    @endif
                </span>
            </div>
            @endforeach
        </div>
        @endforeach

        @endif
    </div>
</div>
@endif

@endsection
