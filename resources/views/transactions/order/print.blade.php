<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Estimation Bill — Order #{{ $hdr->ord_no }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 12px; color: #000; background: #fff; }
        .page { width: 100%; max-width: 750px; margin: 0 auto; padding: 18px 20px; }

        /* Header */
        .firm-header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 8px; margin-bottom: 8px; }
        .firm-name   { font-size: 20px; font-weight: 800; letter-spacing: 1px; text-transform: uppercase; }
        .firm-addr   { font-size: 11px; color: #444; margin-top: 2px; }
        .doc-title   { text-align: center; font-size: 15px; font-weight: 700; letter-spacing: 2px;
                       border: 2px solid #333; padding: 5px 0; margin: 10px 0; background: #f5f5f5; }

        /* Info Row */
        .info-grid  { display: flex; justify-content: space-between; margin-bottom: 10px; }
        .info-left  { flex: 1; }
        .info-right { text-align: right; }
        .info-label { color: #555; font-size: 11px; }
        .info-val   { font-weight: 600; font-size: 12px; }

        /* Customer box */
        .customer-box { border: 1px solid #bbb; padding: 7px 10px; margin-bottom: 10px; border-radius: 3px; }
        .customer-box .lbl { font-size: 10px; color: #666; text-transform: uppercase; letter-spacing: 0.5px; }
        .customer-box .val { font-weight: 700; font-size: 13px; }

        /* Items table */
        table.items { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table.items th { background: #1a3c5e; color: #fff; padding: 6px 8px; text-align: center; font-size: 11px; border: 1px solid #1a3c5e; }
        table.items td { padding: 5px 8px; border: 1px solid #ccc; font-size: 11px; }
        table.items td.center { text-align: center; }
        table.items td.right  { text-align: right; }
        table.items tr.total  { background: #f0f4f8; font-weight: 700; }

        /* Note */
        .note-box { border: 1px dashed #c00; padding: 6px 10px; margin-bottom: 14px; border-radius: 3px;
                    color: #c00; font-size: 11px; text-align: center; font-style: italic; }

        /* Signature */
        .sign-row { display: flex; justify-content: space-between; margin-top: 30px; }
        .sign-box { text-align: center; min-width: 140px; }
        .sign-line { border-top: 1px solid #333; padding-top: 4px; font-size: 10px; color: #555; margin-top: 30px; }

        @media print {
            body { margin: 0; }
            .no-print { display: none; }
            .page { padding: 10px 14px; }
        }
    </style>
</head>
<body>

<div class="no-print" style="text-align:center; padding:10px;">
    <button onclick="window.print()" style="padding:6px 20px; background:#1a3c5e; color:#fff; border:none; border-radius:4px; cursor:pointer;">
        &#128438; Print
    </button>
    <button onclick="window.close()" style="padding:6px 14px; margin-left:8px; border:1px solid #ccc; border-radius:4px; cursor:pointer;">
        Close
    </button>
</div>

<div class="page">

    {{-- Firm Letterhead --}}
    <div class="firm-header">
        <div class="firm-name">{{ $firm?->firm_name ?? 'Subha Enterprises' }}</div>
        @if($firm?->address)
        <div class="firm-addr">{{ $firm->address }}{{ $firm->place ? ', '.$firm->place : '' }}</div>
        @endif
        @if($firm?->phone || $firm?->mobile)
        <div class="firm-addr">
            @if($firm->phone) Ph: {{ $firm->phone }} @endif
            @if($firm->mobile) &nbsp;|&nbsp; Mob: {{ $firm->mobile }} @endif
        </div>
        @endif
    </div>

    {{-- Document Title --}}
    <div class="doc-title">ESTIMATION BILL</div>

    {{-- Doc Info --}}
    <div class="info-grid">
        <div class="info-left">
            <span class="info-label">Order No: </span>
            <span class="info-val">#{{ $hdr->ord_no }}</span>
            &nbsp;&nbsp;
            <span class="info-label">Type: </span>
            <span class="info-val">{{ $ordType }}</span>
        </div>
        <div class="info-right">
            <span class="info-label">Date: </span>
            <span class="info-val">{{ \Carbon\Carbon::parse($hdr->ord_date)->format('d-M-Y') }}</span>
        </div>
    </div>

    {{-- Customer Details --}}
    <div class="customer-box">
        <div class="lbl">Customer</div>
        <div class="val">{{ $hdr->party_name }}</div>
        @if($hdr->party_address)
        <div style="font-size:11px; margin-top:2px;">{{ $hdr->party_address }}</div>
        @endif
        @if($hdr->party_place)
        <div style="font-size:11px;">{{ $hdr->party_place }}{{ $hdr->party_state ? ', '.$hdr->party_state : '' }}</div>
        @endif
    </div>

    {{-- Items Table --}}
    @php
        $total = 0;
    @endphp
    <table class="items">
        <thead>
            <tr>
                <th style="width:32px">Sl</th>
                <th>Item Description</th>
                <th style="width:65px">Qty</th>
                <th style="width:45px">UOM</th>
                <th style="width:70px">Rate (₹)</th>
                <th style="width:80px">Amount (₹)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($dtls as $i => $d)
            @php
                $qty    = (float) $d->ord_qty;
                $rate   = (float) $d->rate;
                $amount = round($qty * $rate, 2);
                $total += $amount;
            @endphp
            <tr>
                <td class="center">{{ $i + 1 }}</td>
                <td>
                    {{ $d->mat_name }}
                    @if($d->narration)
                    <br><small style="color:#666;">{{ $d->narration }}</small>
                    @endif
                </td>
                <td class="center">{{ number_format($qty, 3) }}</td>
                <td class="center">{{ $d->uom_name }}</td>
                <td class="right">{{ number_format($rate, 2) }}</td>
                <td class="right">{{ number_format($amount, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total">
                <td colspan="5" style="text-align:right; padding-right:10px; font-size:12px;">Total Amount:</td>
                <td class="right" style="font-size:13px;">₹ {{ number_format($total, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    {{-- Note --}}
    <div class="note-box">
        &#9888; This is an estimate only and is <strong>not a tax invoice</strong>. Actual prices may vary.
    </div>

    {{-- Signature --}}
    <div class="sign-row">
        <div class="sign-box">
            <div class="sign-line">Date: {{ \Carbon\Carbon::parse($hdr->ord_date)->format('d-M-Y') }}</div>
        </div>
        <div class="sign-box">
            <div class="sign-line">Authorized Signature</div>
        </div>
    </div>

</div>

</body>
</html>
