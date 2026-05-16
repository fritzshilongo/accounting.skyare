<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('licenses', function (Blueprint $table) {
            $table->id('license_id');
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('license_key');
            $table->string('company_name')->nullable();
            $table->string('domain');
            $table->string('status')->default('active');
            $table->string('plan')->default('professional');
            $table->date('expiry_date')->nullable();
            $table->date('valid_until')->nullable();
            $table->timestamp('last_verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('licenses');
    }
};
