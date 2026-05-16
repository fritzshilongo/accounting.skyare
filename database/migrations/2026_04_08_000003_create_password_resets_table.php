<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('password_resets')) {
            Schema::create('password_resets', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->string('token');
                $table->timestamp('expires_at')->nullable();
                $table->string('ip', 45)->nullable();
                $table->timestamp('used_at')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->index('user_id');
                $table->index('token');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('password_resets');
    }
};
