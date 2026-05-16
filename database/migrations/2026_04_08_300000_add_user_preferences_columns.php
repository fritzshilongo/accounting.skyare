<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'phone')) {
                $table->string('phone', 50)->nullable()->after('email');
            }
            if (! Schema::hasColumn('users', 'timezone')) {
                $table->string('timezone', 64)->default('Africa/Windhoek')->after('is_active');
            }
            if (! Schema::hasColumn('users', 'date_format')) {
                $table->string('date_format', 20)->default('Y-m-d')->after('timezone');
            }
            if (! Schema::hasColumn('users', 'currency_symbol')) {
                $table->string('currency_symbol', 10)->default('N$')->after('date_format');
            }
            if (! Schema::hasColumn('users', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable()->after('currency_symbol');
            }
            if (! Schema::hasColumn('users', 'last_login_ip')) {
                $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $cols = ['phone', 'timezone', 'date_format', 'currency_symbol', 'last_login_at', 'last_login_ip'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
