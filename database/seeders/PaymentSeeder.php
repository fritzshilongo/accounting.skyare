<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Payment;

class PaymentSeeder extends Seeder
{
    public function run()
    {
        $invoices = \App\Models\Invoice::where('total', '>', 0)->get();

        if ($invoices->isEmpty()) {
            return;
        }

        foreach (range(1, 20) as $i) {
            $availableInvoices = $invoices->filter(fn ($inv) => $inv->balance > 0);
            if ($availableInvoices->isEmpty()) {
                break;
            }

            $invoice = $availableInvoices->random();
            $balance = $invoice->balance;
            $amount = min($balance, mt_rand(1, (int) max(1, floor($balance))));

            try {
                \App\Models\Payment::create([
                    'invoice_id' => $invoice->invoice_id,
                    'amount' => $amount,
                    'payment_date' => now()->toDateString(),
                    'method' => ['cash', 'credit_card', 'bank_transfer'][array_rand(['cash', 'credit_card', 'bank_transfer'])],
                ]);
            } catch (\Exception $e) {
                continue;
            }

            $invoice->refresh();
            if ($invoice->balance <= 0) {
                $invoices = $invoices->filter(fn ($inv) => $inv->invoice_id !== $invoice->invoice_id);
            }
        }
    }
}
