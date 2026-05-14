@extends('layouts.app')
@section('title', 'Product List')

@push('styles')
<style>
.rpt-table th  { background:#1a3c5e; color:#fff; padding:6px 8px; font-size:0.80rem; white-space:nowrap; }
.rpt-table td  { padding:5px 8px; font-size:0.80rem; vertical-align:middle; }
.rpt-table .cat-header td { background:#d1e7dd; font-weight:700; font-size:0.82rem; }
.rpt-table .cat-total  td { background:#fff3cd; font-weight:600; }
@media print {
    .no-print, nav, .footer { display:none !important; }
    .card { box-shadow:none !important; border:1px solid #ccc !important; }
    body { background:#fff !important; font-size:11px; }
    .rpt-table th           { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
    .rpt-table .cat-header td { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
    .rpt-table .cat-total  td { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
}
</style>
@endpush

@section('content')

<div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <h5 class="mb-0"><i class="bi bi-box-seam me-1 text-primary"></i> Product List</h5>
</div>

{{-- Filter Card --}}
<div class="card mb-3 no-print">
    <div class="card-header py-2">Filter</div>
    <div class="card-body py-2">
        <form method="GET" action="{{ route('reports.products.list') }}" id="filterForm">
            <div class="row g-2 align-items-end">
                <div class="col-md-2">
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
                <div class="col-md-2">
                    <label class="form-label">Rate Type</label>
                    <select name="rate_type" class="form-select form-select-sm">
                        <option value="sale_rate" {{ $rateType === 'sale_rate' ? 'selected' : '' }}>Sale Rate</option>
                        <option value="y_rate"    {{ $rateType === 'y_rate'    ? 'selected' : '' }}>Yoshita Rate</option>
                        <option value="b_rate"    {{ $rateType === 'b_rate'    ? 'selected' : '' }}>Bangalore Rate</option>
                        <option value="all"       {{ $rateType === 'all'       ? 'selected' : '' }}>All Rates</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Branch</label>
                    <select name="br_code" class="form-select form-select-sm">
                        <option value="">All Branches</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->br_code }}" {{ $brCode == $b->br_code ? 'selected' : '' }}>
                                {{ $b->br_name }}
                            </option>
                        @endforeach
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
                    <a href="{{ route('reports.products.list.export') }}?{{ http_build_query(request()->query()) }}"
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

{{-- Print Header --}}
<div class="d-none d-print-block text-center mb-2">
    <h5 class="mb-0">Product List</h5>
    <div style="font-size:0.85rem;">
        @if($rateType === 'all') All Rates
        @elseif($rateType === 'y_rate') Yoshita Rate
        @elseif($rateType === 'b_rate') Bangalore Rate
        @else Sale Rate @endif
    </div>
</div>

<div class="card">
    <div class="card-header py-2 no-print">
        <i class="bi bi-table me-1"></i>Product List
    </div>
    <div class="card-body p-0">
        @if(empty($catGroups))
            <div class="text-center py-4 text-muted"><i class="bi bi-inbox me-1"></i>No products found.</div>
        @else
        <div class="table-responsive">
        <table class="table table-bordered rpt-table mb-0">
            <thead>
                <tr>
                    <th style="width:40px;">#</th>
                    <th>Mat Code</th>
                    <th>Item Name</th>
                    <th>UOM</th>
                    @if($rateType === 'all')
                    <th class="text-end">Sale Rate</th>
                    <th class="text-end">Y-Rate</th>
                    <th class="text-end">B-Rate</th>
                    @else
                    <th class="text-end">
                        @if($rateType === 'y_rate') Yoshita Rate
                        @elseif($rateType === 'b_rate') Bangalore Rate
                        @else Sale Rate @endif
                    </th>
                    @endif
                </tr>
            </thead>
            <tbody>
            @php $grandCount = 0; @endphp
            @foreach($catGroups as $catName => $products)
                <tr class="cat-header">
                    <td colspan="{{ $rateType === 'all' ? 7 : 5 }}">
                        <i class="bi bi-tag me-1"></i>{{ $catName }}
                    </td>
                </tr>
                @php $sno = 1; @endphp
                @foreach($products as $prod)
                <tr>
                    <td class="text-center">{{ $sno++ }}</td>
                    <td>{{ $prod->mat_code }}</td>
                    <td>{{ $prod->mat_name }}</td>
                    <td>{{ $prod->uom_name ?? '' }}</td>
                    @if($rateType === 'all')
                    <td class="text-end">{{ indianFmt($prod->sale_rate) }}</td>
                    <td class="text-end">{{ indianFmt($prod->y_rate) }}</td>
                    <td class="text-end">{{ indianFmt($prod->b_rate) }}</td>
                    @else
                    <td class="text-end">{{ indianFmt($prod->$rateType) }}</td>
                    @endif
                </tr>
                @endforeach
                <tr class="cat-total">
                    <td colspan="{{ $rateType === 'all' ? 7 : 5 }}">
                        Total items in {{ $catName }}: {{ count($products) }}
                    </td>
                </tr>
                @php $grandCount += count($products); @endphp
            @endforeach
            </tbody>
            <tfoot>
                <tr style="background:#1a3c5e; color:#fff; font-weight:700;">
                    <td colspan="{{ $rateType === 'all' ? 7 : 5 }}">
                        <i class="bi bi-check2-all me-1"></i>Grand Total: {{ $grandCount }} products
                    </td>
                </tr>
            </tfoot>
        </table>
        </div>
        @endif
    </div>
</div>
@endif

@endsection
