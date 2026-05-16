<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('company_preferences')) {
            return;
        }

        Schema::create('company_preferences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('preference_key', 100);
            $table->text('preference_value')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'preference_key'], 'company_pref_company_key_unique');
            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_preferences');
    }
};