<?php

namespace App\Services;

use App\Models\DutyType;
use App\Models\Enquiry;
use App\Models\Service;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EnquiryService
{
    public function createPublic(array $data, ?string $ip = null, ?string $userAgent = null): Enquiry
    {
        $service = $this->resolveService($data);
        $duty = $this->resolveDutyType($data);
        if (!$service) throw ValidationException::withMessages(['service_id' => 'Please select a valid service.']);
        if (($data['duty_type_id'] ?? null) || ($data['duty_type_slug'] ?? null)) {
            if (!$duty) throw ValidationException::withMessages(['duty_type_id' => 'Please select a valid duty type.']);
        }
        return DB::transaction(function () use ($data, $service, $duty, $ip, $userAgent) {
            $enquiry = Enquiry::create([
                'enquiry_code' => 'PENDING-'.Str::uuid(), 'name' => $data['name'], 'mobile_number' => $this->normalizeMobile($data['mobile_number']),
                'email' => $data['email'] ?? null, 'city' => $data['city'] ?? null, 'area' => $data['area'] ?? null, 'service_id' => $service->id,
                'duty_type_id' => $duty?->id, 'preferred_start_date' => $data['preferred_start_date'] ?? null, 'required_gender' => $data['required_gender'] ?? null,
                'number_of_persons' => $data['number_of_persons'] ?? null, 'preferred_contact_method' => $data['preferred_contact_method'] ?? null,
                'message' => $data['message'] ?? null, 'source' => 'website', 'page_url' => $data['source_page'] ?? null,
                'referrer' => $data['referrer'] ?? null, 'utm_source' => $data['utm_source'] ?? null, 'utm_medium' => $data['utm_medium'] ?? null,
                'utm_campaign' => $data['utm_campaign'] ?? null, 'utm_term' => $data['utm_term'] ?? null, 'utm_content' => $data['utm_content'] ?? null,
                'status' => 'new', 'ip_address' => $ip, 'user_agent' => $userAgent,
            ]);
            $enquiry->forceFill(['enquiry_code' => sprintf('HH-ENQ-%s-%06d', now()->format('Y'), $enquiry->id)])->saveQuietly();
            return $enquiry->fresh(['service','dutyType']);
        });
    }

    public function createManual(array $data, int $actorId): Enquiry
    {
        $data['source'] = $data['source'] ?? 'manual';
        $service = $this->resolveService($data); $duty = $this->resolveDutyType($data);
        return DB::transaction(function () use ($data, $service, $duty, $actorId) {
            $enquiry = Enquiry::create(Arr::only($data, ['name','mobile_number','email','city','area','preferred_start_date','required_gender','number_of_persons','preferred_contact_method','message','notes']) + ['enquiry_code'=>'PENDING-'.Str::uuid(),'service_id'=>$service?->id,'duty_type_id'=>$duty?->id,'source'=>$data['source']]);
            $enquiry->forceFill(['enquiry_code'=>sprintf('HH-ENQ-%s-%06d', now()->format('Y'), $enquiry->id)])->saveQuietly();
            return $enquiry;
        });
    }

    public function resolveService(array $data): ?Service
    {
        return Service::query()->where('is_active', true)->when($data['service_id'] ?? null, fn($q,$v)=>$q->whereKey($v))->when(!($data['service_id'] ?? null) && ($data['service_slug'] ?? null), fn($q)=>$q->where('slug',$data['service_slug']))->first();
    }
    public function resolveDutyType(array $data): ?DutyType
    {
        return DutyType::query()->where('is_active', true)->when($data['duty_type_id'] ?? null, fn($q,$v)=>$q->whereKey($v))->when(!($data['duty_type_id'] ?? null) && ($data['duty_type_slug'] ?? null), fn($q)=>$q->where('slug',$data['duty_type_slug']))->first();
    }
    public function normalizeMobile(string $value): string { return preg_replace('/\D+/', '', $value); }
}
