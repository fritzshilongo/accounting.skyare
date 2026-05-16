<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Invoice;
use App\Models\Payment;

class InvoicePaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_payment_balance_and_status()
    {
        $invoice = Invoice::create([
            'company_id' => 1,
            'client_id' => 1,
            'client_name' => 'Test Client',
            'invoice_no' => 'INV-0001',
            'amount' => 0,
            'tax_amount' => 0,
            'total' => 1000.00,
            'status' => 'unpaid',
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
        ]);

        $this->assertEquals(1000.00, $invoice->balance);
        $this->assertEquals('unpaid', $invoice->status);

        // partial payment
        Payment::create([
            'invoice_id' => $invoice->invoice_id,
            'amount' => 200.00,
            'payment_date' => now()->toDateString(),
            'method' => 'cash',
        ]);

        $invoice->refresh();
        $this->assertEquals(800.00, $invoice->balance);
        $this->assertEquals('partial', $invoice->status);

        // remaining payment
        Payment::create([
            'invoice_id' => $invoice->invoice_id,
            'amount' => 800.00,
            'payment_date' => now()->toDateString(),
            'method' => 'cash',
        ]);

        $invoice->refresh();
        $this->assertEquals(0.00, $invoice->balance);
        $this->assertEquals('paid', $invoice->status);
    }

    public function test_overpayment_is_blocked()
    {
        $this->expectException(\Exception::class);

        $invoice = Invoice::create([
            'company_id' => 1,
            'client_id' => 1,
            'client_name' => 'Test Client',
            'invoice_no' => 'INV-0002',
            'amount' => 0,
            'tax_amount' => 0,
            'total' => 500.00,
            'status' => 'unpaid',
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
        ]);

        Payment::create([
            'invoice_id' => $invoice->invoice_id,
            'amount' => 600.00,
            'payment_date' => now()->toDateString(),
            'method' => 'cash',
        ]);
    }
}
