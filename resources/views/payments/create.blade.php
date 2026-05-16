@extends('layouts.app')

@section('title', 'Record Payment')

@section('content')
@php($currencySymbol = $_SESSION['user']['currency_symbol'] ?? 'N$')
<section class="hero-card">
    <h1 class="hero-title">Record Payment</h1>
    <p class="hero-copy">Apply cash against an invoice without exceeding its open balance.</p>
</section>

<section class="form-card">
    <form method="POST" action="/payments" class="form-grid two">
        @csrf
        <div class="span-full">
            <label for="invoice_id">Select Invoice (showing pending invoices only)</label>
            <select id="invoice_id" name="invoice_id" required>
                <option value="" data-balance="0" data-total="0" data-paid="0">Select invoice</option>
                @foreach($invoices as $invoice)
                    <option value="{{ $invoice->invoice_id }}"
                            data-balance="{{ number_format($invoice->balance, 2, '.', '') }}"
                            data-total="{{ number_format($invoice->total ?: $invoice->amount, 2, '.', '') }}"
                            data-paid="{{ number_format($invoice->paid_amount, 2, '.', '') }}"
                            data-client="{{ $invoice->client_name ?? $invoice->client->name ?? 'Client' }}"
                            data-due="{{ $invoice->due_date ?? '-' }}"
                            data-status="{{ ucwords(str_replace('_', ' ', $invoice->status)) }}">
                        {{ $invoice->invoice_no }} · {{ $invoice->client_name ?? $invoice->client->name ?? 'Client' }} · Balance: {{ $currencySymbol }}{{ number_format($invoice->balance, 2) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="span-full" id="invoice-details" style="display:none; background:var(--surface-strong, rgba(255,255,255,0.7)); border:1px solid var(--line, rgba(24,49,83,0.08)); border-radius:14px; padding:16px; margin-bottom:8px;">
            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap:12px;">
                <div>
                    <div style="font-size:11px; text-transform:uppercase; color:var(--ink-muted); letter-spacing:0.5px;">Client</div>
                    <div id="detail-client" style="font-weight:700; margin-top:2px;">-</div>
                </div>
                <div>
                    <div style="font-size:11px; text-transform:uppercase; color:var(--ink-muted); letter-spacing:0.5px;">Invoice Total</div>
                    <div id="detail-total" style="font-weight:700; margin-top:2px;">-</div>
                </div>
                <div>
                    <div style="font-size:11px; text-transform:uppercase; color:var(--ink-muted); letter-spacing:0.5px;">Amount Paid</div>
                    <div id="detail-paid" style="font-weight:700; margin-top:2px; color:var(--teal);">-</div>
                </div>
                <div>
                    <div style="font-size:11px; text-transform:uppercase; color:var(--ink-muted); letter-spacing:0.5px;">Balance Owed</div>
                    <div id="detail-balance" style="font-weight:700; margin-top:2px; color:var(--rose);">-</div>
                </div>
                <div>
                    <div style="font-size:11px; text-transform:uppercase; color:var(--ink-muted); letter-spacing:0.5px;">Due Date</div>
                    <div id="detail-due" style="font-weight:700; margin-top:2px;">-</div>
                </div>
                <div>
                    <div style="font-size:11px; text-transform:uppercase; color:var(--ink-muted); letter-spacing:0.5px;">Status</div>
                    <div id="detail-status" style="font-weight:700; margin-top:2px;">-</div>
                </div>
            </div>
        </div>

        <div>
            <label for="method">Payment Method</label>
            <select id="method" name="method" required>
                <option value="bank_transfer">Bank transfer</option>
                <option value="credit_card">Credit card</option>
                <option value="check">Check</option>
                <option value="cash">Cash</option>
            </select>
        </div>
        <div>
            <label for="amount">Amount to Pay</label>
            <input id="amount" type="number" step="0.01" min="0.01" name="amount" required placeholder="Enter payment amount">
            <small id="amount-hint" style="color:var(--ink-muted);font-size:12px;"></small>
        </div>
        <div class="toolbar-left span-full">
            <button type="submit" class="btn-primary">Save payment</button>
            <a href="/payments" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</section>

<script>
(function() {
    var cs = @json($currencySymbol);
    var sel = document.getElementById('invoice_id');
    var panel = document.getElementById('invoice-details');
    var amountInput = document.getElementById('amount');
    var hint = document.getElementById('amount-hint');

    function fmt(v) { return cs + Number(v).toFixed(2); }

    sel.addEventListener('change', function() {
        var opt = sel.options[sel.selectedIndex];
        var balance = parseFloat(opt.getAttribute('data-balance') || 0);
        if (!opt.value) { panel.style.display = 'none'; hint.textContent = ''; return; }
        panel.style.display = 'block';
        document.getElementById('detail-client').textContent = opt.getAttribute('data-client') || '-';
        document.getElementById('detail-total').textContent = fmt(opt.getAttribute('data-total') || 0);
        document.getElementById('detail-paid').textContent = fmt(opt.getAttribute('data-paid') || 0);
        document.getElementById('detail-balance').textContent = fmt(balance);
        document.getElementById('detail-due').textContent = opt.getAttribute('data-due') || '-';
        document.getElementById('detail-status').textContent = opt.getAttribute('data-status') || '-';
        amountInput.max = balance;
        hint.textContent = 'Max payable: ' + fmt(balance);
    });
})();
</script>
@endsection