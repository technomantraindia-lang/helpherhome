<?php

namespace App\Services;

use App\Enums\CustomerGeneratedDocumentStatus;
use App\Enums\CustomerGeneratedDocumentType;
use App\Models\AgencySetting;
use App\Models\CustomerRequirement;
use App\Models\CustomerGeneratedDocument;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Dompdf\Dompdf;
use Dompdf\Options;

class CustomerDocumentGenerationService
{
    public function buildRegistrationSnapshot(CustomerRequirement $requirement): array
    {
        $requirement->loadMissing(['customer', 'service', 'dutyType', 'householdDetail', 'accommodationDetail', 'workingCondition', 'workerPreference']);
        $customer = $requirement->customer;
        $agency = AgencySetting::firstOrCreate([], ['business_name' => 'Helper Home']);
        $household = $requirement->householdDetail;
        $accommodation = $requirement->accommodationDetail;
        $working = $requirement->workingCondition;
        $preference = $requirement->workerPreference;

        return [
            'customer' => [
                'customer_code' => $customer->customer_code,
                'registration_number' => $customer->registration_number,
                'registration_date' => $customer->registration_date?->format('Y-m-d'),
                'name' => $customer->name,
                'mobile_number' => $customer->mobile_number,
                'alternate_mobile_number' => $customer->alternate_mobile_number,
                'email' => $customer->email,
                'location' => $customer->location,
                'address_line_1' => $customer->address_line_1,
                'address_line_2' => $customer->address_line_2,
                'city' => $customer->city,
                'state' => $customer->state,
                'pincode' => $customer->pincode,
                'country' => $customer->country,
            ],
            'requirement' => [
                'requirement_code' => $requirement->requirement_code,
                'service_name' => $requirement->service?->name,
                'required_gender' => $requirement->required_gender,
                'number_of_persons' => $requirement->number_of_persons,
                'other_service_description' => $requirement->other_service_description,
                'duty_type' => $requirement->dutyType?->name,
                'custom_duty_type' => $requirement->custom_duty_type,
                'preferred_start_date' => $requirement->preferred_start_date?->format('Y-m-d'),
                'preferred_end_date' => $requirement->preferred_end_date?->format('Y-m-d'),
                'monthly_salary_budget' => $requirement->monthly_salary_budget,
                'monthly_agency_service_charge' => $requirement->monthly_agency_service_charge,
                'detailed_work_description' => $requirement->detailed_work_description,
                'special_instructions' => $requirement->special_instructions,
            ],
            'household' => $household ? [
                'house_type' => $household->house_type,
                'custom_house_type' => $household->custom_house_type,
                'total_family_members' => $household->total_family_members,
                'adults_count' => $household->adults_count,
                'children_count' => $household->children_count,
                'elderly_count' => $household->elderly_count,
                'patients_count' => $household->patients_count,
                'food_preference' => $household->food_preference,
                'pets_at_home' => $household->pets_at_home,
                'children_at_home' => $household->children_at_home,
                'elderly_at_home' => $household->elderly_at_home,
                'patient_care_required' => $household->patient_care_required,
                'lift_available' => $household->lift_available,
                'outside_travel_required' => $household->outside_travel_required,
            ] : [],
            'accommodation' => $accommodation ? [
                'accommodation_provided' => $accommodation->accommodation_provided,
                'room_type' => $accommodation->room_type,
                'bathroom_type' => $accommodation->bathroom_type,
                'food_provided' => $accommodation->food_provided,
                'breakfast_provided' => $accommodation->breakfast_provided,
                'lunch_provided' => $accommodation->lunch_provided,
                'dinner_provided' => $accommodation->dinner_provided,
                'tea_snacks_provided' => $accommodation->tea_snacks_provided,
                'other_food_details' => $accommodation->other_food_details,
                'accommodation_notes' => $accommodation->accommodation_notes,
            ] : [],
            'working' => $working ? [
                'working_hours_text' => $working->working_hours_text,
                'work_start_time' => $working->work_start_time,
                'work_end_time' => $working->work_end_time,
                'expected_wakeup_time' => $working->expected_wakeup_time,
                'expected_sleep_time' => $working->expected_sleep_time,
                'afternoon_rest' => $working->afternoon_rest,
                'rest_duration_hours' => $working->rest_duration_hours,
                'monthly_leave_days' => $working->monthly_leave_days,
                'leave_details' => $working->leave_details,
                'night_duty_required' => $working->night_duty_required,
                'early_morning_work_required' => $working->early_morning_work_required,
                'early_morning_work_details' => $working->early_morning_work_details,
            ] : [],
            'worker_preferences' => $preference ? [
                'preferred_age_min' => $preference->preferred_age_min,
                'preferred_age_max' => $preference->preferred_age_max,
                'experience_requirement' => $preference->experience_requirement?->value,
                'language_preference' => $preference->language_preference,
                'specific_skills' => $preference->specific_skills,
                'other_work_expected' => $preference->other_work_expected,
                'worker_preference_notes' => $preference->worker_preference_notes,
            ] : [],
            'agency' => [
                'business_name' => $agency->business_name ?: 'Helper Home',
                'tagline' => $agency->tagline,
                'phone' => $agency->phone_primary,
                'email' => $agency->email,
                'address' => collect([$agency->address_line_1, $agency->address_line_2, $agency->city, $agency->state, $agency->pincode])->filter()->implode(', '),
                'authorized_person' => $agency->owner_authorized_person,
                'logo_path' => $agency->logo_path,
                'signature_path' => $agency->signature_path,
                'stamp_path' => $agency->stamp_path,
            ],
        ];
    }

    public function generateRegistrationForm(CustomerRequirement $requirement, int $actorId): CustomerGeneratedDocument
    {
        return DB::transaction(function () use ($requirement, $actorId) {
            $locked = CustomerRequirement::whereKey($requirement->id)->lockForUpdate()->firstOrFail();
            $version = ((int) CustomerGeneratedDocument::where('customer_id', $locked->customer_id)->where('customer_requirement_id', $locked->id)->max('version_number')) + 1;
            $snapshot = $this->buildRegistrationSnapshot($locked);
            $document = CustomerGeneratedDocument::create([
                'customer_id' => $locked->customer_id,
                'customer_requirement_id' => $locked->id,
                'document_type' => CustomerGeneratedDocumentType::RegistrationForm,
                'document_number' => sprintf('HH-CRF-%s-V%d', $snapshot['customer']['customer_code'], $version),
                'version_number' => $version,
                'status' => CustomerGeneratedDocumentStatus::Draft,
                'snapshot_json' => $snapshot,
                'generated_by' => $actorId,
            ]);
            $path = "customers/{$locked->customer_id}/generated/customer-registration-v{$version}.pdf";
            $html = view('admin.customers.documents.registration-pdf', $this->templateData($document, $snapshot))->render();
            $options = new Options();
            $options->set('defaultFont', 'DejaVu Sans');
            $options->set('isRemoteEnabled', false);
            $pdf = new Dompdf($options);
            $pdf->loadHtml($html, 'UTF-8');
            $pdf->setPaper('A4', 'portrait');
            $pdf->render();
            Storage::disk('local')->put($path, $pdf->output());
            $document->update(['pdf_path' => $path, 'generated_at' => now(), 'status' => CustomerGeneratedDocumentStatus::Generated]);
            return $document->fresh(['customer', 'requirement', 'generator']);
        }, 5);
    }

    public function templateData(CustomerGeneratedDocument $document, array $snapshot): array
    {
        return ['document' => $document, 'snapshot' => $snapshot, 'logoData' => $this->dataUri($snapshot['agency']['logo_path'] ?? null), 'signatureData' => $this->dataUri($snapshot['agency']['signature_path'] ?? null), 'stampData' => $this->dataUri($snapshot['agency']['stamp_path'] ?? null)];
    }

    public function snapshotData(CustomerGeneratedDocument $document): array
    {
        return $this->templateData($document, $document->snapshot_json);
    }

    private function dataUri(?string $path): ?string
    {
        if (!$path) return null;
        $disk = Storage::disk('local')->exists($path) ? 'local' : 'public';
        if (!Storage::disk($disk)->exists($path)) return null;
        $mime = Storage::disk($disk)->mimeType($path) ?: 'image/png';
        return 'data:'.$mime.';base64,'.base64_encode(Storage::disk($disk)->get($path));
    }
}
