@extends('layouts.app')

@section('title', 'Dashboard — Subha Enterprises')

@section('content')
<div class="row g-3 mb-4">
    {{-- Welcome Card --}}
    <div class="col-12">
        <div class="card border-0" style="background: linear-gradient(135deg, #0d2b47, #1a3c5e); color:#fff;">
            <div class="card-body d-flex align-items-center py-3 px-4">
                <div class="me-3">
                    <i class="bi bi-gem" style="font-size:2.2rem; color:#ffc107;"></i>
                </div>
                <div>
                    <h5 class="mb-0 fw-bold">Welcome, {{ session('user_name') }}!</h5>
                    <div class="mt-1" style="font-size:0.82rem; color:#8faec8;">
                        <span class="me-3"><i class="bi bi-building me-1 text-warning"></i>{{ session('br_name') }}</span>
                        <span class="me-3"><i class="bi bi-calendar3 me-1 text-warning"></i>F.Y. {{ session('fin_year_name') }}</span>
                        <span><i class="bi bi-calendar-date me-1 text-warning"></i>{{ \Carbon\Carbon::parse(session('login_date'))->format('d-M-Y') }}</span>
                    </div>
                </div>
                <div class="ms-auto text-end" style="font-size:0.78rem; color:#8faec8;">
                    <div>{{ session('user_type') == 'ADMIN' ? '🔑 Administrator' : '👤 User' }}</div>
                    <div class="mt-1">{{ now()->format('D, d M Y') }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Quick Access Cards --}}
<div class="row g-3">

    <div class="col-md-3 col-sm-6">
        <div class="card h-100 text-center p-3" style="cursor:pointer; border-top: 3px solid #1a3c5e;">
            <i class="bi bi-cart-plus" style="font-size:1.8rem; color:#1a3c5e;"></i>
            <div class="mt-2 fw-semibold" style="font-size:0.84rem;">Purchase</div>
            <div class="text-muted" style="font-size:0.75rem;">New purchase entry</div>
        </div>
    </div>

    <div class="col-md-3 col-sm-6">
        <div class="card h-100 text-center p-3" style="cursor:pointer; border-top: 3px solid #198754;">
            <i class="bi bi-receipt" style="font-size:1.8rem; color:#198754;"></i>
            <div class="mt-2 fw-semibold" style="font-size:0.84rem;">Sale</div>
            <div class="text-muted" style="font-size:0.75rem;">New sale invoice</div>
        </div>
    </div>

    <div class="col-md-3 col-sm-6">
        <div class="card h-100 text-center p-3" style="cursor:pointer; border-top: 3px solid #fd7e14;">
            <i class="bi bi-clipboard-check" style="font-size:1.8rem; color:#fd7e14;"></i>
            <div class="mt-2 fw-semibold" style="font-size:0.84rem;">Order</div>
            <div class="text-muted" style="font-size:0.75rem;">New order entry</div>
        </div>
    </div>

    <div class="col-md-3 col-sm-6">
        <div class="card h-100 text-center p-3" style="cursor:pointer; border-top: 3px solid #0dcaf0;">
            <i class="bi bi-box-seam" style="font-size:1.8rem; color:#0dcaf0;"></i>
            <div class="mt-2 fw-semibold" style="font-size:0.84rem;">Stock</div>
            <div class="text-muted" style="font-size:0.75rem;">View stock report</div>
        </div>
    </div>

</div>

{{-- Summary Row --}}
<div class="row g-3 mt-1">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center" style="background:#f4f6f9;">
                <i class="bi bi-info-circle me-2 text-primary"></i>
                <span>Session Information</span>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-md-4">
                        <table class="table table-sm table-borderless mb-0">
                            <tr>
                                <td class="text-muted" style="width:110px;">User</td>
                                <td><strong>{{ session('user_name') }}</strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Role</td>
                                <td><span class="badge {{ session('user_type') == 'ADMIN' ? 'bg-danger' : 'bg-secondary' }}">{{ session('user_type') }}</span></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-4">
                        <table class="table table-sm table-borderless mb-0">
                            <tr>
                                <td class="text-muted" style="width:110px;">Branch</td>
                                <td><strong>{{ session('br_name') }}</strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Branch Code</td>
                                <td>{{ session('br_code') }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-4">
                        <table class="table table-sm table-borderless mb-0">
                            <tr>
                                <td class="text-muted" style="width:110px;">Financial Year</td>
                                <td><strong>{{ session('fin_year_name') }}</strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Login Date</td>
                                <td>{{ \Carbon\Carbon::parse(session('login_date'))->format('d-M-Y') }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
