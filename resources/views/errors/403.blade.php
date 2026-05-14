<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 — Access Denied | Subha Enterprises</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: #f4f6f9; display: flex; align-items: center; justify-content: center; min-height: 100vh; font-family: Arial, sans-serif; }
        .error-box { text-align: center; max-width: 460px; padding: 40px 30px; background: #fff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.10); }
        .error-code { font-size: 6rem; font-weight: 800; color: #dc3545; line-height: 1; }
        .error-icon { font-size: 3rem; color: #dc3545; }
        .error-title { font-size: 1.3rem; font-weight: 700; color: #1a3c5e; margin: 12px 0 8px; }
        .error-msg { color: #6c757d; font-size: 0.88rem; margin-bottom: 24px; }
    </style>
</head>
<body>
    <div class="error-box">
        <div class="error-icon"><i class="bi bi-shield-lock-fill"></i></div>
        <div class="error-code">403</div>
        <div class="error-title">Access Denied</div>
        <p class="error-msg">You do not have permission to access this page. Please contact your administrator.</p>
        <a href="{{ url('/dashboard') }}" class="btn btn-primary px-4">
            <i class="bi bi-house-fill me-1"></i> Go to Dashboard
        </a>
        <div class="mt-3">
            <a href="javascript:history.back()" class="text-secondary text-decoration-none" style="font-size:0.82rem;">
                <i class="bi bi-arrow-left me-1"></i> Go Back
            </a>
        </div>
        <div class="mt-4" style="font-size:0.75rem; color:#adb5bd;">Subha Enterprises &mdash; Gold &amp; Silver Jewellery</div>
    </div>
</body>
</html>
