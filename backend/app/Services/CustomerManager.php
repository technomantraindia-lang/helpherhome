<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerRequirement;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CustomerManager
{
    private const CUSTOMER_FIELDS = ['registration_number','registration_date','name','mobile_number','alternate_mobile_number','email','address_line_1','address_line_2','location','city','state','pincode','country','status','notes'];
    private const REQUIREMENT_FIELDS = ['service_id','duty_type_id','required_gender','number_of_persons','other_service_description','custom_duty_type','monthly_salary_budget','monthly_agency_service_charge','preferred_start_date','preferred_end_date','requirement_status','detailed_work_description','special_instructions'];
    private const HOUSEHOLD_FIELDS = ['house_type','custom_house_type','total_family_members','adults_count','children_count','elderly_count','patients_count','food_preference','pets_at_home','children_at_home','elderly_at_home','patient_care_required','lift_available','outside_travel_required'];
    private const ACCOMMODATION_FIELDS = ['accommodation_provided','room_type','bathroom_type','food_provided','breakfast_provided','lunch_provided','dinner_provided','tea_snacks_provided','other_food_details','accommodation_notes'];
    private const WORKING_FIELDS = ['working_hours_text','work_start_time','work_end_time','expected_wakeup_time','expected_sleep_time','afternoon_rest','rest_duration_hours','monthly_leave_days','leave_details','night_duty_required','early_morning_work_required','early_morning_work_details'];
    private const PREFERENCE_FIELDS = ['preferred_age_min','preferred_age_max','experience_requirement','language_preference','specific_skills','other_work_expected','worker_preference_notes'];

    public function register(array $data, int $actorId): Customer
    {
        return DB::transaction(function () use ($data, $actorId) {
            $customerData = Arr::only($data, self::CUSTOMER_FIELDS) + ['customer_code'=>'PENDING-'.Str::uuid(), 'created_by'=>$actorId, 'updated_by'=>$actorId];
            $customer = Customer::create($customerData);
            $customer->forceFill(['customer_code'=>sprintf('HH-CUS-%06d', $customer->id)])->saveQuietly();
            $this->createRequirement($customer, $data, $actorId);
            return $customer->fresh(['requirements']);
        });
    }

    public function updateCustomer(Customer $customer, array $data, int $actorId): Customer
    {
        return DB::transaction(function () use ($customer, $data, $actorId) {
            $customer->update(Arr::only($data, self::CUSTOMER_FIELDS) + ['updated_by'=>$actorId]);
            return $customer->fresh();
        });
    }

    public function createRequirement(Customer $customer, array $data, int $actorId): CustomerRequirement
    {
        return DB::transaction(function () use ($customer, $data, $actorId) {
            $values = Arr::only($data, self::REQUIREMENT_FIELDS) + ['requirement_code'=>'PENDING-'.Str::uuid(), 'created_by'=>$actorId, 'updated_by'=>$actorId];
            $requirement = $customer->requirements()->create($values);
            $requirement->forceFill(['requirement_code'=>sprintf('HH-REQ-%06d', $requirement->id)])->saveQuietly();
            $this->saveDetails($requirement, $data);
            $requirement->statusHistory()->create(['old_status'=>null,'new_status'=>$requirement->requirement_status->value,'changed_by'=>$actorId,'reason'=>'Initial requirement registration']);
            return $requirement->fresh();
        });
    }

    public function updateRequirement(CustomerRequirement $requirement, array $data, int $actorId): CustomerRequirement
    {
        return DB::transaction(function () use ($requirement, $data, $actorId) {
            $oldStatus = $requirement->requirement_status->value;
            $requirement->update(Arr::only($data, self::REQUIREMENT_FIELDS) + ['updated_by'=>$actorId]);
            $this->saveDetails($requirement, $data);
            if ($oldStatus !== $requirement->requirement_status->value) {
                $requirement->statusHistory()->create(['old_status'=>$oldStatus,'new_status'=>$requirement->requirement_status->value,'changed_by'=>$actorId,'reason'=>$data['status_reason'] ?? null]);
            }
            return $requirement->fresh();
        });
    }

    private function saveDetails(CustomerRequirement $requirement, array $data): void
    {
        $requirement->householdDetail()->updateOrCreate([], Arr::only($data, self::HOUSEHOLD_FIELDS));
        $requirement->accommodationDetail()->updateOrCreate([], Arr::only($data, self::ACCOMMODATION_FIELDS));
        $requirement->workingCondition()->updateOrCreate([], Arr::only($data, self::WORKING_FIELDS));
        $requirement->workerPreference()->updateOrCreate([], Arr::only($data, self::PREFERENCE_FIELDS));
    }
}
