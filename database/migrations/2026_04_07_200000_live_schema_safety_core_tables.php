<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('password_resets')) {
            Schema::create('password_resets', function (Blueprint $table): void {
                $table->id('reset_id');
                $table->unsignedBigInteger('user_id');
                $table->string('token', 255);
                $table->dateTime('expires_at');
                $table->dateTime('used_at')->nullable();
                $table->string('ip', 45)->nullable();
                $table->timestamp('created_at')->nullable()->useCurrent();

                $table->index('user_id', 'idx_password_resets_user_id');
                $table->index('token', 'idx_password_resets_token');
            });
        }

        if (!Schema::hasTable('role_permissions')) {
            Schema::create('role_permissions', function (Blueprint $table): void {
                $table->id('permission_id');
                $table->unsignedBigInteger('company_id');
                $table->string('role_key', 50);
                $table->string('module_key', 100);
                $table->boolean('can_view')->default(true);
                $table->boolean('can_create')->default(false);
                $table->boolean('can_edit')->default(false);
                $table->boolean('can_delete')->default(false);
                $table->timestamp('created_at')->nullable()->useCurrent();
                $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();

                $table->unique(['company_id', 'role_key', 'module_key'], 'uq_role_module');
            });
        }

        if (!Schema::hasTable('inventory_movements')) {
            Schema::create('inventory_movements', function (Blueprint $table): void {
                $table->id('movement_id');
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('product_id');
                $table->string('movement_type', 50);
                $table->decimal('quantity', 14, 2);
                $table->decimal('qty_before', 14, 2)->default(0);
                $table->decimal('qty_after', 14, 2)->default(0);
                $table->string('note', 255)->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamp('created_at')->nullable()->useCurrent();

                $table->index('company_id', 'idx_inventory_movements_company');
                $table->index('product_id', 'idx_inventory_movements_product');
            });
        }
    }

    public function down(): void
    {
        // Intentionally non-destructive for production safety.
    }
};
