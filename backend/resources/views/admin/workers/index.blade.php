@extends('layouts.admin')
@section('title','Workers') @section('heading','Workers') @section('subheading','Search, filter, and manage registered workers.')
@section('actions') @if(auth()->user()->hasPermission('workers.create'))<a href="{{ route('admin.workers.create') }}" class="btn-primary">+ Add worker</a>@endif @endsection
@section('content')
<form method="GET" class="card mb-6 grid gap-3 p-4 md:grid-cols-4 xl:grid-cols-8">
    <input class="md:col-span-2" name="search" value="{{ request('search') }}" placeholder="Code, name, mobile or city">
    <select name="service"><option value="">All services</option>@foreach($services as $service)<option value="{{ $service->id }}" @selected(request('service')==$service->id)>{{ $service->name }}</option>@endforeach</select>
    <select name="availability"><option value="">All availability</option>@foreach($availabilityStatuses as $status)<option value="{{ $status->value }}" @selected(request('availability')===$status->value)>{{ str($status->value)->headline() }}</option>@endforeach</select>
    <select name="status"><option value="">All statuses</option>@foreach($workerStatuses as $status)<option value="{{ $status->value }}" @selected(request('status')===$status->value)>{{ str($status->value)->headline() }}</option>@endforeach</select>
    <select name="verification"><option value="">All verification</option>@foreach($verificationStatuses as $status)<option value="{{ $status->value }}" @selected(request('verification')===$status->value)>{{ str($status->value)->headline() }}</option>@endforeach</select>
    <select name="gender"><option value="">All genders</option>@foreach(['male','female','other','prefer_not_to_say'] as $gender)<option value="{{ $gender }}" @selected(request('gender')===$gender)>{{ str($gender)->headline() }}</option>@endforeach</select>
    <button class="btn-primary" type="submit">Filter</button>
</form>
<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="border-b bg-neutral-50 text-xs uppercase tracking-wider text-neutral-500">
                <tr>
                    <th class="px-4 py-4">Worker</th>
                    <th class="px-4 py-4">Mobile</th>
                    <th class="px-4 py-4">Primary Service</th>
                    <th class="px-4 py-4">City</th>
                    <th class="px-4 py-4">Experience</th>
                    <th class="px-4 py-4">Availability</th>
                    <th class="px-4 py-4">Verification</th>
                    <th class="px-4 py-4">Status</th>
                    <th class="px-4 py-4">Supervisor</th>
                    <th class="px-4 py-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
                @forelse($workers as $worker)
                    <tr class="hover:bg-neutral-50/70 transition">
                        <td class="px-4 py-3 align-middle">
                            <div class="flex items-center gap-3">
                                <div class="relative h-10 w-10 shrink-0">
                                    @if($worker->photo_path)
                                        <img class="h-10 w-10 rounded-xl object-cover" 
                                             src="{{ Storage::url($worker->photo_path) }}" 
                                             alt="{{ $worker->name }}"
                                             loading="lazy"
                                             onerror="this.style.display='none'; this.nextElementSibling.style.display='grid';">
                                        <span class="hidden h-10 w-10 place-items-center rounded-xl bg-gold-500/10 font-bold text-gold-600 text-sm border border-gold-500/20">
                                            {{ strtoupper(substr($worker->name, 0, 1)) }}
                                        </span>
                                    @else
                                        <span class="grid h-10 w-10 place-items-center rounded-xl bg-gold-500/10 font-bold text-gold-600 text-sm border border-gold-500/20">
                                            {{ strtoupper(substr($worker->name, 0, 1)) }}
                                        </span>
                                    @endif
                                </div>
                                <div>
                                    <a class="font-bold text-neutral-900 hover:text-gold-500" href="{{ route('admin.workers.show', $worker) }}">{{ $worker->name }}</a>
                                    <p class="font-mono text-xs text-neutral-400">{{ $worker->worker_code }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 align-middle font-medium text-neutral-700">{{ $worker->mobile_number }}</td>
                        <td class="px-4 py-3 align-middle">{{ $worker->services->first()?->name ?? '—' }}</td>
                        <td class="px-4 py-3 align-middle">{{ $worker->city }}</td>
                        <td class="px-4 py-3 align-middle">{{ $worker->years_of_experience !== null ? $worker->years_of_experience.' yrs' : '—' }}</td>
                        <td class="px-4 py-3 align-middle"><span class="badge-active">{{ str($worker->availability_status->value)->headline() }}</span></td>
                        <td class="px-4 py-3 align-middle"><span class="{{ $worker->verification?->overall_verification_status?->value==='verified'?'badge-active':'badge-inactive' }}">{{ str($worker->verification?->overall_verification_status?->value ?? 'pending')->headline() }}</span></td>
                        <td class="px-4 py-3 align-middle"><span class="{{ $worker->worker_status->value==='active'?'badge-active':'badge-inactive' }}">{{ str($worker->worker_status->value)->headline() }}</span></td>
                        <td class="px-4 py-3 align-middle text-neutral-600">{{ $worker->supervisor?->name ?? '—' }}</td>
                        <td class="px-4 py-3 align-middle text-right">
                            <div class="relative inline-block text-left">
                                <button type="button" class="action-dropdown-btn inline-flex items-center gap-1.5 rounded-lg border border-neutral-200 bg-white px-3 py-1.5 text-xs font-bold text-neutral-700 shadow-sm transition hover:bg-neutral-50 hover:border-gold-500">
                                    Actions
                                    <svg class="h-3 w-3 text-neutral-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" /></svg>
                                </button>
                                <div class="action-dropdown-menu hidden absolute right-0 z-30 mt-1 w-48 rounded-xl border border-neutral-200 bg-white py-1.5 shadow-xl text-left">
                                    <a class="flex items-center px-3.5 py-2 text-xs font-semibold text-neutral-700 hover:bg-neutral-50 hover:text-gold-600" href="{{ route('admin.workers.show', $worker) }}">
                                        View Worker
                                    </a>
                                    @if(auth()->user()->hasPermission('workers.edit'))
                                    <a class="flex items-center px-3.5 py-2 text-xs font-semibold text-neutral-700 hover:bg-neutral-50 hover:text-gold-600" href="{{ route('admin.workers.edit', $worker) }}">
                                        Edit Worker
                                    </a>
                                    @endif
                                    @if(auth()->user()->hasPermission('worker-registration-documents.view'))
                                    <a class="flex items-center px-3.5 py-2 text-xs font-semibold text-neutral-700 hover:bg-neutral-50 hover:text-gold-600" href="{{ route('admin.workers.registration-form.download', $worker) }}">
                                        Registration PDF
                                    </a>
                                    @endif
                                    @if(auth()->user()->hasPermission('worker-resumes.view'))
                                    <a class="flex items-center px-3.5 py-2 text-xs font-semibold text-neutral-700 hover:bg-neutral-50 hover:text-gold-600" href="{{ route('admin.workers.resume.download', $worker) }}">
                                        Resume PDF
                                    </a>
                                    @endif
                                    @if(auth()->user()->hasPermission('worker-documents.view'))
                                    <a class="flex items-center px-3.5 py-2 text-xs font-semibold text-neutral-700 hover:bg-neutral-50 hover:text-gold-600" href="{{ route('admin.worker-documents.index', ['search' => $worker->worker_code]) }}">
                                        Documents
                                    </a>
                                    @endif
                                    @if(auth()->user()->hasPermission('worker-verification.view'))
                                    <a class="flex items-center px-3.5 py-2 text-xs font-semibold text-neutral-700 hover:bg-neutral-50 hover:text-gold-600" href="{{ route('admin.workers.verification.edit', $worker) }}">
                                        Verification
                                    </a>
                                    @endif
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="px-6 py-12 text-center text-neutral-500">No workers match these filters.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($workers->hasPages())
        <div class="border-t px-5 py-4">{{ $workers->links() }}</div>
    @endif
</div>
<script>
document.addEventListener('click', e => {
    const btn = e.target.closest('.action-dropdown-btn');
    const allMenus = document.querySelectorAll('.action-dropdown-menu');
    if (btn) {
        const menu = btn.nextElementSibling;
        const isOpen = !menu.classList.contains('hidden');
        allMenus.forEach(m => m.classList.add('hidden'));
        if (!isOpen) menu.classList.remove('hidden');
    } else if (!e.target.closest('.action-dropdown-menu')) {
        allMenus.forEach(m => m.classList.add('hidden'));
    }
});
</script>
@endsection
