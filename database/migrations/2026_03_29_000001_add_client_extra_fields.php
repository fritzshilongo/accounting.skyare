<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('type')->default('individual')->after('client_id');
            $table->string('contact_person')->nullable()->after('name');
            $table->string('vat_number')->nullable()->after('address');
            $table->string('tax_number')->nullable()->after('vat_number');
            $table->string('registration_number')->nullable()->after('tax_number');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['type', 'contact_person', 'vat_number', 'tax_number', 'registration_number']);
        });
    }
};
