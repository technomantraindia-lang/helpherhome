@extends('layouts.admin')
@section('title', 'Customer Registration Form')
@section('heading', 'Customer Registration Form')
@section('subheading', 'Historical document snapshot')
@section('actions')<a class="btn-secondary" target="_blank" href="{{ route('admin.customer-generated-documents.print',$document) }}">Print</a>@endsection
@section('content')<div class="card overflow-hidden bg-neutral-100 p-4"><div class="mx-auto max-w-[850px] bg-white p-3 shadow">@include('admin.customers.documents.registration-pdf')</div></div>@endsection
