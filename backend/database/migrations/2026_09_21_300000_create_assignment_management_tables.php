<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('worker_shortlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_requirement_id')->constrained('customer_requirements')->cascadeOnDelete();
            $table->foreignId('worker_id')->constrained('workers')->restrictOnDelete();
            $table->foreignId('shortlisted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('shortlisted_at')->nullable();
            $table->string('status')->default('shortlisted')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['customer_requirement_id','worker_id']);
        });

        Schema::create('assignments', function (Blueprint $table) {
            $table->id(); $table->string('assignment_code')->unique();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('customer_requirement_id')->constrained('customer_requirements')->restrictOnDelete();
            $table->foreignId('worker_id')->constrained('workers')->restrictOnDelete();
            $table->foreignId('service_id')->constrained('services')->restrictOnDelete();
            $table->foreignId('duty_type_id')->nullable()->constrained('duty_types')->nullOnDelete();
            $table->date('assignment_start_date')->index(); $table->date('assignment_end_date')->nullable()->index();
            $table->string('working_hours_text')->nullable();
            $table->decimal('monthly_salary',12,2)->nullable(); $table->decimal('agency_service_charge',12,2)->nullable();
            $table->string('work_location')->nullable(); $table->string('status')->default('draft')->index();
            $table->timestamp('confirmed_at')->nullable(); $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable(); $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable(); $table->timestamps();
            $table->index(['worker_id','status','assignment_start_date','assignment_end_date'],'assignments_worker_overlap_index');
        });

        Schema::create('assignment_status_history', function (Blueprint $table) {
            $table->id(); $table->foreignId('assignment_id')->constrained('assignments')->cascadeOnDelete();
            $table->string('old_status')->nullable(); $table->string('new_status');
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable(); $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('replacement_requests', function (Blueprint $table) {
            $table->id(); $table->string('replacement_code')->unique();
            $table->foreignId('assignment_id')->constrained('assignments')->restrictOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('customer_requirement_id')->constrained('customer_requirements')->restrictOnDelete();
            $table->foreignId('old_worker_id')->constrained('workers')->restrictOnDelete();
            $table->string('reason')->index(); $table->text('reason_details')->nullable();
            $table->date('requested_date')->index(); $table->date('replacement_required_date')->nullable();
            $table->string('status')->default('requested')->index();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamp('approved_at')->nullable();
            $table->foreignId('new_worker_id')->nullable()->constrained('workers')->nullOnDelete();
            $table->foreignId('replacement_assignment_id')->nullable()->constrained('assignments')->nullOnDelete();
            $table->timestamp('completed_at')->nullable(); $table->text('remarks')->nullable(); $table->timestamps();
        });

        Schema::create('replacement_status_history', function (Blueprint $table) {
            $table->id(); $table->foreignId('replacement_request_id')->constrained('replacement_requests')->cascadeOnDelete();
            $table->string('old_status')->nullable(); $table->string('new_status');
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable(); $table->timestamp('created_at')->useCurrent();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('replacement_status_history'); Schema::dropIfExists('replacement_requests');
        Schema::dropIfExists('assignment_status_history'); Schema::dropIfExists('assignments'); Schema::dropIfExists('worker_shortlists');
    }
};
