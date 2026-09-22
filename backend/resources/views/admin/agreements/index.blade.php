@extends('layouts.admin')
@section('title', 'Agreements')
@section('heading', 'Agreements')
@section('subheading', 'Versioned service agreements and signature status.')

@section('actions')
    @if(auth()->user()->hasPermission('agreements.create'))
        <a class="btn-primary" href="{{ route('admin.agreements.create') }}">+ Create Agreement</a>
    @endif
@endsection

@section('content')
    <form class="card mb-6 grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-4" method="GET">
        <input name="search" value="{{ request('search') }}" placeholder="Code, customer, worker, mobile">
        <select name="status"><option value="">All statuses</option>@foreach($statuses as $status)<option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>@endforeach</select>
        <select name="service"><option value="">All services</option>@foreach($services as $service)<option value="{{ $service->id }}" @selected(request('service') == $service->id)>{{ $service->name }}</option>@endforeach</select>
        <select name="customer"><option value="">All customers</option>@foreach($customers as $customer)<option value="{{ $customer->id }}" @selected(request('customer') == $customer->id)>{{ $customer->name }}</option>@endforeach</select>
        <select name="worker"><option value="">All workers</option>@foreach($workers as $worker)<option value="{{ $worker->id }}" @selected(request('worker') == $worker->id)>{{ $worker->name }}</option>@endforeach</select>
        <input type="date" name="date" value="{{ request('date') }}">
        <button class="btn-primary">Filter</button>
        <a class="btn-secondary text-center" href="{{ route('admin.agreements.index') }}">Clear</a>
    </form>

    <div class="card overflow-x-auto"><table class="w-full min-w-[1100px] text-left text-sm">
        <thead class="bg-neutral-50"><tr>@foreach(['Agreement Code', 'Customer', 'Worker', 'Service', 'Assignment', 'Start', 'End', 'Salary', 'Agency Charge', 'Status', 'Agreement Date', 'Actions'] as $heading)<th class="px-4 py-3">{{ $heading }}</th>@endforeach</tr></thead>
        <tbody class="divide-y">@forelse($agreements as $agreement)<tr>
            <td class="px-4 py-4 font-bold">{{ $agreement->agreement_code }}</td><td class="px-4">{{ $agreement->customer_name_snapshot }}</td><td class="px-4">{{ $agreement->worker_name_snapshot ?: '—' }}</td><td class="px-4">{{ $agreement->service?->name ?: '—' }}</td><td class="px-4">{{ $agreement->assignment?->assignment_code ?: 'Manual' }}</td><td class="px-4">{{ $agreement->start_date?->format('d M Y') ?: '—' }}</td><td class="px-4">{{ $agreement->end_date?->format('d M Y') ?: '—' }}</td><td class="px-4">{{ $agreement->monthly_salary ? '₹'.number_format((float) $agreement->monthly_salary) : '—' }}</td><td class="px-4">{{ $agreement->monthly_agency_service_charge ? '₹'.number_format((float) $agreement->monthly_agency_service_charge) : '—' }}</td><td class="px-4"><span class="badge-neutral">{{ $agreement->status->label() }}</span></td><td class="px-4">{{ $agreement->agreement_date->format('d M Y') }}</td><td class="px-4"><a class="font-bold text-gold-500" href="{{ route('admin.agreements.show', $agreement) }}">View</a></td>
        </tr>@empty<tr><td colspan="12" class="p-10 text-center">No agreements found.</td></tr>@endforelse</tbody>
    </table></div>
    <div class="mt-4">{{ $agreements->links() }}</div>
@endsection
