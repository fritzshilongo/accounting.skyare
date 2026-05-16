<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\Invoice;
use App\Core\RequestContext;
use App\Core\Database;
use App\Http\Controllers\ActivityFeed;
use App\Support\SchemaCompat;

class PaymentController extends Controller
{
    public function index(Request $request, RequestContext $context)
    {
        $companyId = (int) ($context->company()['company_id'] ?? 0) ?: 1;

        $query = Payment::with('invoice')
            ->whereHas('invoice', fn ($q) => $q->where('company_id', $companyId));

        // Search by invoice number or client name
        if ($search = $request->input('search')) {
            $invoiceNoColumn = SchemaCompat::invoiceNoColumn();
            $clientNameColumn = SchemaCompat::invoiceClientNameColumn();

            $query->whereHas('invoice', function ($q) use ($search, $invoiceNoColumn, $clientNameColumn) {
                $applied = false;

                if ($invoiceNoColumn !== null) {
                    $q->where($invoiceNoColumn, 'like', "%{$search}%");
                    $applied = true;
                }

                if ($clientNameColumn !== null) {
                    if ($applied) {
                        $q->orWhere($clientNameColumn, 'like', "%{$search}%");
                    } else {
                        $q->where($clientNameColumn, 'like', "%{$search}%");
                    }
                }
            });
        }

        // Filter by payment method
        if ($method = $request->input('method')) {
            $query->where('method', $method);
        }

        // Filter by date range
        if ($from = $request->input('from')) {
            $query->where('payment_date', '>=', $from);
        }
        if ($to = $request->input('to')) {
            $query->where('payment_date', '<=', $to);
        }

        $payments = $query->latest()->paginate(20)->withQueryString();

        return view('payments.index', compact('payments'));
    }

    public function create(RequestContext $context)
    {
        $companyId = (int) ($context->company()['company_id'] ?? 0) ?: 1;
        $invoices = Invoice::where('company_id', $companyId)
            ->whereNotIn('status', ['paid', 'finalised', 'finalized', 'cancelled'])
            ->get()
            ->filter(fn (Invoice $invoice) => $invoice->balance > 0)
            ->values();
        return view('payments.create', compact('invoices'));
    }

    public function store(Request $request, RequestContext $context, Database $db)
    {
        $companyId = (int) ($context->company()['company_id'] ?? 0) ?: 1;
        $validated = $request->validate([
            'invoice_id' => 'required|exists:invoices,invoice_id',
            'amount' => 'required|numeric|min:0.01',
            'method' => 'required|string|max:50',
        ]);

        // Ensure the invoice belongs to the current tenant
        $invoice = Invoice::where('company_id', $companyId)->findOrFail((int) $validated['invoice_id']);

        try {
            // Save payment and enforce invoice constraints via model events
            Payment::create([
                'invoice_id' => (int) $invoice->invoice_id,
                'amount' => (float) $validated['amount'],
                'payment_date' => now(),
                'method' => (string) $validated['method'],
            ]);
        } catch (\Throwable $e) {
            return back()->withErrors([
                'amount' => 'Payment could not be recorded: ' . $e->getMessage(),
            ])->withInput();
        }

        // Refresh invoice status/paid_at once payment is saved
        $invoice->refreshPaymentStatus();

        try {
            ActivityFeed::log($db, $context, 'recorded payment', 'payment', null, '$' . number_format((float) $validated['amount'], 2) . ' for Invoice #' . $invoice->invoice_no);
            ActivityFeed::notify($db, $context, 'payment_received', 'Payment Received', '$' . number_format((float) $validated['amount'], 2) . ' for ' . ($invoice->client_name ?? 'Invoice #' . $invoice->invoice_no), '/payments', 'fa-wallet');
        } catch (\Throwable $e) {}

        return redirect('/payments')->with('success', 'Payment recorded successfully.');
    }

    public function show($id, RequestContext $context)
    {
        $companyId = (int) ($context->company()['company_id'] ?? 0) ?: 1;
        $payment = Payment::with('invoice')
            ->whereHas('invoice', fn ($q) => $q->where('company_id', $companyId))
            ->findOrFail($id);
        return view('payments.show', compact('payment'));
    }
}
