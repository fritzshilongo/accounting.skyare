<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('exchange_rates')) {
            Schema::create('exchange_rates', function (Blueprint $table) {
                $table->id('rate_id');
                $table->unsignedBigInteger('company_id');
                $table->string('from_currency', 3);
                $table->string('to_currency', 3);
                $table->decimal('rate', 15, 6);
                $table->date('effective_date');
                $table->timestamps();

                $table->foreign('company_id')->references('company_id')->on('companies')->onDelete('cascade');
                $table->unique(['company_id', 'from_currency', 'to_currency', 'effective_date'], 'ex_rates_company_currency_date_unique');
                $table->index('company_id');
            });

            return;
        }

        Schema::table('exchange_rates', function (Blueprint $table) {
            $table->unique(['company_id', 'from_currency', 'to_currency', 'effective_date'], 'ex_rates_company_currency_date_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};
