<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'business_name', 'tagline', 'owner_authorized_person', 'phone_primary',
    'phone_secondary', 'whatsapp_number', 'email', 'address_line_1',
    'address_line_2', 'city', 'state', 'pincode', 'country', 'gst_number',
    'pan_number', 'logo_path', 'stamp_path', 'signature_path', 'facebook_url',
    'instagram_url', 'twitter_url', 'youtube_url', 'bank_account_holder_name',
    'bank_name', 'bank_account_number', 'bank_ifsc', 'bank_branch', 'upi_id',
    'upi_qr_path', 'default_currency',
])]
class AgencySetting extends Model
{
    use HasFactory;
}
