<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ensure legacy/live schemas include company_id on tenant-scoped tables.
     */
    public function up(): void
    {
        $tables = [
            'clients',
            'products',
            'invoices',
            'estimates',
            'payments',
            'inventory_movements',
            'expenses',
            'journal_entries',
            'customers',
            'audit_logs',
            'credits',
            'invoice_items',
            'estimate_items',
        ];

        foreach ($tables as $tableName) {
            if (!Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'company_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->unsignedBigInteger('company_id')->nullable();
                $table->index('company_id');
            });
        }
    }

    public function down(): void
    {
        // Intentionally left empty to avoid destructive rollbacks on production data.
    }
};
