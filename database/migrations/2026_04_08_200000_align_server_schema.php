<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Align pre-existing server tables with the expected schema.
 *
 * The products, invoices, and estimates tables were created before the
 * migration system and are missing columns the application depends on.
 * Every column addition is guarded with hasColumn() so this migration
 * is safe to re-run and safe on a fresh install where the CREATE TABLE
 * migrations already added the columns.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── products ───────────────────────────────────────────────
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                if (! Schema::hasColumn('products', 'company_id')) {
                    $table->unsignedBigInteger('company_id')->nullable()->after('product_id');
                    $table->index('company_id', 'idx_products_company_id');
                }
                if (! Schema::hasColumn('products', 'sku')) {
                    $table->string('sku')->nullable()->after('company_id');
                }
                if (! Schema::hasColumn('products', 'name') && Schema::hasColumn('products', 'product_name')) {
                    $table->string('name')->nullable()->after('sku');
                }
                if (! Schema::hasColumn('products', 'name') && ! Schema::hasColumn('products', 'product_name')) {
                    $table->string('name')->nullable()->after('sku');
                }
                if (! Schema::hasColumn('products', 'price') && Schema::hasColumn('products', 'unit_price')) {
                    $table->decimal('price', 10, 2)->default(0)->after('description');
                }
                if (! Schema::hasColumn('products', 'price') && ! Schema::hasColumn('products', 'unit_price')) {
                    $table->decimal('price', 10, 2)->default(0)->after('description');
                }
                if (! Schema::hasColumn('products', 'type')) {
                    $table->string('type')->default('product')->after('price');
                }
                if (! Schema::hasColumn('products', 'stock_control_type')) {
                    $table->string('stock_control_type')->default('STOCK_CONTROLLED')->after('type');
                }
                if (! Schema::hasColumn('products', 'stock_qty')) {
                    $table->decimal('stock_qty', 14, 2)->default(0)->after('stock_control_type');
                }
                if (! Schema::hasColumn('products', 'is_active')) {
                    $table->boolean('is_active')->default(true)->after('stock_qty');
                }
                if (! Schema::hasColumn('products', 'created_at')) {
                    $table->timestamp('created_at')->nullable();
                }
                if (! Schema::hasColumn('products', 'updated_at')) {
                    $table->timestamp('updated_at')->nullable();
                }
            });

            // Copy legacy column data into new columns where applicable
            if (Schema::hasColumn('products', 'product_name') && Schema::hasColumn('products', 'name')) {
                \Illuminate\Support\Facades\DB::statement(
                    'UPDATE products SET name = product_name WHERE name IS NULL AND product_name IS NOT NULL'
                );
            }
            if (Schema::hasColumn('products', 'unit_price') && Schema::hasColumn('products', 'price')) {
                \Illuminate\Support\Facades\DB::statement(
                    'UPDATE products SET price = unit_price WHERE (price = 0 OR price IS NULL) AND unit_price IS NOT NULL'
                );
            }
        }

        // ── invoices ───────────────────────────────────────────────
        if (Schema::hasTable('invoices')) {
            Schema::table('invoices', function (Blueprint $table) {
                if (! Schema::hasColumn('invoices', 'company_id')) {
                    $table->unsignedBigInteger('company_id')->nullable()->after('invoice_id');
                    $table->index('company_id', 'idx_invoices_company_id');
                }
                if (! Schema::hasColumn('invoices', 'client_id')) {
                    $table->unsignedBigInteger('client_id')->nullable()->after('company_id');
                }
                if (! Schema::hasColumn('invoices', 'client_name')) {
                    $table->string('client_name')->nullable()->after('client_id');
                }
                if (! Schema::hasColumn('invoices', 'invoice_no')) {
                    $table->string('invoice_no')->nullable()->after('client_name');
                }
                if (! Schema::hasColumn('invoices', 'tax_rate')) {
                    $table->decimal('tax_rate', 5, 2)->default(0)->after('amount');
                }
                if (! Schema::hasColumn('invoices', 'tax_amount')) {
                    $table->decimal('tax_amount', 10, 2)->default(0)->after('tax_rate');
                }
                if (! Schema::hasColumn('invoices', 'total')) {
                    $table->decimal('total', 10, 2)->default(0)->after('tax_amount');
                }
                if (! Schema::hasColumn('invoices', 'issue_date')) {
                    $table->date('issue_date')->nullable()->after('total');
                }
                if (! Schema::hasColumn('invoices', 'due_date')) {
                    $table->date('due_date')->nullable()->after('issue_date');
                }
                if (! Schema::hasColumn('invoices', 'paid_at')) {
                    $table->timestamp('paid_at')->nullable();
                }
                if (! Schema::hasColumn('invoices', 'created_at')) {
                    $table->timestamp('created_at')->nullable();
                }
                if (! Schema::hasColumn('invoices', 'updated_at')) {
                    $table->timestamp('updated_at')->nullable();
                }
            });

            // Back-fill total from amount where total is zero
            \Illuminate\Support\Facades\DB::statement(
                'UPDATE invoices SET total = amount WHERE (total = 0 OR total IS NULL) AND amount > 0'
            );
        }

        // ── estimates ──────────────────────────────────────────────
        if (Schema::hasTable('estimates')) {
            Schema::table('estimates', function (Blueprint $table) {
                if (! Schema::hasColumn('estimates', 'company_id')) {
                    $table->unsignedBigInteger('company_id')->nullable()->after('estimate_id');
                    $table->index('company_id', 'idx_estimates_company_id');
                }
                if (! Schema::hasColumn('estimates', 'client_id')) {
                    $table->unsignedBigInteger('client_id')->nullable()->after('company_id');
                }
                if (! Schema::hasColumn('estimates', 'customer_id')) {
                    $table->unsignedBigInteger('customer_id')->nullable()->after('client_id');
                }
                if (! Schema::hasColumn('estimates', 'product_id')) {
                    $table->unsignedBigInteger('product_id')->nullable()->after('customer_id');
                }
                if (! Schema::hasColumn('estimates', 'client_name')) {
                    $table->string('client_name')->nullable()->after('product_id');
                }
                if (! Schema::hasColumn('estimates', 'tax_amount')) {
                    $table->decimal('tax_amount', 10, 2)->default(0)->after('amount');
                }
                if (! Schema::hasColumn('estimates', 'total')) {
                    $table->decimal('total', 10, 2)->default(0)->after('tax_amount');
                }
                if (! Schema::hasColumn('estimates', 'created_at')) {
                    $table->timestamp('created_at')->nullable();
                }
                if (! Schema::hasColumn('estimates', 'updated_at')) {
                    $table->timestamp('updated_at')->nullable();
                }
            });

            // Back-fill total from amount where total is zero
            \Illuminate\Support\Facades\DB::statement(
                'UPDATE estimates SET total = amount WHERE (total = 0 OR total IS NULL) AND amount > 0'
            );
        }
    }

    public function down(): void
    {
        // Not dropping columns to avoid data loss on rollback
    }
};
