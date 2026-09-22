<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('customer_code')->unique()->index();
            $table->string('registration_number')->nullable()->unique();
            $table->date('registration_date')->index();
            $table->string('name')->index();
            $table->string('mobile_number', 20)->index();
            $table->string('alternate_mobile_number', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('location')->nullable();
            $table->string('city')->nullable()->index();
            $table->string('state')->nullable();
            $table->string('pincode', 12)->nullable();
            $table->string('country')->default('India');
            $table->string('status')->default('active')->index();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('customer_requirements', function (Blueprint $table) {
            $table->id();
            $table->string('requirement_code')->unique()->index();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('services')->restrictOnDelete();
            $table->foreignId('duty_type_id')->nullable()->constrained('duty_types')->nullOnDelete();
            $table->string('required_gender')->nullable(); // male, female, both, no_preference
            $table->unsignedSmallInteger('number_of_persons')->default(1);
            $table->string('other_service_description')->nullable();
            $table->string('custom_duty_type')->nullable();
            $table->decimal('monthly_salary_budget', 12, 2)->nullable();
            $table->decimal('monthly_agency_service_charge', 12, 2)->nullable();
            $table->date('preferred_start_date')->nullable();
            $table->date('preferred_end_date')->nullable();
            $table->string('requirement_status')->default('open')->index();
            $table->text('detailed_work_description')->nullable();
            $table->text('special_instructions')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['customer_id', 'requirement_status']);
            $table->index(['service_id', 'requirement_status']);
        });

        Schema::create('customer_household_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_requirement_id')->unique()->constrained('customer_requirements')->cascadeOnDelete();
            $table->string('house_type')->nullable(); // 1bhk, 2bhk, 3bhk, 4bhk, bungalow, villa, other
            $table->string('custom_house_type')->nullable();
            $table->unsignedTinyInteger('total_family_members')->nullable();
            $table->unsignedTinyInteger('adults_count')->nullable();
            $table->unsignedTinyInteger('children_count')->nullable();
            $table->unsignedTinyInteger('elderly_count')->nullable();
            $table->unsignedTinyInteger('patients_count')->nullable();
            $table->string('food_preference')->nullable(); // vegetarian, non_vegetarian, both, other
            $table->boolean('pets_at_home')->nullable();
            $table->boolean('children_at_home')->nullable();
            $table->boolean('elderly_at_home')->nullable();
            $table->boolean('patient_care_required')->nullable();
            $table->boolean('lift_available')->nullable();
            $table->boolean('outside_travel_required')->nullable();
            $table->timestamps();
        });

        Schema::create('customer_accommodation_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_requirement_id')->unique()->constrained('customer_requirements')->cascadeOnDelete();
            $table->string('accommodation_provided')->nullable(); // yes, no
            $table->string('room_type')->nullable(); // separate, shared, other
            $table->string('bathroom_type')->nullable(); // separate, shared, other
            $table->string('food_provided')->nullable(); // yes, no
            $table->boolean('breakfast_provided')->default(false);
            $table->boolean('lunch_provided')->default(false);
            $table->boolean('dinner_provided')->default(false);
            $table->boolean('tea_snacks_provided')->default(false);
            $table->string('other_food_details')->nullable();
            $table->text('accommodation_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('customer_working_conditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_requirement_id')->unique()->constrained('customer_requirements')->cascadeOnDelete();
            $table->string('working_hours_text')->nullable();
            $table->time('work_start_time')->nullable();
            $table->time('work_end_time')->nullable();
            $table->time('expected_wakeup_time')->nullable();
            $table->time('expected_sleep_time')->nullable();
            $table->boolean('afternoon_rest')->nullable();
            $table->decimal('rest_duration_hours', 4, 1)->nullable();
            $table->unsignedTinyInteger('monthly_leave_days')->nullable();
            $table->string('leave_details')->nullable();
            $table->boolean('night_duty_required')->nullable();
            $table->boolean('early_morning_work_required')->nullable();
            $table->string('early_morning_work_details')->nullable();
            $table->timestamps();
        });

        Schema::create('customer_worker_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_requirement_id')->unique()->constrained('customer_requirements')->cascadeOnDelete();
            $table->unsignedTinyInteger('preferred_age_min')->nullable();
            $table->unsignedTinyInteger('preferred_age_max')->nullable();
            $table->string('experience_requirement')->nullable(); // none, 1_2_years, 2_5_years, 5_plus_years, custom
            $table->string('language_preference')->nullable();
            $table->text('specific_skills')->nullable();
            $table->text('other_work_expected')->nullable();
            $table->text('worker_preference_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('customer_requirement_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_requirement_id')->constrained('customer_requirements')->cascadeOnDelete();
            $table->string('old_status')->nullable();
            $table->string('new_status');
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['customer_requirement_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_requirement_status_history');
        Schema::dropIfExists('customer_worker_preferences');
        Schema::dropIfExists('customer_working_conditions');
        Schema::dropIfExists('customer_accommodation_details');
        Schema::dropIfExists('customer_household_details');
        Schema::dropIfExists('customer_requirements');
        Schema::dropIfExists('customers');
    }
};
