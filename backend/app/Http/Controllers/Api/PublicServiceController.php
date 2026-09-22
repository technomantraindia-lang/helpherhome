<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
class PublicServiceController extends Controller { public function __invoke(): JsonResponse { return response()->json(['success'=>true,'data'=>Service::where('is_active',true)->orderBy('sort_order')->get(['id','name','slug','short_description'])]); } }
