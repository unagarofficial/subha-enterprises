@extends('layouts.app')

@section('title', 'Dashboard — Subha Enterprises')

@push('styles')
<style>
    .summary-card { border-radius: 8px; border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.10); }
    .summary-card .card-icon { font-size: 2rem; opacity: 0.9; }
    .summary-card .card-value { font-size: 1.45rem; font-weight: 700; line-height: 1.1; }
    .summary-card .card-label { font-size: 0.76rem; letter-spacing: 0.5px; text-transform: uppercase; opacity: 0.85; }
    .summary-card .card-sub { font-size: 0.78rem; margin-top: 4px; opacity: 0.80; }
    .quick-link-card { border: none; box-shadow: 0 2px 6px rgba(0,0,0,0.08); border-radius: 8px; transition: transform 0.15s, box-shadow 0.15s; cursor: pointer; text-decoration: none; color: inherit; }
    .quick-link-card:hover { transform: translateY(-3px); box-shadow: 0 6px 16px rgba(0,0,0,0.13); color: inherit; }
    .section-title { font-size: 0.78rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: #6c757d; margin-bottom: 10px; padding-bottom: 6px; border-bottom: 2px solid #e9ecef; }
</style>
@endpush

@section('content')

{{-- ── Welcome Banner ─────────────────────────────────────────────────────── --}}
<div class="card mb-4 border-0" style="background:linear-gradient(135deg,#0d2b47,#1a3c5e); color:#fff; border-radius:10px;">
    <div class="card-body d-flex align-items-center py-3 px-4">
        <i class="bi bi-gem me-3" style="font-size:2.4rem; color:#ffc107;"></i>
        <div>
            <h5 class="mb-0 fw-bold">Welcome, {{ session('user_name') }}!</h5>
            <div style="font-size:0.80rem; color:#8faec8; margin-top:3px;">
                <span class="me-3"><i class="bi bi-building me-1 text-warning"></i>{{ session('br_name') }}</span>
                <span class="me-3"><i class="bi bi-calendar3 me-1 text-warning"></i>F.Y. {{ session('fin_year_name') }}</span>
                <span><i class="bi bi-calendar-date me-1 text-warning"></i>{{ \Carbon\Carbon::parse($loginDate)->format('d-M-Y') }}</span>
            </div>
        </div>
        <div class="ms-auto text-end" style="font-size:0.78rem; color:#8faec8;">
            <div>{{ session('user_type') == 'ADMIN' ? 'Administrator' : 'User' }}</div>
            <div class="mt-1">{{ now()->format('D, d M Y') }}</div>
        </div>
    </div>
</div>

{{-- ── Row 1: Today's Summary ──────────────────────────────────────────────── --}}
<div class="section-title"><i class="bi bi-sun me-1"></i>Today's Summary — {{ \Carbon\Carbon::parse($loginDate)->format('d M Y') }}</div>
<div class="row g-3 mb-4">

    {{-- Today Sales --}}
    <div class="col-md-4 col-sm-6">
        <div class="card summary-card" style="background:linear-gradient(135deg,#198754,#20c45e); color:#fff;">
            <div class="card-body d-flex align-items-center p-3">
                <i class="bi bi-receipt card-icon me-3"></i>
                <div>
                    <div class="card-label">Today's Sales</div>
                    <div class="card-value">₹ {{ indianFmt($todaySales->total ?? 0) }}</div>
                    <div class="card-sub">{{ $todaySales->cnt ?? 0 }} invoice(s)</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Today Purchases --}}
    <div class="col-md-4 col-sm-6">
        <div class="card summary-card" style="background:linear-gradient(135deg,#0d6efd,#4d9eff); color:#fff;">
            <div class="card-body d-flex align-items-center p-3">
                <i class="bi bi-cart-plus card-icon me-3"></i>
                <div>
                    <div class="card-label">Today's Purchases</div>
                    <div class="card-value">₹ {{ indianFmt($todayPurchases->total ?? 0) }}</div>
                    <div class="card-sub">{{ $todayPurchases->cnt ?? 0 }} invoice(s)</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Low Stock --}}
    <div class="col-md-4 col-sm-6">
        <div class="card summary-card" style="background:linear-gradient(135deg,#dc3545,#ff6b7a); color:#fff;">
            <div class="card-body d-flex align-items-center p-3">
                <i class="bi bi-exclamation-triangle card-icon me-3"></i>
                <div>
                    <div class="card-label">Low Stock Items</div>
                    <div class="card-value">{{ $lowStockCount }}</div>
                    <div class="card-sub">Items with stock ≤ 10</div>
                </div>
            </div>
        </div>
    </div>

</div>

{{-- ── Row 2: Chart + Top Customers ───────────────────────────────────────── --}}
<div class="section-title"><i class="bi bi-bar-chart me-1"></i>Monthly Overview — {{ now()->format('F Y') }}</div>
<div class="row g-3 mb-4">

    {{-- Sales Chart --}}
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header" style="background:#f8f9fa;">
                <i class="bi bi-graph-up-arrow me-1 text-success"></i>
                Sales — Last 30 Days
            </div>
            <div class="card-body p-3">
                <canvas id="salesChart" style="max-height:240px;"></canvas>
            </div>
        </div>
    </div>

    {{-- Top 5 Customers --}}
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header" style="background:#f8f9fa;">
                <i class="bi bi-trophy me-1 text-warning"></i>
                Top 5 Customers — This Month
            </div>
            <div class="card-body p-0">
                @if($topCustomers->isEmpty())
                    <p class="text-muted text-center py-4" style="font-size:0.82rem;">No sales this month.</p>
                @else
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Customer</th>
                            <th class="text-end">Amount</th>
                            <th class="text-center">Bills</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($topCustomers as $i => $c)
                        <tr>
                            <td>
                                @if($i === 0) <i class="bi bi-trophy-fill text-warning"></i>
                                @elseif($i === 1) <i class="bi bi-trophy-fill text-secondary"></i>
                                @elseif($i === 2) <i class="bi bi-trophy-fill" style="color:#cd7f32;"></i>
                                @else {{ $i + 1 }}
                                @endif
                            </td>
                            <td class="fw-semibold" style="max-width:130px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="{{ $c->party_name }}">{{ $c->party_name }}</td>
                            <td class="text-end">₹ {{ indianFmt($c->total) }}</td>
                            <td class="text-center"><span class="badge bg-secondary">{{ $c->bill_count }}</span></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif
            </div>
        </div>
    </div>

</div>

{{-- ── Row 3: Quick Links ──────────────────────────────────────────────────── --}}
<div class="section-title"><i class="bi bi-lightning me-1"></i>Quick Links</div>
<div class="row g-3 mb-4">

    <div class="col-6 col-md-3">
        <a href="{{ route('transactions.sale.create', ['saleType' => 1]) }}" class="quick-link-card card text-center p-3 d-block">
            <i class="bi bi-receipt" style="font-size:1.7rem; color:#198754;"></i>
            <div class="mt-2 fw-semibold" style="font-size:0.84rem;">New Sale Entry</div>
            <div class="text-muted" style="font-size:0.74rem;">Cash Sale (Type 1)</div>
        </a>
    </div>

    <div class="col-6 col-md-3">
        <a href="{{ route('transactions.purchase.create') }}" class="quick-link-card card text-center p-3 d-block">
            <i class="bi bi-cart-plus" style="font-size:1.7rem; color:#0d6efd;"></i>
            <div class="mt-2 fw-semibold" style="font-size:0.84rem;">New Purchase Entry</div>
            <div class="text-muted" style="font-size:0.74rem;">Purchase Invoice</div>
        </a>
    </div>

    <div class="col-6 col-md-3">
        <a href="{{ route('reports.stock.current') }}" class="quick-link-card card text-center p-3 d-block">
            <i class="bi bi-box-seam" style="font-size:1.7rem; color:#fd7e14;"></i>
            <div class="mt-2 fw-semibold" style="font-size:0.84rem;">View Stock</div>
            <div class="text-muted" style="font-size:0.74rem;">Current Stock Report</div>
        </a>
    </div>

    <div class="col-6 col-md-3">
        <a href="{{ route('reports.ledger.party') }}" class="quick-link-card card text-center p-3 d-block">
            <i class="bi bi-journal-text" style="font-size:1.7rem; color:#6f42c1;"></i>
            <div class="mt-2 fw-semibold" style="font-size:0.84rem;">Party Ledger</div>
            <div class="text-muted" style="font-size:0.74rem;">Account Statement</div>
        </a>
    </div>

</div>

{{-- ── Row 4: Recent Transactions ─────────────────────────────────────────── --}}
<div class="section-title"><i class="bi bi-clock-history me-1"></i>Recent Transactions</div>
<div class="row g-3">

    {{-- Recent Sales --}}
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center" style="background:#f8f9fa;">
                <span><i class="bi bi-receipt me-1 text-success"></i>Last 10 Sales</span>
                <a href="{{ route('transactions.sale.index', ['saleType' => 1]) }}" class="btn btn-xs btn-outline-secondary">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Bill No</th>
                                <th>Date</th>
                                <th>Customer</th>
                                <th class="text-end">Net Amt</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentSales as $s)
                            <tr>
                                <td>
                                    <a href="{{ route('transactions.sale.edit', [$s->sale_type, session('br_code'), $s->inv_no]) }}"
                                       class="text-decoration-none fw-semibold">
                                       {{ formatBillNo(session('br_code'), session('fin_year_name'), $s->inv_no) }}
                                    </a>
                                </td>
                                <td>{{ \Carbon\Carbon::parse($s->inv_date)->format('d-M') }}</td>
                                <td style="max-width:120px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="{{ $s->party_name }}">{{ $s->party_name }}</td>
                                <td class="text-end">{{ indianFmt($s->nett) }}</td>
                                <td class="text-center">
                                    @if($s->is_locked)
                                        <span class="badge bg-success" style="font-size:0.70rem;">Locked</span>
                                    @else
                                        <span class="badge bg-secondary" style="font-size:0.70rem;">Open</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-muted text-center py-3">No recent sales.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Recent Purchases --}}
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center" style="background:#f8f9fa;">
                <span><i class="bi bi-cart-plus me-1 text-primary"></i>Last 10 Purchases</span>
                <a href="{{ route('transactions.purchase.index') }}" class="btn btn-xs btn-outline-secondary">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Bill No</th>
                                <th>Date</th>
                                <th>Supplier</th>
                                <th class="text-end">Net Amt</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentPurchases as $p)
                            <tr>
                                <td>
                                    <a href="{{ route('transactions.purchase.edit', [session('br_code'), $p->inv_no]) }}"
                                       class="text-decoration-none fw-semibold">
                                       {{ formatBillNo(session('br_code'), session('fin_year_name'), $p->inv_no) }}
                                    </a>
                                </td>
                                <td>{{ \Carbon\Carbon::parse($p->inv_date)->format('d-M') }}</td>
                                <td style="max-width:140px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="{{ $p->party_name }}">{{ $p->party_name }}</td>
                                <td class="text-end">{{ indianFmt($p->nett) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-muted text-center py-3">No recent purchases.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
(function () {
    const labels = @json($chartLabels);
    const values = @json($chartValues);

    const ctx = document.getElementById('salesChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Sales (₹)',
                data: values,
                backgroundColor: 'rgba(25,135,84,0.75)',
                borderColor: '#198754',
                borderWidth: 1,
                borderRadius: 3,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function (ctx) {
                            const v = ctx.parsed.y;
                            return '₹ ' + v.toLocaleString('en-IN', { minimumFractionDigits: 2 });
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { font: { size: 10 }, maxRotation: 45 }
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        font: { size: 10 },
                        callback: function (v) {
                            if (v >= 100000) return '₹' + (v / 100000).toFixed(1) + 'L';
                            if (v >= 1000)   return '₹' + (v / 1000).toFixed(0) + 'K';
                            return '₹' + v;
                        }
                    }
                }
            }
        }
    });
})();
</script>
@endpush
