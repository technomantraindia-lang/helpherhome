<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\AgencySetting;
use Illuminate\Http\JsonResponse;
class PublicSettingsController extends Controller { public function __invoke(): JsonResponse { $s=AgencySetting::query()->first(); return response()->json(['success'=>true,'data'=>$s?->only(['business_name','tagline','phone_primary','whatsapp_number','email','address_line_1','address_line_2','city','state','pincode','country','facebook_url','instagram_url','twitter_url','youtube_url']) ?? []]); } }
