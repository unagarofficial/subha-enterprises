<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Subha Enterprises')</title>

    <!-- Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        body { font-size: 0.88rem; background-color: #f4f6f9; }

        /* Top Navbar */
        .navbar-brand { font-weight: 700; font-size: 1rem; letter-spacing: 0.5px; }
        .navbar-info { font-size: 0.80rem; color: #dee2e6; }
        .navbar-info span { margin-right: 12px; }
        .navbar-info i { margin-right: 3px; color: #ffc107; }

        /* Main Menu Bar */
        .main-menu { background: #1a3c5e; }
        .main-menu .nav-link { color: #c9d8e8 !important; font-size: 0.84rem; padding: 8px 14px; }
        .main-menu .nav-link:hover,
        .main-menu .nav-link.active { color: #fff !important; background: #0d2b47; border-radius: 4px; }
        .main-menu .dropdown-menu { border-radius: 4px; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.15); min-width: 200px; }
        .main-menu .dropdown-item { font-size: 0.83rem; padding: 6px 16px; }
        .main-menu .dropdown-item:hover { background: #e8f0fe; color: #1a3c5e; }
        .main-menu .dropdown-divider { margin: 4px 0; }

        /* Content Area */
        .content-wrapper { padding: 20px; min-height: calc(100vh - 110px); }

        /* Cards */
        .card { border: none; box-shadow: 0 1px 4px rgba(0,0,0,0.08); border-radius: 6px; }
        .card-header { font-weight: 600; font-size: 0.88rem; padding: 10px 16px; }

        /* Tables */
        .table { font-size: 0.83rem; }
        .table thead th { background: #1a3c5e; color: #fff; border: none; font-weight: 500; padding: 8px 10px; }
        .table tbody td { padding: 6px 10px; vertical-align: middle; }
        .table-striped tbody tr:nth-child(odd) { background: #f8f9fa; }

        /* Forms */
        .form-label { font-weight: 500; margin-bottom: 3px; font-size: 0.83rem; }
        .form-control, .form-select { font-size: 0.83rem; border-radius: 4px; }
        .form-control:focus, .form-select:focus { border-color: #1a3c5e; box-shadow: 0 0 0 0.2rem rgba(26,60,94,0.15); }

        /* Buttons */
        .btn { font-size: 0.83rem; border-radius: 4px; }
        .btn-primary { background: #1a3c5e; border-color: #1a3c5e; }
        .btn-primary:hover { background: #0d2b47; border-color: #0d2b47; }

        /* Alert */
        .alert { font-size: 0.83rem; border-radius: 4px; }

        /* Extra small button */
        .btn-xs { padding: 2px 7px; font-size: 0.76rem; }

        /* DataTables overrides */
        .dataTables_wrapper .dataTables_filter input { font-size: 0.82rem; }
        .dataTables_wrapper .dataTables_length select { font-size: 0.82rem; }
        .dataTables_wrapper .dataTables_info { font-size: 0.80rem; }
        .dataTables_wrapper .dataTables_paginate .paginate_button { font-size: 0.80rem !important; }

        /* Footer */
        .footer { background: #1a3c5e; color: #8faec8; font-size: 0.75rem; padding: 6px 16px; text-align: center; }
    </style>

    @stack('styles')
</head>
<body>

{{-- Top Navbar --}}
<nav class="navbar navbar-dark" style="background:#0d2b47; padding: 5px 16px;">
    <span class="navbar-brand">
        <i class="bi bi-gem me-1"></i> Subha Enterprises
    </span>
    <div class="navbar-info d-flex align-items-center flex-wrap">
        <span><i class="bi bi-person-fill"></i>{{ session('user_name') }} ({{ session('user_type') }})</span>
        <span><i class="bi bi-building"></i>{{ session('br_name') }}</span>
        <span><i class="bi bi-calendar3"></i>{{ session('fin_year_name') }}</span>
        <span><i class="bi bi-calendar-date"></i>{{ \Carbon\Carbon::parse(session('login_date'))->format('d-M-Y') }}</span>
        <form action="{{ route('logout') }}" method="POST" class="d-inline ms-2">
            @csrf
            <button type="submit" class="btn btn-sm btn-outline-warning py-0 px-2">
                <i class="bi bi-box-arrow-right"></i> Logout
            </button>
        </form>
    </div>
</nav>

{{-- Main Menu Bar --}}
<nav class="navbar navbar-expand main-menu px-2 py-0">
    <div class="collapse navbar-collapse">
        <ul class="navbar-nav">

            {{-- Dashboard --}}
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                    <i class="bi bi-house-fill"></i> Home
                </a>
            </li>

            {{-- Masters --}}
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                    <i class="bi bi-grid-fill"></i> Masters
                </a>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="{{ route('masters.firm.index') }}"><i class="bi bi-building me-1"></i>Firm Info</a></li>
                    <li><a class="dropdown-item" href="{{ route('masters.branch.index') }}"><i class="bi bi-diagram-2 me-1"></i>Branch</a></li>
                    <li><a class="dropdown-item" href="{{ route('masters.category.index') }}"><i class="bi bi-tags me-1"></i>Category</a></li>
                    <li><a class="dropdown-item" href="{{ route('masters.uom.index') }}"><i class="bi bi-rulers me-1"></i>UOM</a></li>
                    <li><a class="dropdown-item" href="{{ route('masters.tax.index') }}"><i class="bi bi-percent me-1"></i>Tax</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="{{ route('masters.party.index') }}"><i class="bi bi-people me-1"></i>Party</a></li>
                    <li><a class="dropdown-item" href="{{ route('masters.product.index') }}"><i class="bi bi-gem me-1"></i>Product</a></li>
                    <li><a class="dropdown-item" href="{{ route('masters.design.index') }}"><i class="bi bi-palette me-1"></i>Design</a></li>
                    <li><hr class="dropdown-divider"></li>
                    @if(session('user_type') === 'ADMIN')
                    <li><a class="dropdown-item" href="{{ route('utilities.users.index') }}"><i class="bi bi-person-gear me-1"></i>User</a></li>
                    @endif
                    <li><a class="dropdown-item" href="{{ route('masters.stock.index') }}"><i class="bi bi-box-seam me-1"></i>Stock Opening</a></li>
                </ul>
            </li>

            {{-- Transactions --}}
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                    <i class="bi bi-arrow-left-right"></i> Transactions
                </a>
                <ul class="dropdown-menu">
                    <li><span class="dropdown-item-text text-muted fw-bold" style="font-size:0.75rem;">PURCHASE</span></li>
                    <li><a class="dropdown-item {{ request()->routeIs('transactions.purchase.*') ? 'active' : '' }}" href="{{ route('transactions.purchase.index') }}"><i class="bi bi-cart-plus me-1"></i>Purchase</a></li>
                    <li><a class="dropdown-item {{ request()->routeIs('transactions.purchase-return.*') ? 'active' : '' }}" href="{{ route('transactions.purchase-return.index') }}"><i class="bi bi-cart-dash me-1"></i>Purchase Return</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><span class="dropdown-item-text text-muted fw-bold" style="font-size:0.75rem;">SALES</span></li>
                    <li><a class="dropdown-item {{ request()->routeIs('transactions.sale.*') && request()->route('saleType') == 1 ? 'active' : '' }}" href="{{ route('transactions.sale.index', ['saleType' => 1]) }}"><i class="bi bi-receipt me-1"></i>Sale (Type 1 — Cash)</a></li>
                    <li><a class="dropdown-item {{ request()->routeIs('transactions.sale.*') && request()->route('saleType') == 2 ? 'active' : '' }}" href="{{ route('transactions.sale.index', ['saleType' => 2]) }}"><i class="bi bi-receipt me-1"></i>Sale (Type 2 — Credit)</a></li>
                    <li><a class="dropdown-item {{ request()->routeIs('transactions.sale-return.*') ? 'active' : '' }}" href="{{ route('transactions.sale-return.index') }}"><i class="bi bi-arrow-return-left me-1"></i>Sale Return</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><span class="dropdown-item-text text-muted fw-bold" style="font-size:0.75rem;">ORDERS</span></li>
                    <li><a class="dropdown-item {{ request()->routeIs('transactions.order.*') && request()->route('ordType') == 1 ? 'active' : '' }}" href="{{ route('transactions.order.index', ['ordType' => 1]) }}"><i class="bi bi-clipboard-check me-1"></i>Order (Type 1)</a></li>
                    <li><a class="dropdown-item {{ request()->routeIs('transactions.order.*') && request()->route('ordType') == 2 ? 'active' : '' }}" href="{{ route('transactions.order.index', ['ordType' => 2]) }}"><i class="bi bi-clipboard-check me-1"></i>Order (Type 2)</a></li>
                    <li><a class="dropdown-item" href="#"><i class="bi bi-arrow-left-right me-1"></i>Stock Transfer</a></li>
                </ul>
            </li>

            {{-- Reports --}}
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                    <i class="bi bi-bar-chart-fill"></i> Reports
                </a>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="#"><i class="bi bi-box me-1"></i>Stock Report</a></li>
                    <li><a class="dropdown-item" href="#"><i class="bi bi-cart me-1"></i>Purchase Report</a></li>
                    <li><a class="dropdown-item" href="#"><i class="bi bi-receipt me-1"></i>Sales Report</a></li>
                    <li><a class="dropdown-item" href="#"><i class="bi bi-arrow-return-left me-1"></i>Return Reports</a></li>
                    <li><a class="dropdown-item" href="#"><i class="bi bi-clipboard me-1"></i>Order Report</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="#"><i class="bi bi-people me-1"></i>Party Ledger</a></li>
                </ul>
            </li>

            {{-- Utilities --}}
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle {{ request()->is('utilities/*') ? 'active' : '' }}" href="#" data-bs-toggle="dropdown">
                    <i class="bi bi-gear-fill"></i> Utilities
                </a>
                <ul class="dropdown-menu">
                    @if(session('user_type') === 'ADMIN')
                    <li><a class="dropdown-item {{ request()->routeIs('utilities.users.*') ? 'active' : '' }}" href="{{ route('utilities.users.index') }}"><i class="bi bi-people-fill me-1"></i>User Management</a></li>
                    <li><a class="dropdown-item {{ request()->routeIs('utilities.financial-year.*') ? 'active' : '' }}" href="{{ route('utilities.financial-year.index') }}"><i class="bi bi-calendar-range me-1"></i>Financial Year</a></li>
                    <li><a class="dropdown-item {{ request()->routeIs('utilities.system-parameters*') ? 'active' : '' }}" href="{{ route('utilities.system-parameters') }}"><i class="bi bi-sliders me-1"></i>System Parameters</a></li>
                    <li><hr class="dropdown-divider"></li>
                    @endif
                    <li><a class="dropdown-item {{ request()->routeIs('utilities.change-password') ? 'active' : '' }}" href="{{ route('utilities.change-password') }}"><i class="bi bi-key me-1"></i>Change Password</a></li>
                </ul>
            </li>

        </ul>
    </div>
</nav>

{{-- Flash Messages --}}
<div class="content-wrapper">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
            <i class="bi bi-check-circle-fill me-1"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-1"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @yield('content')
</div>

<div class="footer">
    &copy; {{ date('Y') }} Subha Enterprises, Machilipatnam &mdash; Powered by Laravel 11
</div>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

@stack('scripts')
</body>
</html>
