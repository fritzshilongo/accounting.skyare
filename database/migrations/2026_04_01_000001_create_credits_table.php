<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credits', function (Blueprint $table) {
            $table->id('credit_id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('credit_no')->unique();
            $table->string('customer_name');
            $table->decimal('amount', 14, 2)->default(0);
            $table->enum('interest_type', ['flat', 'monthly', 'daily'])->default('flat');
            $table->decimal('interest_percent', 10, 2)->default(0);
            $table->decimal('interest_amount', 14, 2)->default(0);
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->decimal('amount_paid', 14, 2)->default(0);
            $table->date('due_date')->nullable();
            $table->date('issue_date')->nullable();
            $table->date('last_payment_date')->nullable();
            $table->date('settlement_date')->nullable();
            $table->enum('status', ['ACTIVE', 'OVERDUE', 'PAID', 'BAD_DEBT'])->default('ACTIVE');
            $table->string('reason')->nullable();
            $table->string('write_off_reason')->nullable();
            $table->decimal('write_off_amount', 14, 2)->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('company_id')->on('companies')->onDelete('cascade');
            $table->foreign('customer_id')->references('customer_id')->on('customers')->onDelete('set null');
        });

        Schema::create('credit_payments', function (Blueprint $table) {
            $table->id('payment_id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('credit_id');
            $table->string('customer_name');
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('payment_method')->nullable();
            $table->date('payment_date');
            $table->string('reference')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('company_id')->on('companies')->onDelete('cascade');
            $table->foreign('credit_id')->references('credit_id')->on('credits')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_payments');
        Schema::dropIfExists('credits');
    }
};
