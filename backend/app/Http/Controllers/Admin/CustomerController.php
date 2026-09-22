<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CustomerStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\DutyType;
use App\Models\Service;
use App\Services\ActivityLogger;
use App\Services\CustomerManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $customers = Customer::query()->with(['requirements'=>fn($q)=>$q->with(['service:id,name','dutyType:id,name'])->latest()])
            ->when($request->filled('search'), function ($q) use ($request) { $term='%'.$request->string('search')->trim().'%'; $q->where(fn($x)=>$x->where('customer_code','like',$term)->orWhere('name','like',$term)->orWhere('mobile_number','like',$term)->orWhere('email','like',$term)->orWhere('city','like',$term)); })
            ->when($request->filled('status'), fn($q)=>$q->where('status',$request->input('status')))
            ->when($request->filled('city'), fn($q)=>$q->where('city',$request->input('city')))
            ->when($request->filled('registration_date'), fn($q)=>$q->whereDate('registration_date',$request->input('registration_date')))
            ->latest()->paginate(20)->withQueryString();
        return view('admin.customers.index',['customers'=>$customers,'statuses'=>CustomerStatus::cases(),'cities'=>Customer::whereNotNull('city')->distinct()->orderBy('city')->pluck('city')]);
    }

    public function create(): View { return view('admin.customers.form', $this->formData(new Customer)); }

    public function store(StoreCustomerRequest $request, CustomerManager $manager, ActivityLogger $logger): RedirectResponse
    {
        $data=$request->validated();
        $duplicate=Customer::where('mobile_number',$data['mobile_number'])->first();
        if ($duplicate && !($data['force_duplicate'] ?? false)) return back()->withInput()->with('duplicate_customer_id',$duplicate->id)->with('duplicate_customer_code',$duplicate->customer_code);
        $customer=$manager->register($data,$request->user()->id);
        $logger->log('created','customers',$customer,'Customer registered.',['customer_code'=>$customer->customer_code]);
        $requirement=$customer->requirements->first();
        $logger->log('created','customer-requirements',$requirement,'Customer requirement created.',['requirement_code'=>$requirement->requirement_code]);
        return redirect()->route('admin.customers.show',$customer)->with('success',"Customer {$customer->customer_code} and requirement registered successfully.");
    }

    public function show(Customer $customer): View
    {
        $customer->load(['requirements.service','requirements.dutyType','requirements.statusHistory.changedBy','assignments.worker','assignments.service','replacementRequests.oldWorker','replacementRequests.newWorker','agreements.worker','agreements.service','invoices.assignment.service','payments.invoice','payments.receipt','generatedDocuments.requirement','generatedDocuments.generator']);
        $activities=ActivityLog::where('subject_type',$customer->getMorphClass())->where('subject_id',$customer->id)->with('user')->latest()->get();
        return view('admin.customers.show',compact('customer','activities'));
    }

    public function edit(Customer $customer): View { return view('admin.customers.edit',compact('customer')); }

    public function update(UpdateCustomerRequest $request, Customer $customer, CustomerManager $manager, ActivityLogger $logger): RedirectResponse
    {
        $oldStatus=$customer->status->value;
        if ($oldStatus !== $request->validated('status')) abort_unless($request->user()->hasPermission('customers.status'),403);
        $customer=$manager->updateCustomer($customer,$request->validated(),$request->user()->id);
        $logger->log('updated','customers',$customer,'Customer updated.',['customer_code'=>$customer->customer_code]);
        if ($oldStatus !== $customer->status->value) $logger->log('status_changed','customers',$customer,'Customer status changed.',['from'=>$oldStatus,'to'=>$customer->status->value]);
        return redirect()->route('admin.customers.show',$customer)->with('success','Customer details updated.');
    }

    private function formData(Customer $customer): array
    {
        return ['customer'=>$customer,'requirement'=>null,'services'=>Service::where('is_active',true)->orderBy('sort_order')->get(),'dutyTypes'=>DutyType::where('is_active',true)->orderBy('sort_order')->get(),'customerStatuses'=>CustomerStatus::cases()];
    }
}
