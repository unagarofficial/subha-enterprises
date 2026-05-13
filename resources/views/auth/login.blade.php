<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Subha Enterprises</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #0d2b47 0%, #1a3c5e 50%, #2a5a8e 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.88rem;
        }
        .login-box {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 420px;
            overflow: hidden;
        }
        .login-header {
            background: linear-gradient(135deg, #0d2b47, #1a3c5e);
            color: #fff;
            padding: 28px 30px 22px;
            text-align: center;
        }
        .login-header .brand-icon {
            font-size: 2.4rem;
            color: #ffc107;
            display: block;
            margin-bottom: 6px;
        }
        .login-header h4 { font-weight: 700; margin: 0; font-size: 1.1rem; letter-spacing: 0.5px; }
        .login-header p { margin: 4px 0 0; font-size: 0.78rem; color: #8faec8; }
        .login-body { padding: 28px 30px; }
        .form-label { font-weight: 500; color: #333; margin-bottom: 3px; }
        .form-control, .form-select {
            border-radius: 5px;
            font-size: 0.85rem;
            border-color: #cdd5df;
        }
        .form-control:focus, .form-select:focus {
            border-color: #1a3c5e;
            box-shadow: 0 0 0 0.18rem rgba(26,60,94,0.18);
        }
        .btn-login {
            background: linear-gradient(135deg, #0d2b47, #1a3c5e);
            border: none;
            color: #fff;
            width: 100%;
            padding: 10px;
            border-radius: 5px;
            font-size: 0.9rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            margin-top: 6px;
            transition: opacity 0.2s;
        }
        .btn-login:hover { opacity: 0.9; color: #fff; }
        .login-footer {
            background: #f4f6f9;
            border-top: 1px solid #eee;
            padding: 10px 30px;
            text-align: center;
            color: #888;
            font-size: 0.75rem;
        }
        .alert { font-size: 0.83rem; border-radius: 5px; padding: 8px 12px; }
        .input-group-text { background: #f4f6f9; border-color: #cdd5df; color: #555; }
    </style>
</head>
<body>

<div class="login-box">
    <div class="login-header">
        <i class="bi bi-gem brand-icon"></i>
        <h4>Subha Enterprises</h4>
        <p>Gold & Silver Jewelry Distribution</p>
    </div>

    <div class="login-body">
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-triangle-fill me-1"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle-fill me-1"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <form action="{{ route('login.post') }}" method="POST" autocomplete="off">
            @csrf

            {{-- Branch --}}
            <div class="mb-3">
                <label class="form-label"><i class="bi bi-building me-1 text-primary"></i>Branch</label>
                <select name="br_code" class="form-select @error('br_code') is-invalid @enderror" required>
                    <option value="">-- Select Branch --</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->br_code }}"
                            {{ old('br_code') == $branch->br_code ? 'selected' : '' }}>
                            {{ $branch->br_name }}
                            @if($branch->br_place) — {{ $branch->br_place }} @endif
                        </option>
                    @endforeach
                </select>
                @error('br_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            {{-- Financial Year --}}
            <div class="mb-3">
                <label class="form-label"><i class="bi bi-calendar3 me-1 text-primary"></i>Financial Year</label>
                <select name="fin_year_id" class="form-select @error('fin_year_id') is-invalid @enderror" required>
                    <option value="">-- Select Year --</option>
                    @foreach($financialYears as $fy)
                        <option value="{{ $fy->id }}"
                            {{ old('fin_year_id', $activeYearId) == $fy->id ? 'selected' : '' }}>
                            {{ $fy->year_name }}{{ $fy->is_active ? ' ★' : '' }}
                        </option>
                    @endforeach
                </select>
                @error('fin_year_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            {{-- User Name --}}
            <div class="mb-3">
                <label class="form-label"><i class="bi bi-person me-1 text-primary"></i>User Name</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                    <input type="text" name="user_name" class="form-control @error('user_name') is-invalid @enderror"
                           value="{{ old('user_name') }}" placeholder="Enter username" required autocomplete="username">
                    @error('user_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            {{-- Password --}}
            <div class="mb-3">
                <label class="form-label"><i class="bi bi-lock me-1 text-primary"></i>Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" name="password" id="passwordField"
                           class="form-control @error('password') is-invalid @enderror"
                           placeholder="Enter password" required autocomplete="current-password">
                    <button type="button" class="btn btn-outline-secondary" id="togglePwd" tabindex="-1">
                        <i class="bi bi-eye" id="eyeIcon"></i>
                    </button>
                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            {{-- Login Date --}}
            <div class="mb-4">
                <label class="form-label"><i class="bi bi-calendar-date me-1 text-primary"></i>Login Date</label>
                <input type="date" name="login_date"
                       class="form-control @error('login_date') is-invalid @enderror"
                       value="{{ old('login_date', date('Y-m-d')) }}" required>
                @error('login_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <button type="submit" class="btn-login">
                <i class="bi bi-box-arrow-in-right me-1"></i> LOGIN
            </button>
        </form>
    </div>

    <div class="login-footer">
        &copy; {{ date('Y') }} Subha Enterprises, Machilipatnam
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Toggle password visibility
    document.getElementById('togglePwd').addEventListener('click', function () {
        const pwd = document.getElementById('passwordField');
        const icon = document.getElementById('eyeIcon');
        if (pwd.type === 'password') {
            pwd.type = 'text';
            icon.classList.replace('bi-eye', 'bi-eye-slash');
        } else {
            pwd.type = 'password';
            icon.classList.replace('bi-eye-slash', 'bi-eye');
        }
    });

    // Password field focuses on Enter from username field
    document.querySelector('input[name="user_name"]').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') { e.preventDefault(); document.getElementById('passwordField').focus(); }
    });
</script>
</body>
</html>
