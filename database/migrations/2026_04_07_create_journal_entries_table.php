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
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id('entry_id');
            $table->unsignedBigInteger('company_id');
            $table->date('date');
            $table->string('reference');
            $table->text('description');
            $table->string('currency')->default('USD');
            $table->decimal('debit_amount', 15, 2)->default(0);
            $table->decimal('credit_amount', 15, 2)->default(0);
            $table->string('account_code')->nullable();
            $table->string('status')->default('draft');
            $table->timestamps();
            
            $table->foreign('company_id')->references('company_id')->on('companies')->onDelete('cascade');
            $table->index('company_id');
            $table->index('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};
