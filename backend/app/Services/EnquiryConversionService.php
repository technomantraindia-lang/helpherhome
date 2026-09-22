<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Enquiry;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class EnquiryConversionService
{
    public function __construct(private readonly CustomerManager $customers, private readonly EnquiryService $enquiries) {}

    public function convert(Enquiry $enquiry, int $actorId, ?int $existingCustomerId = null): array
    {
        return DB::transaction(function () use ($enquiry, $actorId, $existingCustomerId) {
            $locked = Enquiry::query()->lockForUpdate()->findOrFail($enquiry->id);
            if ($locked->converted_customer_id || $locked->converted_requirement_id || $locked->status->value === 'converted') {
                return ['enquiry' => $locked->fresh(['convertedCustomer','convertedRequirement']), 'customer' => $locked->convertedCustomer, 'requirement' => $locked->convertedRequirement, 'already_converted' => true];
            }
            $customer = $existingCustomerId ? Customer::query()->findOrFail($existingCustomerId) : Customer::query()->where('mobile_number', $locked->mobile_number)->first();
            $payload = ['registration_date'=>today()->toDateString(),'name'=>$locked->name,'mobile_number'=>$locked->mobile_number,'email'=>$locked->email,'city'=>$locked->city,'location'=>$locked->area,'address_line_1'=>$locked->area,'status'=>'active'];
            if (!$customer) {
                $service = $locked->service_id; if (!$service) throw new RuntimeException('A service is required before conversion.');
                $customer = $this->customers->register($payload + $this->requirementPayload($locked), $actorId);
                $requirement = $customer->requirements->first();
            } else {
                $requirement = $this->customers->createRequirement($customer, $this->requirementPayload($locked), $actorId);
            }
            $locked->update(['status'=>'converted','converted_customer_id'=>$customer->id,'converted_requirement_id'=>$requirement->id,'converted_at'=>now(),'converted_by'=>$actorId]);
            return ['enquiry'=>$locked->fresh(['convertedCustomer','convertedRequirement']),'customer'=>$customer->fresh(),'requirement'=>$requirement->fresh(),'already_converted'=>false];
        });
    }

    private function requirementPayload(Enquiry $enquiry): array
    {
        return ['service_id'=>$enquiry->service_id,'duty_type_id'=>$enquiry->duty_type_id,'required_gender'=>$enquiry->required_gender,'number_of_persons'=>$enquiry->number_of_persons ?: 1,'preferred_start_date'=>$enquiry->preferred_start_date?->toDateString(),'requirement_status'=>'open','detailed_work_description'=>$enquiry->message];
    }
}
