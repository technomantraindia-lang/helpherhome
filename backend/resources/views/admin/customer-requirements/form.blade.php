@extends('layouts.admin')
@section('title',$requirement->exists?'Edit Requirement':'Add Requirement') @section('heading',$requirement->exists?'Edit Service Requirement':'Add Service Requirement') @section('subheading',$customer->customer_code.' · '.$customer->name)
@section('content')
<form id="requirementForm" method="POST" action="{{ $requirement->exists?route('admin.customer-requirements.update',$requirement):route('admin.customers.requirements.store',$customer) }}">@csrf @if($requirement->exists)@method('PUT')@endif
<div class="card mb-6 overflow-x-auto p-2"><div class="flex min-w-max gap-1">@foreach(['requirement'=>'Service','household'=>'House & Family','accommodation'=>'Accommodation','working'=>'Working Conditions','preferences'=>'Preferences'] as $step=>$label)<button type="button" data-step-button="{{ $step }}" class="rounded-xl px-4 py-2.5 text-sm font-bold text-neutral-500 first:bg-charcoal-900 first:text-white">{{ $label }}</button>@endforeach</div></div>
@include('admin.customer-requirements._fields',['registration'=>false])
<div class="mt-6 flex flex-wrap gap-3"><button class="btn-primary" type="submit">{{ $requirement->exists?'Update Requirement':'Create Requirement' }}</button><a class="btn-secondary" href="{{ $requirement->exists?route('admin.customer-requirements.show',$requirement):route('admin.customers.show',$customer) }}">Cancel</a></div></form>
@include('admin.customer-requirements._form-script')
@endsection
