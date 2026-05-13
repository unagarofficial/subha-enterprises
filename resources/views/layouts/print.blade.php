<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Print')</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        body { font-size: 0.82rem; font-family: Arial, sans-serif; background: #fff; }

        .print-header   { text-align: center; border-bottom: 2px solid #000; padding-bottom: 8px; margin-bottom: 12px; }
        .print-header h4 { font-weight: 700; margin: 0; font-size: 1.1rem; }
        .print-header p  { margin: 1px 0; font-size: 0.78rem; }

        .section-title  { font-weight: 700; font-size: 0.78rem; text-transform: uppercase;
                          letter-spacing: 0.5px; color: #444; }

        .inv-table      { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .inv-table th   { background: #333; color: #fff; padding: 5px 8px;
                          font-size: 0.78rem; font-weight: 600; }
        .inv-table td   { padding: 4px 8px; border-bottom: 1px solid #ddd; font-size: 0.80rem; }
        .inv-table tfoot td { font-weight: 700; border-top: 2px solid #333; }

        .totals-box     { border: 1px solid #ccc; padding: 8px 12px; border-radius: 4px; }
        .totals-box .row div { padding: 2px 0; }
        .totals-box .total-row { font-weight: 700; border-top: 2px solid #333; padding-top: 4px; margin-top: 4px; }

        .words-box      { background: #f8f9fa; border: 1px solid #dee2e6; padding: 6px 10px;
                          border-radius: 4px; font-style: italic; margin-top: 8px; }

        .sign-area      { margin-top: 40px; }
        .sign-line      { border-top: 1px solid #333; width: 140px; padding-top: 4px; text-align: center; font-size:0.75rem; }

        .no-print       { display: block; }

        @media print {
            .no-print   { display: none !important; }
            body        { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .inv-table th { background: #333 !important; color: #fff !important; }
        }
    </style>
    @stack('styles')
</head>
<body class="p-3">

<div class="no-print mb-3 d-flex gap-2">
    <button onclick="window.print()" class="btn btn-primary btn-sm">
        <i class="bi bi-printer me-1"></i> Print
    </button>
    <button onclick="window.close()" class="btn btn-secondary btn-sm">Close</button>
</div>

@yield('content')

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</body>
</html>
