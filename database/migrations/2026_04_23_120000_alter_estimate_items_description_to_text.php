<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE estimate_items MODIFY description TEXT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE estimate_items MODIFY description VARCHAR(255) NULL');
    }
};
