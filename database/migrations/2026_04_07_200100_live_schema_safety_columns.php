<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table): void {
                if (!Schema::hasColumn('users', 'company_id')) {
                    $table->unsignedBigInteger('company_id')->nullable();
                    $table->index('company_id', 'idx_users_company_id');
                }
                if (!Schema::hasColumn('users', 'full_name')) {
                    $table->string('full_name')->nullable();
                }
                if (!Schema::hasColumn('users', 'password_hash')) {
                    $table->string('password_hash')->nullable();
                }
                if (!Schema::hasColumn('users', 'role_key')) {
                    $table->string('role_key', 50)->default('admin');
                }
                if (!Schema::hasColumn('users', 'is_active')) {
                    $table->boolean('is_active')->default(true);
                }
            });

            if (Schema::hasColumn('users', 'full_name') && Schema::hasColumn('users', 'name')) {
                DB::statement("UPDATE users SET full_name = name WHERE (full_name IS NULL OR full_name = '') AND name IS NOT NULL");
            }
            if (Schema::hasColumn('users', 'password_hash') && Schema::hasColumn('users', 'password')) {
                DB::statement("UPDATE users SET password_hash = password WHERE (password_hash IS NULL OR password_hash = '') AND password IS NOT NULL");
            }
        }

        if (Schema::hasTable('clients') && !Schema::hasColumn('clients', 'company_id')) {
            Schema::table('clients', function (Blueprint $table): void {
                $table->unsignedBigInteger('company_id')->nullable();
                $table->index('company_id', 'idx_clients_company_id');
            });
        }

        if (Schema::hasTable('audit_logs') && !Schema::hasColumn('audit_logs', 'company_id')) {
            Schema::table('audit_logs', function (Blueprint $table): void {
                $table->unsignedBigInteger('company_id')->nullable();
                $table->index('company_id', 'idx_audit_logs_company_id');
            });
        }
    }

    public function down(): void
    {
        // Intentionally non-destructive for production safety.
    }
};
