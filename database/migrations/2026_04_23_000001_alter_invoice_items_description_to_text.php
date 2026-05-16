<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE invoice_items MODIFY description TEXT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE invoice_items MODIFY description VARCHAR(191) NULL');
    }
};
