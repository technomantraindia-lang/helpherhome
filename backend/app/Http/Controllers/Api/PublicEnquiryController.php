<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePublicEnquiryRequest;
use App\Services\ActivityLogger;
use App\Services\EnquiryService;
use Illuminate\Http\JsonResponse;
class PublicEnquiryController extends Controller
{
    public function store(StorePublicEnquiryRequest $request, EnquiryService $service, ActivityLogger $logger): JsonResponse
    {
        if ($request->filled('website')) return response()->json(['success'=>true,'message'=>'Enquiry received successfully.','data'=>['enquiry_code'=>null]], 201);
        $enquiry=$service->createPublic($request->validated(),$request->ip(),$request->userAgent());
        $logger->log('created','enquiries',$enquiry,'Website enquiry created.',['source'=>'website'], $request);
        return response()->json(['success'=>true,'message'=>'Enquiry received successfully.','data'=>['enquiry_code'=>$enquiry->enquiry_code]],201);
    }
}
