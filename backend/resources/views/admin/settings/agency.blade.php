@extends('layouts.admin')
@section('title','Agency Settings') @section('heading','Agency Settings') @section('subheading','Central business details used throughout Helper Home.')
@section('content')
<form method="POST" enctype="multipart/form-data" action="{{ route('admin.settings.agency.update') }}" class="space-y-6">@csrf @method('PUT')
@php
$sections=[
    ['Business Information', [['business_name','Business name','text'],['tagline','Tagline','text'],['owner_authorized_person','Owner / authorized person','text'],['default_currency','Default currency','text']]],
    ['Contact Information', [['phone_primary','Primary phone','text'],['phone_secondary','Secondary phone','text'],['whatsapp_number','WhatsApp number','text'],['email','Email','email']]],
    ['Address', [['address_line_1','Address line 1','text'],['address_line_2','Address line 2','text'],['city','City','text'],['state','State','text'],['pincode','Pincode','text'],['country','Country','text']]],
    ['Tax Information', [['gst_number','GST number','text'],['pan_number','PAN number','text']]],
    ['Social Media', [['facebook_url','Facebook URL','url'],['instagram_url','Instagram URL','url'],['twitter_url','X / Twitter URL','url'],['youtube_url','YouTube URL','url']]],
    ['Bank / Payment Information', [['bank_account_holder_name','Account holder name','text'],['bank_name','Bank name','text'],['bank_account_number','Account number','text'],['bank_ifsc','IFSC','text'],['bank_branch','Branch','text'],['upi_id','UPI ID','text']]],
];
@endphp
@foreach($sections as [$title,$fields])<section class="card p-6 md:p-8"><h2 class="mb-5 text-lg font-black text-neutral-900">{{ $title }}</h2><div class="grid gap-5 md:grid-cols-2">@foreach($fields as [$name,$label,$type])<div class="{{ in_array($name,['address_line_1','address_line_2'])?'md:col-span-2':'' }}"><label for="{{ $name }}">{{ $label }}</label><input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($name,$settings->{$name}) }}" @required(in_array($name,['business_name','country','default_currency']))>@error($name)<p class="field-error">{{ $message }}</p>@enderror</div>@endforeach</div></section>@endforeach
<section class="card p-6 md:p-8"><h2 class="mb-2 text-lg font-black text-neutral-900">Branding</h2><p class="mb-5 text-sm text-neutral-500">JPG, PNG, or WebP images up to 2 MB. Uploading a new file replaces the previous one.</p><div class="grid gap-5 md:grid-cols-2">@foreach([['logo','Logo','logo_path','logo'],['stamp','Stamp','stamp_path','stamp'],['signature','Signature','signature_path','signature'],['upi_qr','UPI QR code','upi_qr_path','upi']] as [$name,$label,$path,$asset])<div><label for="{{ $name }}">{{ $label }}</label>@if($settings->{$path})<a class="mb-2 block text-xs font-semibold text-gold-500" href="{{ route('admin.settings.agency.asset',$asset) }}" target="_blank" rel="noopener">View current file</a>@endif<input id="{{ $name }}" name="{{ $name }}" type="file" accept="image/jpeg,image/png,image/webp">@error($name)<p class="field-error">{{ $message }}</p>@enderror</div>@endforeach</div></section>
@if(auth()->user()->hasPermission('settings.edit'))<div class="sticky bottom-4 flex justify-end"><button class="btn-primary px-6 shadow-lg" type="submit">Save changes</button></div>@endif
</form>
@endsection
