<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agency_settings', function (Blueprint $table) {
            $table->id();
            $table->string('business_name');
            $table->string('tagline')->nullable();
            $table->string('owner_authorized_person')->nullable();
            $table->string('phone_primary', 30)->nullable();
            $table->string('phone_secondary', 30)->nullable();
            $table->string('whatsapp_number', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('pincode', 12)->nullable();
            $table->string('country')->default('India');
            $table->string('gst_number', 30)->nullable();
            $table->string('pan_number', 20)->nullable();
            $table->string('logo_path')->nullable();
            $table->string('stamp_path')->nullable();
            $table->string('signature_path')->nullable();
            $table->string('facebook_url')->nullable();
            $table->string('instagram_url')->nullable();
            $table->string('twitter_url')->nullable();
            $table->string('youtube_url')->nullable();
            $table->string('bank_account_holder_name')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_ifsc', 20)->nullable();
            $table->string('bank_branch')->nullable();
            $table->string('upi_id')->nullable();
            $table->string('upi_qr_path')->nullable();
            $table->string('default_currency', 3)->default('INR');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agency_settings');
    }
};
