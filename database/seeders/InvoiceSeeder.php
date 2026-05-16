<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use Database\Seeders\ClientSeeder;
use Illuminate\Support\Facades\Schema;

class InvoiceSeeder extends Seeder
{
    public function run()
    {
        $invoiceColumns = array_flip(Schema::getColumnListing('invoices'));
        $invoiceItemColumns = array_flip(Schema::getColumnListing('invoice_items'));

        $clientIds = ClientSeeder::$clientIds;
        $productIds = Product::pluck('product_id')->toArray();
        if (empty($productIds)) {
            throw new \Exception('No products found. Run ProductSeeder first.');
        }
        $products = Product::pluck('price', 'product_id');
        $vatRate = config('app.vat', 0.15);
        foreach (range(1, 10) as $i) {
            $clientId = $clientIds[array_rand($clientIds)];
            $invoicePayload = [
                'company_id' => 1,
                'client_id' => $clientId,
                'client_name' => sprintf('Client %02d', $clientId),
                'invoice_no' => sprintf('INV-%04d', $i),
                'amount' => 0,
                'tax_amount' => 0,
                'total' => 0,
                'status' => 'unpaid',
                'issue_date' => now()->subDays($i)->toDateString(),
                'due_date' => now()->addDays($i + 14)->toDateString(),
            ];
            $invoice = Invoice::create(array_intersect_key($invoicePayload, $invoiceColumns));

            $itemCount = rand(1, 5);
            $subtotal = 0;
            for ($j = 0; $j < $itemCount; $j++) {
                $productId = $productIds[array_rand($productIds)];
                $quantity = rand(1, 10);
                $unit_price = $products[$productId];
                $line_total = $quantity * $unit_price;
                $subtotal += $line_total;
                $itemPayload = [
                    'invoice_id' => $invoice->invoice_id,
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'unit_price' => $unit_price,
                    'line_total' => $line_total,
                ];
                InvoiceItem::create(array_intersect_key($itemPayload, $invoiceItemColumns));
            }
            $vat = round($subtotal * $vatRate, 2);
            $total = $subtotal + $vat;
            $invoice->update([
                'amount' => $subtotal,
                'tax_amount' => $vat,
                'total' => $total,
            ]);
        }
    }
}
