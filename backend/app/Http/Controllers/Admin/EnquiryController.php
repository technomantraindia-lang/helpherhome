<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EnquiryStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEnquiryRequest;
use App\Http\Requests\UpdateEnquiryRequest;
use App\Http\Requests\UpdateEnquiryStatusRequest;
use App\Models\ActivityLog;
use App\Models\Enquiry;
use App\Models\Service;
use App\Models\DutyType;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\EnquiryConversionService;
use App\Services\EnquiryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class EnquiryController extends Controller
{
    public function index(Request $request): View
    {
        $enquiries=Enquiry::query()->with(['service','dutyType','assignee'])->when($request->filled('search'),function($q)use($request){$term='%'.$request->string('search')->trim().'%';$q->where(fn($x)=>$x->where('enquiry_code','like',$term)->orWhere('name','like',$term)->orWhere('mobile_number','like',$term)->orWhere('email','like',$term)->orWhere('city','like',$term));})->when($request->filled('status'),fn($q)=>$q->where('status',$request->input('status')))->when($request->filled('service_id'),fn($q)=>$q->where('service_id',$request->input('service_id')))->when($request->filled('duty_type_id'),fn($q)=>$q->where('duty_type_id',$request->input('duty_type_id')))->when($request->filled('source'),fn($q)=>$q->where('source',$request->input('source')))->when($request->filled('assigned_to'),fn($q)=>$q->where('assigned_to',$request->input('assigned_to')))->when($request->filled('follow_up'),function($q)use($request){$window=$request->input('follow_up');$q->whereHas('followUps',fn($fq)=>$fq->where('status','pending')->when($window==='overdue',fn($x)=>$x->whereDate('follow_up_date','<',today()))->when($window==='today',fn($x)=>$x->whereDate('follow_up_date',today()))->when($window==='upcoming',fn($x)=>$x->whereDate('follow_up_date','>',today()))); })->when($request->filled('start_date'),fn($q)=>$q->whereDate('created_at','>=',$request->input('start_date')))->when($request->filled('end_date'),fn($q)=>$q->whereDate('created_at','<=',$request->input('end_date')))->latest()->paginate(25)->withQueryString();
        return view('admin.enquiries.index',['enquiries'=>$enquiries,'statuses'=>EnquiryStatus::cases(),'services'=>Service::where('is_active',true)->orderBy('name')->get(),'dutyTypes'=>DutyType::where('is_active',true)->orderBy('sort_order')->get(),'users'=>User::where('is_active',true)->whereHas('role',fn($q)=>$q->whereIn('slug',['admin','staff','super-admin']))->orderBy('name')->get()]);
    }
    public function create(): View { return view('admin.enquiries.form',['enquiry'=>new Enquiry,'services'=>Service::where('is_active',true)->get(),'dutyTypes'=>DutyType::where('is_active',true)->get()]); }
    public function store(StoreEnquiryRequest $request, EnquiryService $service, ActivityLogger $logger): RedirectResponse { $e=$service->createManual($request->validated(),$request->user()->id);$logger->log('created','enquiries',$e,'Manual enquiry created.');return redirect()->route('admin.enquiries.show',$e)->with('success','Enquiry created.'); }
    public function show(Enquiry $enquiry): View { $enquiry->load(['service','dutyType','assignee','followUps.assignee','convertedCustomer','convertedRequirement']);$activities=ActivityLog::where('subject_type',$enquiry->getMorphClass())->where('subject_id',$enquiry->id)->with('user')->latest()->get();$users=User::where('is_active',true)->whereHas('role',fn($q)=>$q->whereIn('slug',['admin','staff','super-admin']))->orderBy('name')->get();return view('admin.enquiries.show',compact('enquiry','activities','users')); }
    public function update(UpdateEnquiryRequest $request, Enquiry $enquiry, EnquiryService $service, ActivityLogger $logger): RedirectResponse { $data=$request->validated();$data['mobile_number']=$service->normalizeMobile($data['mobile_number']);$enquiry->update($data);$logger->log('updated','enquiries',$enquiry,'Enquiry details updated.');return back()->with('success','Enquiry updated.'); }
    public function status(UpdateEnquiryStatusRequest $request, Enquiry $enquiry, ActivityLogger $logger): RedirectResponse { $old=$enquiry->status->value;$new=$request->validated('status');$enquiry->update(['status'=>$new,'first_contacted_at'=>($new==='contacted'&&!$enquiry->first_contacted_at)?now():$enquiry->first_contacted_at,'last_contacted_at'=>$new==='contacted'?now():$enquiry->last_contacted_at]);$logger->log('status_changed','enquiries',$enquiry,'Enquiry status changed.',['from'=>$old,'to'=>$new]);return back()->with('success','Enquiry status updated.'); }
    public function assign(Request $request, Enquiry $enquiry, ActivityLogger $logger): RedirectResponse { $data=$request->validate(['assigned_to'=>'nullable|exists:users,id']);if($data['assigned_to'] && !User::whereKey($data['assigned_to'])->where('is_active',true)->whereHas('role',fn($q)=>$q->whereIn('slug',['admin','staff','super-admin']))->exists()) return back()->withErrors(['assigned_to'=>'Choose an active admin or staff member.']);$enquiry->update(['assigned_to'=>$data['assigned_to']]);$logger->log('assigned','enquiries',$enquiry,'Enquiry assigned to staff.',['assigned_to'=>$data['assigned_to']]);return back()->with('success','Enquiry assignment updated.'); }
    public function whatsapp(Enquiry $enquiry, ActivityLogger $logger): RedirectResponse { $phone=preg_replace('/\D+/','',$enquiry->mobile_number);$message="Hello {$enquiry->name},\n\nThank you for contacting Helper Home regarding ".($enquiry->service?->name ?: 'your requirement').".\n\nWe would like to understand your requirement in more detail.\n\nRegards,\nHelper Home";$logger->log('whatsapp','enquiries',$enquiry,'WhatsApp action initiated.');return redirect()->away('https://wa.me/'.$phone.'?text='.rawurlencode($message)); }
    public function convert(Request $request, Enquiry $enquiry, EnquiryConversionService $conversion, ActivityLogger $logger): RedirectResponse { $data=$request->validate(['existing_customer_id'=>'nullable|exists:customers,id']);$result=$conversion->convert($enquiry,$request->user()->id,$data['existing_customer_id']??null);$logger->log('conversion','enquiries',$result['enquiry'],'Enquiry converted to customer and requirement.',['customer_id'=>$result['customer']?->id,'requirement_id'=>$result['requirement']?->id]);return back()->with('success',$result['already_converted']?'Enquiry was already converted.':'Enquiry converted successfully.'); }
}
