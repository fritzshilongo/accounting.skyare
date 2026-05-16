<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('credits', function (Blueprint $table) {
            if (!Schema::hasColumn('credits', 'written_off_at')) {
                $table->timestamp('written_off_at')->nullable()->after('write_off_amount');
            }
            if (!Schema::hasColumn('credits', 'written_off_by')) {
                $table->string('written_off_by')->nullable()->after('written_off_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('credits', function (Blueprint $table) {
            $table->dropColumn(['written_off_at', 'written_off_by']);
        });
    }
};
