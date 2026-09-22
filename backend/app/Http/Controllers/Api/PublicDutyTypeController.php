<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\DutyType;
use Illuminate\Http\JsonResponse;
class PublicDutyTypeController extends Controller { public function __invoke(): JsonResponse { return response()->json(['success'=>true,'data'=>DutyType::where('is_active',true)->orderBy('sort_order')->get(['id','name','slug','description'])]); } }
