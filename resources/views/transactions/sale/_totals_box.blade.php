<div class="totals-box" style="font-size:0.82rem;">
    <div class="row">
        <div class="col-6 text-muted">Gross Amount</div>
        <div class="col-6 text-end">{{ number_format($hdr->gross, 2) }}</div>
    </div>
    @if($hdr->tax_rate > 0)
    <div class="row">
        <div class="col-6 text-muted">Tax ({{ number_format($hdr->tax_rate, 2) }}%)</div>
        <div class="col-6 text-end">{{ number_format($hdr->tax_amount, 2) }}</div>
    </div>
    @if($isInState)
    <div class="row" style="font-size:0.75rem; color:#666;">
        <div class="col-6 text-muted ps-3">CGST ({{ number_format($hdr->tax_rate/2, 2) }}%)</div>
        <div class="col-6 text-end">{{ number_format($halfTax, 2) }}</div>
    </div>
    <div class="row" style="font-size:0.75rem; color:#666;">
        <div class="col-6 text-muted ps-3">SGST ({{ number_format($hdr->tax_rate/2, 2) }}%)</div>
        <div class="col-6 text-end">{{ number_format($halfTax, 2) }}</div>
    </div>
    @else
    <div class="row" style="font-size:0.75rem; color:#666;">
        <div class="col-6 text-muted ps-3">IGST ({{ number_format($hdr->tax_rate, 2) }}%)</div>
        <div class="col-6 text-end">{{ number_format($hdr->tax_amount, 2) }}</div>
    </div>
    @endif
    @endif
    <div class="row total-row">
        <div class="col-6 fw-bold">NET AMOUNT</div>
        <div class="col-6 text-end fw-bold">₹ {{ number_format($hdr->nett, 2) }}</div>
    </div>
</div>
