<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workers', function (Blueprint $table) {
            $table->id();
            $table->string('worker_code')->unique();
            $table->string('registration_number')->nullable()->unique();
            $table->date('registration_date')->index();
            $table->date('date_of_joining')->nullable();
            $table->string('name');
            $table->string('photo_path')->nullable();
            $table->string('mobile_number', 20)->index();
            $table->string('alternate_mobile_number', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('address_line_1');
            $table->string('address_line_2')->nullable();
            $table->string('city')->index();
            $table->string('state')->index();
            $table->string('pincode', 12)->nullable();
            $table->string('country')->default('India');
            $table->date('date_of_birth')->nullable();
            $table->unsignedTinyInteger('age')->nullable();
            $table->string('gender', 30)->nullable()->index();
            $table->string('marital_status', 30)->nullable();
            $table->decimal('years_of_experience', 5, 1)->nullable();
            $table->text('experience_notes')->nullable();
            $table->decimal('salary_expectation', 12, 2)->nullable();
            $table->string('preferred_work_location')->nullable();
            $table->foreignId('preferred_duty_type_id')->nullable()->constrained('duty_types')->nullOnDelete();
            $table->string('availability_status')->default('available')->index();
            $table->string('worker_status')->default('active')->index();
            $table->string('police_station_name')->nullable();
            $table->foreignId('supervisor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('executive_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('registration_source')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('worker_service', function (Blueprint $table) {
            $table->foreignId('worker_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->restrictOnDelete();
            $table->string('skill_level')->nullable();
            $table->decimal('experience_years', 5, 1)->nullable();
            $table->text('notes')->nullable();
            $table->primary(['worker_id', 'service_id']);
        });

        Schema::create('worker_references', function (Blueprint $table) {
            $table->id();
            $table->foreignId('worker_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('mobile_number', 20);
            $table->string('relation');
            $table->text('address')->nullable();
            $table->string('occupation')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['worker_id', 'mobile_number']);
        });

        Schema::create('worker_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('worker_id')->constrained()->cascadeOnDelete();
            $table->string('document_type')->index();
            $table->string('document_name')->nullable();
            $table->string('document_number')->nullable()->index();
            $table->string('file_path')->nullable();
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable()->index();
            $table->string('verification_status')->default('pending')->index();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('worker_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('worker_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('document_verification_status')->default('pending');
            $table->date('document_verification_date')->nullable();
            $table->foreignId('document_verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('police_verification_status')->default('pending');
            $table->date('police_verification_date')->nullable();
            $table->foreignId('police_verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('background_verification_status')->default('pending');
            $table->date('background_verification_date')->nullable();
            $table->foreignId('background_verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('overall_verification_status')->default('pending')->index();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('worker_interviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('worker_id')->constrained()->cascadeOnDelete();
            $table->dateTime('interview_date')->nullable()->index();
            $table->foreignId('interviewer_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->text('experience_assessment')->nullable();
            $table->text('skills_assessment')->nullable();
            $table->text('communication_assessment')->nullable();
            $table->decimal('expected_salary', 12, 2)->nullable();
            $table->foreignId('preferred_duty_type_id')->nullable()->constrained('duty_types')->nullOnDelete();
            $table->string('preferred_location')->nullable();
            $table->text('interview_notes')->nullable();
            $table->string('result')->default('pending')->index();
            $table->timestamps();
        });

        Schema::create('worker_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('worker_id')->constrained()->cascadeOnDelete();
            $table->string('old_status')->nullable();
            $table->string('new_status');
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['worker_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('worker_status_history');
        Schema::dropIfExists('worker_interviews');
        Schema::dropIfExists('worker_verifications');
        Schema::dropIfExists('worker_documents');
        Schema::dropIfExists('worker_references');
        Schema::dropIfExists('worker_service');
        Schema::dropIfExists('workers');
    }
};
