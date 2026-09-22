<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('enquiries', function (Blueprint $table) {
            $table->id();
            $table->string('enquiry_code')->unique();
            $table->string('name');
            $table->string('mobile_number', 30)->index();
            $table->string('email')->nullable();
            $table->string('city')->nullable();
            $table->string('area')->nullable();
            $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->foreignId('duty_type_id')->nullable()->constrained('duty_types')->nullOnDelete();
            $table->date('preferred_start_date')->nullable();
            $table->string('required_gender')->nullable();
            $table->unsignedSmallInteger('number_of_persons')->nullable();
            $table->string('preferred_contact_method')->nullable();
            $table->text('message')->nullable();
            $table->string('source')->default('website')->index();
            $table->text('page_url')->nullable();
            $table->text('referrer')->nullable();
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('utm_term')->nullable();
            $table->string('utm_content')->nullable();
            $table->string('status')->default('new')->index();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete()->index();
            $table->timestamp('first_contacted_at')->nullable();
            $table->timestamp('last_contacted_at')->nullable();
            $table->foreignId('converted_customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('converted_requirement_id')->nullable()->constrained('customer_requirements')->nullOnDelete();
            $table->timestamp('converted_at')->nullable();
            $table->foreignId('converted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            $table->index(['created_at', 'status']);
            $table->index(['service_id', 'duty_type_id']);
        });

        Schema::create('enquiry_follow_ups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enquiry_id')->constrained()->cascadeOnDelete();
            $table->date('follow_up_date')->index();
            $table->time('follow_up_time')->nullable();
            $table->string('status')->default('pending')->index();
            $table->text('notes')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete()->index();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enquiry_follow_ups');
        Schema::dropIfExists('enquiries');
    }
};
