<?php

namespace App\Http\Requests\Concerns;

use App\Enums\ExperienceRequirement;
use App\Enums\RequirementStatus;
use Illuminate\Validation\Rule;

trait CustomerRequirementRules
{
    protected function normalizeRequirementInput(): void
    {
        $booleans = ['pets_at_home','children_at_home','elderly_at_home','patient_care_required','lift_available','outside_travel_required','breakfast_provided','lunch_provided','dinner_provided','tea_snacks_provided','afternoon_rest','night_duty_required','early_morning_work_required'];
        $values = [];
        foreach ($booleans as $field) {
            if ($this->has($field)) $values[$field] = $this->boolean($field);
        }
        $this->merge($values);
    }

    protected function requirementRules(): array
    {
        return [
            'service_id' => ['required','integer',Rule::exists('services','id')->where('is_active', true)],
            'duty_type_id' => ['nullable','integer',Rule::exists('duty_types','id')->where('is_active', true)],
            'required_gender' => ['nullable',Rule::in(['male','female','both','no_preference'])],
            'number_of_persons' => ['required','integer','min:1','max:50'],
            'other_service_description' => ['nullable','string','max:255'], 'custom_duty_type' => ['nullable','string','max:255'],
            'monthly_salary_budget' => ['nullable','numeric','min:0','max:9999999999.99'], 'monthly_agency_service_charge' => ['nullable','numeric','min:0','max:9999999999.99'],
            'preferred_start_date' => ['nullable','date'], 'preferred_end_date' => ['nullable','date','after_or_equal:preferred_start_date'],
            'requirement_status' => ['required',Rule::enum(RequirementStatus::class)],
            'detailed_work_description' => ['nullable','string','max:10000'], 'special_instructions' => ['nullable','string','max:10000'],
            'house_type' => ['nullable',Rule::in(['1_bhk','2_bhk','3_bhk','4_bhk','bungalow','villa','other'])],
            'custom_house_type' => ['nullable','required_if:house_type,other','string','max:255'],
            'total_family_members' => ['nullable','integer','min:0','max:255'], 'adults_count' => ['nullable','integer','min:0','max:255'],
            'children_count' => ['nullable','integer','min:0','max:255'], 'elderly_count' => ['nullable','integer','min:0','max:255'], 'patients_count' => ['nullable','integer','min:0','max:255'],
            'food_preference' => ['nullable',Rule::in(['vegetarian','non_vegetarian','both','other'])],
            'pets_at_home' => ['nullable','boolean'], 'children_at_home' => ['nullable','boolean'], 'elderly_at_home' => ['nullable','boolean'],
            'patient_care_required' => ['nullable','boolean'], 'lift_available' => ['nullable','boolean'], 'outside_travel_required' => ['nullable','boolean'],
            'accommodation_provided' => ['nullable',Rule::in(['yes','no'])], 'room_type' => ['nullable',Rule::in(['separate','shared','other'])],
            'bathroom_type' => ['nullable',Rule::in(['separate','shared','other'])], 'food_provided' => ['nullable',Rule::in(['yes','no'])],
            'breakfast_provided' => ['boolean'], 'lunch_provided' => ['boolean'], 'dinner_provided' => ['boolean'], 'tea_snacks_provided' => ['boolean'],
            'other_food_details' => ['nullable','string','max:255'], 'accommodation_notes' => ['nullable','string','max:5000'],
            'working_hours_text' => ['nullable','string','max:255'], 'work_start_time' => ['nullable','date_format:H:i'], 'work_end_time' => ['nullable','date_format:H:i'],
            'expected_wakeup_time' => ['nullable','date_format:H:i'], 'expected_sleep_time' => ['nullable','date_format:H:i'],
            'afternoon_rest' => ['nullable','boolean'], 'rest_duration_hours' => ['nullable','numeric','min:0','max:24'],
            'monthly_leave_days' => ['nullable','integer','min:0','max:31'], 'leave_details' => ['nullable','string','max:255'],
            'night_duty_required' => ['nullable','boolean'], 'early_morning_work_required' => ['nullable','boolean'],
            'early_morning_work_details' => ['nullable','string','max:255'],
            'preferred_age_min' => ['nullable','integer','min:18','max:100'], 'preferred_age_max' => ['nullable','integer','min:18','max:100','gte:preferred_age_min'],
            'experience_requirement' => ['nullable',Rule::enum(ExperienceRequirement::class)], 'language_preference' => ['nullable','string','max:255'],
            'specific_skills' => ['nullable','string','max:5000'], 'other_work_expected' => ['nullable','string','max:5000'], 'worker_preference_notes' => ['nullable','string','max:5000'],
            'status_reason' => ['nullable','string','max:1000'],
        ];
    }
}
