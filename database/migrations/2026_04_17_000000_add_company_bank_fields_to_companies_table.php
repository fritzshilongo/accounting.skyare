<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (!Schema::hasColumn('companies', 'bank_account_type')) {
                $table->string('bank_account_type')->nullable()->after('bank_account_number');
            }
            if (!Schema::hasColumn('companies', 'bank_branch_code')) {
                $table->string('bank_branch_code')->nullable()->after('bank_account_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (Schema::hasColumn('companies', 'bank_branch_code')) {
                $table->dropColumn('bank_branch_code');
            }
            if (Schema::hasColumn('companies', 'bank_account_type')) {
                $table->dropColumn('bank_account_type');
            }
        });
    }
};
