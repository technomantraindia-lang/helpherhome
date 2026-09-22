@extends('layouts.admin')
@section('title', $enquiry->enquiry_code)
@section('heading', $enquiry->enquiry_code)
@section('subheading', $enquiry->created_at->format('d M Y, H:i'))
@section('actions')
<div class="flex flex-wrap gap-2">
  <a class="btn-secondary" href="tel:{{ $enquiry->mobile_number }}">Call</a>
  <a class="btn-secondary" href="{{ route('admin.enquiries.whatsapp', $enquiry) }}">WhatsApp</a>
  @if(!$enquiry->converted_customer_id)
    <form method="POST" action="{{ route('admin.enquiries.convert', $enquiry) }}">@csrf<button class="btn-primary">Convert to Customer</button></form>
  @endif
</div>
@endsection
@section('content')
<div class="grid gap-6 lg:grid-cols-3">
  <section class="card space-y-2 p-6 lg:col-span-2">
    <h2 class="mb-4 text-xl font-black">Enquiry Details</h2>
    <p><strong>Name:</strong> {{ $enquiry->name }}</p>
    <p><strong>Mobile:</strong> {{ $enquiry->mobile_number }}</p>
    <p><strong>Email:</strong> {{ $enquiry->email ?: '—' }}</p>
    <p><strong>City / Area:</strong> {{ $enquiry->city }} / {{ $enquiry->area }}</p>
    <p><strong>Service:</strong> {{ $enquiry->service?->name ?: '—' }}</p>
    <p><strong>Duty:</strong> {{ $enquiry->dutyType?->name ?: '—' }}</p>
    <p><strong>Start date:</strong> {{ $enquiry->preferred_start_date?->format('d M Y') ?: '—' }}</p>
    <p><strong>Gender / Persons:</strong> {{ $enquiry->required_gender ?: '—' }} / {{ $enquiry->number_of_persons ?: '—' }}</p>
    <p class="whitespace-pre-line"><strong>Message:</strong><br>{{ $enquiry->message ?: '—' }}</p>
    <p><strong>Source:</strong> {{ $enquiry->source }}</p>
    <p><strong>Page:</strong> {{ $enquiry->page_url ?: '—' }}</p>
    <p><strong>UTM:</strong> {{ collect(['source'=>$enquiry->utm_source,'medium'=>$enquiry->utm_medium,'campaign'=>$enquiry->utm_campaign])->filter()->map(fn($v,$k)=>$k.'='.$v)->join(', ') ?: '—' }}</p>
  </section>
  <aside class="space-y-6">
    <section class="card p-5">
      <h2 class="mb-3 font-black">Status</h2>
      <form method="POST" action="{{ route('admin.enquiries.status', $enquiry) }}">@csrf
        <select name="status" class="mb-2 w-full">@foreach(App\Enums\EnquiryStatus::cases() as $status)<option value="{{ $status->value }}" @selected($enquiry->status === $status)>{{ $status->label() }}</option>@endforeach</select>
        <button class="btn-primary w-full">Update Status</button>
      </form>
    </section>
    <section class="card p-5">
      <h2 class="mb-3 font-black">Assignment</h2>
      <form method="POST" action="{{ route('admin.enquiries.assign', $enquiry) }}">@csrf
        <select name="assigned_to" class="mb-2 w-full">
          <option value="">Unassigned</option>
          @foreach($users as $user)<option value="{{ $user->id }}" @selected($enquiry->assigned_to === $user->id)>{{ $user->name }} ({{ $user->role?->name ?: $user->role?->slug }})</option>@endforeach
        </select>
        <button class="btn-secondary w-full">Save Assignment</button>
      </form>
    </section>
    <section class="card p-5">
      <h2 class="mb-3 font-black">Follow-Up</h2>
      <form method="POST" action="{{ route('admin.enquiries.follow-ups.store', $enquiry) }}">@csrf
        <input class="mb-2 w-full" type="date" name="follow_up_date" required>
        <input class="mb-2 w-full" type="time" name="follow_up_time">
        <textarea class="mb-2 w-full" name="notes" placeholder="Notes"></textarea>
        <button class="btn-secondary w-full">Schedule</button>
      </form>
      @foreach($enquiry->followUps as $follow)
        <p class="mt-2 text-sm">{{ $follow->follow_up_date->format('d M Y') }} · {{ $follow->status->label() }}
          @if($follow->status->value === 'pending')<form class="inline" method="POST" action="{{ route('admin.enquiry-follow-ups.complete', $follow) }}">@csrf<button>Complete</button></form>@endif
        </p>
      @endforeach
    </section>
  </aside>
</div>
<section class="card mt-6 p-6">
  <h2 class="mb-3 text-xl font-black">Contact History</h2>
  @forelse($activities as $activity)
    <div class="border-b border-neutral-100 py-2 text-sm last:border-0"><strong>{{ str_replace('_', ' ', ucfirst($activity->action)) }}</strong><span class="ml-2 text-neutral-500">{{ $activity->created_at->format('d M Y, H:i') }} @if($activity->user) · {{ $activity->user->name }} @endif</span><p class="text-neutral-600">{{ $activity->description }}</p></div>
  @empty
    <p class="text-sm text-neutral-500">No contact activity recorded yet.</p>
  @endforelse
</section>
@if($enquiry->converted_customer_id)
<div class="card mt-6 p-6"><h2 class="font-black">Converted</h2><p class="mt-2"><a class="text-gold-600" href="{{ route('admin.customers.show', $enquiry->converted_customer_id) }}">Customer: {{ $enquiry->convertedCustomer?->customer_code }}</a> · <a class="text-gold-600" href="{{ route('admin.customer-requirements.show', $enquiry->converted_requirement_id) }}">Requirement: {{ $enquiry->convertedRequirement?->requirement_code }}</a></p></div>
@endif
@endsection
