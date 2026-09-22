<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEnquiryFollowUpRequest;
use App\Models\Enquiry;
use App\Models\EnquiryFollowUp;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
class EnquiryFollowUpController extends Controller
{
    public function store(StoreEnquiryFollowUpRequest $request, Enquiry $enquiry, ActivityLogger $logger): RedirectResponse { $follow=$enquiry->followUps()->create($request->validated()+['created_by'=>$request->user()->id,'status'=>'pending']);$enquiry->update(['status'=>'follow_up']);$logger->log('created','enquiry-follow-ups',$follow,'Enquiry follow-up created.');return back()->with('success','Follow-up scheduled.'); }
    public function complete(Request $request, EnquiryFollowUp $followUp, ActivityLogger $logger): RedirectResponse { abort_unless($request->user()?->hasPermission('enquiries.follow-up'),403);$followUp->update(['status'=>'completed','completed_at'=>now(),'completed_by'=>$request->user()->id]);$logger->log('completed','enquiry-follow-ups',$followUp,'Enquiry follow-up completed.');return back()->with('success','Follow-up completed.'); }
}
