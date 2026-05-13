<table class="inv-table">
    <thead>
        <tr>
            <th style="width:35px">Sl</th>
            <th>Item Name</th>
            <th class="text-end" style="width:80px">Qty</th>
            <th style="width:55px">UOM</th>
            <th class="text-end" style="width:90px">Rate</th>
            <th class="text-end" style="width:100px">Amount</th>
            <th style="width:120px">Narration</th>
        </tr>
    </thead>
    <tbody>
        @foreach($dtls as $d)
        <tr>
            <td class="text-center">{{ $d->sl_no }}</td>
            <td>{{ $d->mat_name }} <small class="text-muted">({{ $d->mat_code }})</small></td>
            <td class="text-end">{{ number_format($d->qty, 3) }}</td>
            <td>{{ $d->uom_name }}</td>
            <td class="text-end">{{ number_format($d->rate, 2) }}</td>
            <td class="text-end">{{ number_format($d->s_value, 2) }}</td>
            <td>{{ $d->narration }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
