<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payment_sequences', function (Blueprint $table) { $table->unsignedSmallInteger('year')->primary(); $table->unsignedBigInteger('last_number')->default(0); $table->timestamps(); });
        Schema::create('receipt_sequences', function (Blueprint $table) { $table->unsignedSmallInteger('year')->primary(); $table->unsignedBigInteger('last_number')->default(0); $table->timestamps(); });
        Schema::create('payments', function (Blueprint $table) {
            $table->id(); $table->string('payment_code')->unique(); $table->foreignId('invoice_id')->constrained('invoices')->restrictOnDelete(); $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->date('payment_date')->index(); $table->decimal('amount',12,2); $table->string('payment_mode')->index(); $table->string('transaction_reference')->nullable(); $table->string('bank_reference')->nullable(); $table->string('upi_reference')->nullable(); $table->string('cheque_number')->nullable(); $table->date('cheque_date')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete(); $table->string('status')->default('completed')->index(); $table->text('notes')->nullable(); $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamps();
        });
        Schema::create('payment_receipts', function (Blueprint $table) {
            $table->id(); $table->string('receipt_number')->unique(); $table->foreignId('payment_id')->unique()->constrained('payments')->restrictOnDelete(); $table->foreignId('invoice_id')->constrained('invoices')->restrictOnDelete(); $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete(); $table->date('receipt_date');
            $table->decimal('invoice_total_snapshot',12,2); $table->decimal('previously_paid_snapshot',12,2); $table->decimal('payment_amount_snapshot',12,2); $table->decimal('balance_amount_snapshot',12,2); $table->string('payment_mode_snapshot'); $table->string('transaction_reference_snapshot')->nullable();
            $table->string('customer_name_snapshot')->nullable(); $table->text('customer_mobile_snapshot')->nullable(); $table->text('customer_address_snapshot')->nullable(); $table->string('customer_email_snapshot')->nullable(); $table->string('agency_name_snapshot')->nullable(); $table->text('agency_address_snapshot')->nullable(); $table->string('agency_phone_snapshot')->nullable(); $table->string('agency_email_snapshot')->nullable(); $table->string('agency_gst_snapshot')->nullable(); $table->string('bank_holder_snapshot')->nullable(); $table->string('bank_name_snapshot')->nullable(); $table->string('bank_ifsc_snapshot')->nullable(); $table->string('upi_id_snapshot')->nullable(); $table->string('authorized_person_snapshot')->nullable(); $table->string('signature_path_snapshot')->nullable(); $table->string('stamp_path_snapshot')->nullable(); $table->text('notes_snapshot')->nullable();
            $table->string('pdf_path')->nullable(); $table->timestamp('pdf_generated_at')->nullable(); $table->unsignedInteger('pdf_version')->default(0); $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamps();
        });
        Schema::create('payment_status_history', function (Blueprint $table) { $table->id(); $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete(); $table->string('old_status')->nullable(); $table->string('new_status'); $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete(); $table->text('reason')->nullable(); $table->timestamp('created_at')->useCurrent(); });
    }
    public function down(): void { Schema::dropIfExists('payment_status_history'); Schema::dropIfExists('payment_receipts'); Schema::dropIfExists('payments'); Schema::dropIfExists('receipt_sequences'); Schema::dropIfExists('payment_sequences'); }
};
