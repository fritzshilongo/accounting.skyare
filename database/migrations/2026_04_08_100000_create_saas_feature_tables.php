<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── User Invitations ──
        if (!Schema::hasTable('user_invitations')) {
            Schema::create('user_invitations', function (Blueprint $table) {
                $table->id('invitation_id');
                $table->unsignedBigInteger('company_id');
                $table->string('email');
                $table->string('role_key')->default('user');
                $table->string('token', 64)->unique();
                $table->unsignedBigInteger('invited_by')->nullable();
                $table->timestamp('accepted_at')->nullable();
                $table->timestamp('expires_at');
                $table->timestamps();
                $table->index(['company_id', 'email']);
            });
        }

        // ── Recurring Invoice Templates ──
        if (!Schema::hasTable('recurring_invoices')) {
            Schema::create('recurring_invoices', function (Blueprint $table) {
                $table->id('recurring_id');
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('client_id');
                $table->string('client_name');
                $table->string('frequency'); // weekly, monthly, quarterly, yearly
                $table->decimal('amount', 12, 2);
                $table->decimal('tax_rate', 5, 2)->default(0);
                $table->decimal('tax_amount', 12, 2)->default(0);
                $table->decimal('total', 12, 2);
                $table->text('description')->nullable();
                $table->date('start_date');
                $table->date('end_date')->nullable();
                $table->date('next_run_date');
                $table->date('last_run_date')->nullable();
                $table->integer('occurrences_generated')->default(0);
                $table->integer('max_occurrences')->nullable();
                $table->string('status')->default('active'); // active, paused, completed, cancelled
                $table->timestamps();
                $table->index(['company_id', 'status']);
                $table->index('next_run_date');
            });
        }

        // ── Recurring Invoice Line Items ──
        if (!Schema::hasTable('recurring_invoice_items')) {
            Schema::create('recurring_invoice_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('recurring_id');
                $table->unsignedBigInteger('product_id')->nullable();
                $table->string('description');
                $table->decimal('quantity', 10, 2)->default(1);
                $table->decimal('unit_price', 12, 2);
                $table->decimal('line_total', 12, 2);
                $table->timestamps();
                $table->foreign('recurring_id')->references('recurring_id')->on('recurring_invoices')->onDelete('cascade');
            });
        }

        // ── Notifications ──
        if (!Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->id('notification_id');
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('type'); // invoice_sent, payment_received, overdue_reminder, user_invited, etc.
                $table->string('title');
                $table->text('body')->nullable();
                $table->string('action_url')->nullable();
                $table->string('icon')->nullable();
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
                $table->index(['company_id', 'user_id', 'read_at']);
            });
        }

        // ── File Attachments ──
        if (!Schema::hasTable('file_attachments')) {
            Schema::create('file_attachments', function (Blueprint $table) {
                $table->id('attachment_id');
                $table->unsignedBigInteger('company_id');
                $table->string('attachable_type'); // invoice, estimate, expense, client
                $table->unsignedBigInteger('attachable_id');
                $table->string('original_name');
                $table->string('stored_path');
                $table->string('mime_type')->nullable();
                $table->unsignedBigInteger('size_bytes')->default(0);
                $table->unsignedBigInteger('uploaded_by')->nullable();
                $table->timestamps();
                $table->index(['company_id', 'attachable_type', 'attachable_id']);
            });
        }

        // ── Tax Configurations ──
        if (!Schema::hasTable('tax_rates')) {
            Schema::create('tax_rates', function (Blueprint $table) {
                $table->id('tax_rate_id');
                $table->unsignedBigInteger('company_id');
                $table->string('name'); // VAT 15%, Sales Tax, etc.
                $table->decimal('rate', 5, 2);
                $table->string('type')->default('percentage'); // percentage, fixed
                $table->boolean('is_default')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->index(['company_id', 'is_active']);
            });
        }

        // ── Activity Feed ──
        if (!Schema::hasTable('activity_feed')) {
            Schema::create('activity_feed', function (Blueprint $table) {
                $table->id('activity_id');
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('user_name')->nullable();
                $table->string('action'); // created, updated, deleted, sent, paid, etc.
                $table->string('entity_type'); // invoice, client, payment, etc.
                $table->unsignedBigInteger('entity_id')->nullable();
                $table->string('entity_label')->nullable();
                $table->text('details')->nullable();
                $table->timestamps();
                $table->index(['company_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_feed');
        Schema::dropIfExists('tax_rates');
        Schema::dropIfExists('file_attachments');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('recurring_invoice_items');
        Schema::dropIfExists('recurring_invoices');
        Schema::dropIfExists('user_invitations');
    }
};
