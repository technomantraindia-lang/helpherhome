@extends('layouts.admin')
@section('title', 'Customer Registration Form')
@section('heading', 'Customer Registration Form')
@section('subheading', $snapshot['customer']['name'].' · '.$snapshot['requirement']['requirement_code'])
@section('actions')<form method="POST" action="{{ route('admin.customer-requirements.registration-form.generate',$requirement) }}">@csrf<button class="btn-primary">Generate PDF</button></form>@endsection
@section('content')<div class="card overflow-hidden bg-neutral-100 p-4"><div class="mx-auto max-w-[850px] bg-white p-3 shadow">@include('admin.customers.documents.registration-pdf')</div></div>@endsection
