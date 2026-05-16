<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Client;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class InvoiceController extends Controller
{
    public function index()
    {
        $today = Carbon::now();

        $invoices = Invoice::with('client')
            ->orderBy('invoice_id', 'desc')
            ->get()
            ->map(function ($inv) use ($today) {

                $inv->aging = 0;
                $inv->is_overdue = false;

                if (!empty($inv->due_date) && $inv->status !== 'paid') {
                    try {
                        $dueDate = Carbon::parse($inv->due_date);

                        if ($dueDate->lt($today)) {
                            $inv->aging = $today->diffInDays($dueDate);
                            $inv->is_overdue = true;
                        }
                    } catch (\Exception $e) {}
                }

                return $inv;
            });

        return view('invoices.index', compact('invoices'));
    }

    public function create()
    {
        $clients = Client::all();

        return view('invoices.create', compact('clients'));
    }

    public function store(Request $request)
    {
        $last = Invoice::orderBy('invoice_id', 'desc')->first();

        $next = $last && $last->invoice_no
            ? (int) str_replace('INV-', '', $last->invoice_no) + 1
            : 1;

        $invoiceNo = 'INV-' . str_pad($next, 3, '0', STR_PAD_LEFT);

        $invoice = Invoice::create([
            'company_id' => 1,
            'client_id' => $request->customer_id,
            'client_name' => $request->client_name ?: '',
            'invoice_no' => $invoiceNo,
            'amount' => 0,
            'status' => $request->status ?: 'draft',
            'issue_date' => $request->issue_date ?: now()->toDateString(),
            'due_date' => $request->due_date ?: now()->addDays(7)->toDateString(),
        ]);

        if ($request->filled('product_id')) {
            $quantity = max(1, (int) $request->quantity);
            $product = Product::find($request->product_id);
            $lineTotal = (float) $request->amount;
            $unitPrice = $quantity > 0 ? ($lineTotal / $quantity) : 0.0;

            InvoiceItem::create([
                'invoice_id' => $invoice->invoice_id,
                'company_id' => 1,
                'product_id' => $request->product_id,
                'description' => $product ? ($product->description ?? '') : '',
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
            ]);

            $this->recalculate($invoice->invoice_id);
        } else {
            DB::table('invoices')
                ->where('invoice_id', $invoice->invoice_id)
                ->update(['amount' => (float) $request->amount]);
        }

        return redirect('/invoices/' . $invoice->invoice_id);
    }

    public function show($id)
    {
        $invoice = Invoice::with(['items', 'client'])->findOrFail($id);

        return view('invoices.show', compact('invoice'));
    }

    public function addItem($id, Request $request)
    {
        InvoiceItem::create([
            'invoice_id' => $id,
            'company_id' => 1,
            'description' => $request->description,
            'quantity' => $request->quantity,
            'unit_price' => $request->unit_price,
            'line_total' => $request->quantity * $request->unit_price,
        ]);

        $this->recalculate($id);

        return back();
    }

    public function updateStatus($id, $status)
    {
        DB::table('invoices')
            ->where('invoice_id', $id)
            ->update([
                'status' => $status,
                'paid_at' => $status === 'paid' ? now() : null
            ]);

        return back();
    }

    public function destroy($id)
    {
        InvoiceItem::where('invoice_id', $id)->delete();
        Invoice::where('invoice_id', $id)->delete();

        return redirect('/invoices');
    }

    public function pdf($id)
    {
        $invoice = Invoice::with(['items', 'client'])->findOrFail($id);

        $pdf = Pdf::loadView('invoices.pdf', compact('invoice'));

        return $pdf->download($invoice->invoice_no . '.pdf');
    }

    private function recalculate($id)
    {
        $subtotal = InvoiceItem::where('invoice_id', $id)->sum('line_total');
        $vat = $subtotal * 0.15;
        $total = $subtotal + $vat;

        DB::table('invoices')
            ->where('invoice_id', $id)
            ->update([
                'amount' => $total,
                'tax_amount' => $vat
            ]);
    }
}