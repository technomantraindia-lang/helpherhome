<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ExperienceRequirement;
use App\Enums\RequirementStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCustomerRequirementRequest;
use App\Http\Requests\UpdateCustomerRequirementRequest;
use App\Models\Customer;
use App\Models\CustomerRequirement;
use App\Models\DutyType;
use App\Models\Service;
use App\Services\ActivityLogger;
use App\Services\CustomerManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerRequirementController extends Controller
{
    public function index(Request $request): View
    {
        $requirements=CustomerRequirement::query()->with(['customer:id,customer_code,name,mobile_number,city','service:id,name','dutyType:id,name'])
            ->when($request->filled('search'),function($q)use($request){$term='%'.$request->string('search')->trim().'%';$q->where(fn($x)=>$x->where('requirement_code','like',$term)->orWhereHas('customer',fn($c)=>$c->where('name','like',$term)->orWhere('mobile_number','like',$term)));})
            ->when($request->filled('service'),fn($q)=>$q->where('service_id',$request->integer('service')))
            ->when($request->filled('duty_type'),fn($q)=>$q->where('duty_type_id',$request->integer('duty_type')))
            ->when($request->filled('status'),fn($q)=>$q->where('requirement_status',$request->input('status')))
            ->when($request->boolean('open'),fn($q)=>$q->whereIn('requirement_status',RequirementStatus::openStatuses()))
            ->when($request->filled('city'),fn($q)=>$q->whereHas('customer',fn($c)=>$c->where('city',$request->input('city'))))
            ->when($request->filled('gender'),fn($q)=>$q->where('required_gender',$request->input('gender')))
            ->when($request->filled('preferred_start_date'),fn($q)=>$q->whereDate('preferred_start_date',$request->input('preferred_start_date')))
            ->latest()->paginate(20)->withQueryString();
        return view('admin.customer-requirements.index',['requirements'=>$requirements,'services'=>Service::where('is_active',true)->orderBy('sort_order')->get(),'dutyTypes'=>DutyType::where('is_active',true)->orderBy('sort_order')->get(),'statuses'=>RequirementStatus::cases(),'cities'=>Customer::whereNotNull('city')->distinct()->orderBy('city')->pluck('city')]);
    }

    public function create(Customer $customer): View { return view('admin.customer-requirements.form',$this->formData($customer,new CustomerRequirement)); }

    public function store(StoreCustomerRequirementRequest $request, Customer $customer, CustomerManager $manager, ActivityLogger $logger): RedirectResponse
    {
        $requirement=$manager->createRequirement($customer,$request->validated(),$request->user()->id);
        $logger->log('created','customer-requirements',$requirement,'Customer requirement created.',['requirement_code'=>$requirement->requirement_code,'customer_code'=>$customer->customer_code]);
        return redirect()->route('admin.customer-requirements.show',$requirement)->with('success',"Requirement {$requirement->requirement_code} created.");
    }

    public function show(CustomerRequirement $requirement): View
    {
        $requirement->load(['customer','service','dutyType','householdDetail','accommodationDetail','workingCondition','workerPreference','statusHistory.changedBy','workerShortlists.worker','assignments.worker','replacementRequests.oldWorker','replacementRequests.newWorker','generatedDocuments.generator']);
        return view('admin.customer-requirements.show',compact('requirement'));
    }

    public function edit(CustomerRequirement $requirement): View
    {
        $requirement->load(['customer','householdDetail','accommodationDetail','workingCondition','workerPreference']);
        return view('admin.customer-requirements.form',$this->formData($requirement->customer,$requirement));
    }

    public function update(UpdateCustomerRequirementRequest $request, CustomerRequirement $requirement, CustomerManager $manager, ActivityLogger $logger): RedirectResponse
    {
        $oldStatus=$requirement->requirement_status->value;
        if ($oldStatus !== $request->validated('requirement_status')) abort_unless($request->user()->hasPermission('customer-requirements.status'),403);
        $requirement=$manager->updateRequirement($requirement,$request->validated(),$request->user()->id);
        $logger->log('updated','customer-requirements',$requirement,'Customer requirement updated.',['requirement_code'=>$requirement->requirement_code]);
        if($oldStatus!==$requirement->requirement_status->value)$logger->log('status_changed','customer-requirements',$requirement,'Requirement status changed.',['from'=>$oldStatus,'to'=>$requirement->requirement_status->value]);
        return redirect()->route('admin.customer-requirements.show',$requirement)->with('success','Requirement updated.');
    }

    private function formData(Customer $customer, CustomerRequirement $requirement): array
    {
        return ['customer'=>$customer,'requirement'=>$requirement,'services'=>Service::where('is_active',true)->orderBy('sort_order')->get(),'dutyTypes'=>DutyType::where('is_active',true)->orderBy('sort_order')->get(),'statuses'=>RequirementStatus::cases(),'experiences'=>ExperienceRequirement::cases()];
    }
}
