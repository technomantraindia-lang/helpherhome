<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_sequences', function (Blueprint $table) {
            $table->unsignedSmallInteger('year')->primary();
            $table->unsignedBigInteger('last_number')->default(0);
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('customer_requirement_id')->nullable()->constrained('customer_requirements')->nullOnDelete();
            $table->foreignId('assignment_id')->nullable()->constrained('assignments')->nullOnDelete();
            $table->foreignId('agreement_id')->nullable()->constrained('agreements')->nullOnDelete();
            $table->date('invoice_date')->index();
            $table->date('due_date')->nullable()->index();
            $table->date('billing_period_start')->nullable();
            $table->date('billing_period_end')->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->string('discount_type')->nullable();
            $table->decimal('discount_value', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->string('tax_type')->default('none');
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('cgst_rate', 5, 2)->default(0);
            $table->decimal('cgst_amount', 12, 2)->default(0);
            $table->decimal('sgst_rate', 5, 2)->default(0);
            $table->decimal('sgst_amount', 12, 2)->default(0);
            $table->decimal('igst_rate', 5, 2)->default(0);
            $table->decimal('igst_amount', 12, 2)->default(0);
            $table->decimal('tax_total', 12, 2)->default(0);
            $table->decimal('round_off', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('balance_amount', 12, 2)->default(0);
            $table->string('payment_status')->default('unpaid')->index();
            $table->string('status')->default('draft')->index();
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->string('agency_name_snapshot')->nullable();
            $table->text('agency_address_snapshot')->nullable();
            $table->string('agency_phone_snapshot', 30)->nullable();
            $table->string('agency_email_snapshot')->nullable();
            $table->string('agency_gst_snapshot', 30)->nullable();
            $table->string('agency_logo_snapshot')->nullable();
            $table->string('bank_holder_snapshot')->nullable();
            $table->string('bank_name_snapshot')->nullable();
            $table->string('bank_account_snapshot')->nullable();
            $table->string('bank_ifsc_snapshot', 20)->nullable();
            $table->string('bank_branch_snapshot')->nullable();
            $table->string('upi_id_snapshot')->nullable();
            $table->string('upi_qr_snapshot')->nullable();
            $table->string('customer_name_snapshot')->nullable();
            $table->text('customer_address_snapshot')->nullable();
            $table->string('customer_mobile_snapshot', 30)->nullable();
            $table->string('customer_email_snapshot')->nullable();
            $table->string('customer_gst_snapshot', 30)->nullable();
            $table->string('signature_path_snapshot')->nullable();
            $table->string('stamp_path_snapshot')->nullable();
            $table->string('pdf_path')->nullable();
            $table->timestamp('pdf_generated_at')->nullable();
            $table->unsignedInteger('pdf_version')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('description');
            $table->string('hsn_sac_code')->nullable();
            $table->decimal('quantity', 10, 2)->default(1);
            $table->string('unit')->nullable();
            $table->decimal('rate', 12, 2);
            $table->decimal('amount', 12, 2);
            $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->foreignId('worker_id')->nullable()->constrained('workers')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('invoice_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->string('old_status')->nullable();
            $table->string('new_status');
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('invoice_payment_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->string('old_status')->nullable();
            $table->string('new_status');
            $table->decimal('paid_amount', 12, 2)->nullable();
            $table->decimal('balance_amount', 12, 2)->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('invoice_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->json('snapshot_json');
            $table->string('pdf_path');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['invoice_id', 'version_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_versions');
        Schema::dropIfExists('invoice_payment_status_history');
        Schema::dropIfExists('invoice_status_history');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('invoice_sequences');
    }
};
