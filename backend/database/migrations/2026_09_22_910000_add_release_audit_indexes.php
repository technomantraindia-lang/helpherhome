<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('worker_documents', function (Blueprint $table) {
            $table->index('worker_id', 'worker_documents_worker_id_index');
        });
        Schema::table('worker_interviews', function (Blueprint $table) {
            $table->index('worker_id', 'worker_interviews_worker_id_index');
            $table->index('service_id', 'worker_interviews_service_id_index');
            $table->index('interviewer_user_id', 'worker_interviews_interviewer_user_id_index');
        });
        Schema::table('assignments', function (Blueprint $table) {
            $table->index('customer_id', 'assignments_customer_id_index');
            $table->index('worker_id', 'assignments_worker_id_index');
        });
        Schema::table('replacement_requests', function (Blueprint $table) {
            $table->index('assignment_id', 'replacement_requests_assignment_id_index');
            $table->index('customer_id', 'replacement_requests_customer_id_index');
        });
        Schema::table('agreements', function (Blueprint $table) {
            $table->index('customer_id', 'agreements_customer_id_index');
            $table->index('worker_id', 'agreements_worker_id_index');
            $table->index('assignment_id', 'agreements_assignment_id_index');
        });
        Schema::table('invoices', function (Blueprint $table) {
            $table->index('customer_id', 'invoices_customer_id_index');
            $table->index('agreement_id', 'invoices_agreement_id_index');
        });
        Schema::table('payments', function (Blueprint $table) {
            $table->index('invoice_id', 'payments_invoice_id_index');
            $table->index('customer_id', 'payments_customer_id_index');
        });
        Schema::table('payment_receipts', function (Blueprint $table) {
            $table->index('invoice_id', 'payment_receipts_invoice_id_index');
            $table->index('customer_id', 'payment_receipts_customer_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('worker_documents', fn (Blueprint $table) => $table->dropIndex('worker_documents_worker_id_index'));
        Schema::table('worker_interviews', function (Blueprint $table) { $table->dropIndex('worker_interviews_worker_id_index'); $table->dropIndex('worker_interviews_service_id_index'); $table->dropIndex('worker_interviews_interviewer_user_id_index'); });
        Schema::table('assignments', function (Blueprint $table) { $table->dropIndex('assignments_customer_id_index'); $table->dropIndex('assignments_worker_id_index'); });
        Schema::table('replacement_requests', function (Blueprint $table) { $table->dropIndex('replacement_requests_assignment_id_index'); $table->dropIndex('replacement_requests_customer_id_index'); });
        Schema::table('agreements', function (Blueprint $table) { $table->dropIndex('agreements_customer_id_index'); $table->dropIndex('agreements_worker_id_index'); $table->dropIndex('agreements_assignment_id_index'); });
        Schema::table('invoices', function (Blueprint $table) { $table->dropIndex('invoices_customer_id_index'); $table->dropIndex('invoices_agreement_id_index'); });
        Schema::table('payments', function (Blueprint $table) { $table->dropIndex('payments_invoice_id_index'); $table->dropIndex('payments_customer_id_index'); });
        Schema::table('payment_receipts', function (Blueprint $table) { $table->dropIndex('payment_receipts_invoice_id_index'); $table->dropIndex('payment_receipts_customer_id_index'); });
    }
};
