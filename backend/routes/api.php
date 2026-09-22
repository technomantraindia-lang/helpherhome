<?php

use App\Http\Controllers\Api\PublicDutyTypeController;
use App\Http\Controllers\Api\PublicEnquiryController;
use App\Http\Controllers\Api\PublicServiceController;
use App\Http\Controllers\Api\PublicSettingsController;
use Illuminate\Support\Facades\Route;

Route::prefix('public')->middleware('throttle:public-enquiries')->group(function () {
    Route::get('/services', PublicServiceController::class)->withoutMiddleware('throttle:public-enquiries')->name('api.public.services');
    Route::get('/duty-types', PublicDutyTypeController::class)->withoutMiddleware('throttle:public-enquiries')->name('api.public.duty-types');
    Route::get('/settings/contact', PublicSettingsController::class)->withoutMiddleware('throttle:public-enquiries')->name('api.public.settings.contact');
    Route::post('/enquiries', [PublicEnquiryController::class, 'store'])->name('api.public.enquiries.store');
});
