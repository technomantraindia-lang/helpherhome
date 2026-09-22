@extends('layouts.admin')
@section('title','Global Search')
@section('heading','Global Search')
@section('subheading','Search operational records by code, name, mobile, or document number.')
@section('content')
<form class="card mb-6 flex gap-3 p-4" method="GET"><input class="flex-1" name="q" value="{{ $term }}" placeholder="Worker code, customer mobile, invoice number..."><button class="btn-primary">Search</button></form>
@if($term !== '')<div class="space-y-5">@foreach($results as $group=>$items)<section class="card overflow-hidden"><div class="border-b p-4"><h2 class="font-black">{{ $group }}</h2></div>@forelse($items as $item)<div class="flex flex-wrap justify-between gap-3 border-b p-4 text-sm"><span class="font-semibold">{{ $item->name ?? $item->customer_name_snapshot ?? $item->worker_code ?? $item->customer_code ?? $item->requirement_code ?? $item->assignment_code ?? $item->agreement_code ?? $item->invoice_number ?? $item->payment_code ?? $item->receipt_number ?? $item->document_number }}</span><span class="text-neutral-500">{{ $item->mobile_number ?? $item->status?->value ?? $item->status ?? '' }}</span></div>@empty<p class="p-4 text-sm text-neutral-500">No matches.</p>@endforelse</section>@endforeach</div>@endif
@endsection
