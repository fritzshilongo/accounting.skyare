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
        Schema::table('users', function (Blueprint $table) {
            // Rename legacy Laravel columns to Skyare schema
            if (Schema::hasColumn('users', 'name') && !Schema::hasColumn('users', 'full_name')) {
                $table->renameColumn('name', 'full_name');
            } elseif (!Schema::hasColumn('users', 'full_name')) {
                $table->string('full_name')->nullable();
            }

            if (Schema::hasColumn('users', 'password') && !Schema::hasColumn('users', 'password_hash')) {
                $table->renameColumn('password', 'password_hash');
            } elseif (!Schema::hasColumn('users', 'password_hash')) {
                $table->string('password_hash')->nullable();
            }

            if (!Schema::hasColumn('users', 'company_id')) {
                $table->unsignedBigInteger('company_id')->nullable();
            }
            if (!Schema::hasColumn('users', 'role_key')) {
                $table->string('role_key')->default('user');
            }
            if (!Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'full_name')) {
                $table->dropColumn('full_name');
            }
            if (Schema::hasColumn('users', 'company_id')) {
                $table->dropColumn('company_id');
            }
            if (Schema::hasColumn('users', 'password_hash')) {
                $table->dropColumn('password_hash');
            }
            if (Schema::hasColumn('users', 'role_key')) {
                $table->dropColumn('role_key');
            }
            if (Schema::hasColumn('users', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });
    }
};
