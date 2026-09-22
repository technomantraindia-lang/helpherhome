@extends('layouts.admin')
@section('title', 'Enquiries')
@section('heading', 'Enquiries')
@section('subheading', 'Manage website and manually captured leads.')
@section('actions')
@if(auth()->user()->hasPermission('enquiries.create'))<a class="btn-primary" href="{{ route('admin.enquiries.create') }}">Add Enquiry</a>@endif
@endsection
@section('content')
<div class="mb-4 flex flex-wrap gap-2">
  <a class="btn-secondary" href="{{ route('admin.enquiries.index', ['status'=>'new']) }}">New Enquiries</a>
  <a class="btn-secondary" href="{{ route('admin.enquiries.index', ['status'=>'follow_up']) }}">Follow-Ups</a>
  <a class="btn-secondary" href="{{ route('admin.enquiries.index', ['status'=>'converted']) }}">Converted</a>
  <a class="btn-secondary" href="{{ route('admin.enquiries.index', ['status'=>'closed']) }}">Closed</a>
  <a class="btn-secondary" href="{{ route('admin.enquiries.index', ['follow_up'=>'today']) }}">Follow-Ups Today</a>
  <a class="btn-secondary" href="{{ route('admin.enquiries.index', ['follow_up'=>'overdue']) }}">Overdue Follow-Ups</a>
  <a class="btn-secondary" href="{{ route('admin.enquiries.index', ['follow_up'=>'upcoming']) }}">Upcoming Follow-Ups</a>
</div>
<form class="card mb-5 grid gap-3 p-4 md:grid-cols-4" method="GET">
  <input name="search" placeholder="Name, mobile, email, code, city" value="{{ request('search') }}">
  <select name="status"><option value="">All statuses</option>@foreach($statuses as $status)<option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>@endforeach</select>
  <select name="service_id"><option value="">All services</option>@foreach($services as $service)<option value="{{ $service->id }}" @selected(request('service_id') == $service->id)>{{ $service->name }}</option>@endforeach</select>
  <select name="duty_type_id"><option value="">All duty types</option>@foreach($dutyTypes as $duty)<option value="{{ $duty->id }}" @selected(request('duty_type_id') == $duty->id)>{{ $duty->name }}</option>@endforeach</select>
  <select name="source"><option value="">All sources</option>@foreach(['website','manual','phone','whatsapp','social','other'] as $source)<option value="{{ $source }}" @selected(request('source') === $source)>{{ ucfirst($source) }}</option>@endforeach</select>
  <select name="assigned_to"><option value="">All assignees</option>@foreach($users as $user)<option value="{{ $user->id }}" @selected(request('assigned_to') == $user->id)>{{ $user->name }}</option>@endforeach</select>
  <div class="flex gap-2"><input type="date" name="start_date" value="{{ request('start_date') }}"><input type="date" name="end_date" value="{{ request('end_date') }}"></div>
  <button class="btn-primary">Filter</button>
</form>
<div class="card overflow-x-auto"><table class="w-full min-w-[1050px] text-left text-sm"><thead><tr>@foreach(['Enquiry Code','Date','Name','Mobile','Service','Duty Type','City / Area','Status','Assigned To','Actions'] as $head)<th class="p-3">{{ $head }}</th>@endforeach</tr></thead><tbody class="divide-y">
@forelse($enquiries as $enquiry)
<tr><td class="p-3 font-bold"><a href="{{ route('admin.enquiries.show', $enquiry) }}">{{ $enquiry->enquiry_code }}</a></td><td>{{ $enquiry->created_at->format('d M Y H:i') }}</td><td>{{ $enquiry->name }}</td><td>{{ $enquiry->mobile_number }}</td><td>{{ $enquiry->service?->name ?: '—' }}</td><td>{{ $enquiry->dutyType?->name ?: '—' }}</td><td>{{ trim(($enquiry->city ?: '').' / '.($enquiry->area ?: ''), ' /') ?: '—' }}</td><td>{{ $enquiry->status->label() }}</td><td>{{ $enquiry->assignee?->name ?: '—' }}</td><td><a href="{{ route('admin.enquiries.show', $enquiry) }}">View</a></td></tr>
@empty<tr><td colspan="10" class="p-8 text-center text-neutral-500">No enquiries found.</td></tr>@endforelse
</tbody></table></div><div class="mt-4">{{ $enquiries->links() }}</div>
@endsection
