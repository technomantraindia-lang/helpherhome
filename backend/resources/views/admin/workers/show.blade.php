@extends('layouts.admin')
@section('title', $worker->name)
@section('heading', $worker->name)
@section('subheading', $worker->worker_code)
@section('actions')
<div class="flex flex-wrap items-center gap-2">
    @if(auth()->user()->hasPermission('worker-registration-documents.view'))
        <a class="btn-secondary" href="{{ route('admin.workers.registration-form.download', $worker) }}">
            Download Registration PDF
        </a>
    @endif
    @if(auth()->user()->hasPermission('worker-resumes.view'))
        <a class="btn-secondary" href="{{ route('admin.workers.resume.download', $worker) }}">
            Download Resume PDF
        </a>
    @endif
    @if(auth()->user()->hasPermission('workers.edit'))
        <a class="btn-primary" href="{{ route('admin.workers.edit', $worker) }}">Edit Worker</a>
    @endif
</div>
@endsection
@section('content')
<div class="card mb-6 overflow-x-auto p-2"><div class="flex min-w-max gap-1">@foreach(['overview'=>'Overview','services'=>'Services','documents'=>'Documents','generated'=>'Generated Documents','assignments'=>'Assignments','agreements'=>'Agreements','replacements'=>'Replacement History'] as $tab=>$label)<button type="button" data-tab-button="{{ $tab }}" class="rounded-xl px-4 py-2.5 text-sm font-bold text-neutral-500 first:bg-charcoal-900 first:text-white">{{ $label }}</button>@endforeach</div></div>
<section data-tab-panel="overview" class="card p-6">
    <div class="mb-6 flex items-center gap-4 border-b border-neutral-100 pb-5">
        <div class="relative h-16 w-16 shrink-0">
            @if($worker->photo_path)
                <img class="h-16 w-16 rounded-2xl object-cover" 
                     src="{{ Storage::url($worker->photo_path) }}" 
                     alt="{{ $worker->name }}"
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='grid';">
                <span class="hidden h-16 w-16 place-items-center rounded-2xl bg-gold-500/10 text-xl font-bold text-gold-600 border border-gold-500/20">
                    {{ strtoupper(substr($worker->name, 0, 1)) }}
                </span>
            @else
                <span class="grid h-16 w-16 place-items-center rounded-2xl bg-gold-500/10 text-xl font-bold text-gold-600 border border-gold-500/20">
                    {{ strtoupper(substr($worker->name, 0, 1)) }}
                </span>
            @endif
        </div>
        <div>
            <h2 class="text-xl font-black text-neutral-900">{{ $worker->name }}</h2>
            <p class="text-sm font-mono text-neutral-500">{{ $worker->worker_code }} @if($worker->registration_number) · Reg No: {{ $worker->registration_number }} @endif</p>
        </div>
    </div>
    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
        @foreach([
            ['Worker Code',$worker->worker_code],
            ['Registration Number',$worker->registration_number],
            ['Name',$worker->name],
            ['Mobile',$worker->mobile_number],
            ['City',$worker->city],
            ['Age',$worker->age],
            ['Gender',$worker->gender?str($worker->gender)->headline():null],
            ['Experience',$worker->years_of_experience!==null?$worker->years_of_experience.' years':null],
            ['Salary Expectation',$worker->salary_expectation!==null?'₹'.number_format((float)$worker->salary_expectation,2):null],
            ['Availability',str($worker->availability_status?->value)->headline()],
            ['Worker Status',str($worker->worker_status?->value)->headline()],
            ['Verification',str($worker->verification?->overall_verification_status?->value??'pending')->headline()],
            ['Preferred Duty',$worker->preferredDutyType?->name],
            ['Registration Source',!empty($worker->registration_source) ? ($worker->registration_source==='other' && !empty($worker->registration_source_other) ? 'Other ('.$worker->registration_source_other.')' : str($worker->registration_source)->headline()) : null],
            ['Supervisor',$worker->supervisor?->name],
            ['Executive',$worker->executive?->name],
        ] as [$label,$value])
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-neutral-400">{{ $label }}</p>
                <p class="mt-1 font-semibold text-neutral-800">{{ $value ?: '—' }}</p>
            </div>
        @endforeach
    </div>
</section>
<section data-tab-panel="services" class="card hidden p-6"><div class="flex flex-wrap gap-2">@forelse($worker->services as $service)<span class="rounded-full bg-gold-500/10 px-3 py-2 text-sm font-bold text-gold-500">{{ $service->name }}</span>@empty<p>No services.</p>@endforelse</div></section>
<section data-tab-panel="documents" class="card hidden divide-y">@forelse($worker->documents as $document)<div class="p-4"><strong>{{ $document->document_name ?: str($document->document_type)->headline() }}</strong><span class="float-right">{{ str($document->verification_status->value)->headline() }}</span></div>@empty<p class="p-6">No documents.</p>@endforelse</section>
<section data-tab-panel="generated" class="card hidden p-6"><div class="mb-5 flex flex-wrap items-center justify-between gap-3"><h2 class="text-lg font-black">Generated Documents</h2><div class="flex flex-wrap gap-2">@if(auth()->user()->hasPermission('worker-resumes.generate'))<a class="btn-secondary" href="{{ route('admin.workers.resume.preview', $worker) }}">Generate Resume</a>@endif @if(auth()->user()->hasPermission('worker-registration-documents.generate'))<a class="btn-secondary" href="{{ route('admin.workers.registration-form.preview', $worker) }}">Generate Registration Form</a>@endif</div></div><div class="max-w-full overflow-x-auto"><table class="w-full min-w-[700px] text-left text-sm"><thead><tr><th class="p-3">Type</th><th class="p-3">Version</th><th class="p-3">Generated Date</th><th class="p-3">Status</th><th class="p-3">Actions</th></tr></thead><tbody>@forelse($worker->generatedDocuments as $document)<tr class="border-t"><td class="p-3">{{ $document->document_type->label() }}</td><td>v{{ $document->version_number }}</td><td>{{ $document->generated_at?->format('d M Y, h:i A') ?: '—' }}</td><td>{{ $document->status->label() }}</td><td><a href="{{ route('admin.worker-generated-documents.show', $document) }}">View</a> · <a href="{{ route('admin.worker-generated-documents.preview', $document) }}">Preview</a></td></tr>@empty<tr><td colspan="5" class="p-6">No generated documents yet.</td></tr>@endforelse</tbody></table></div></section>
<section data-tab-panel="assignments" class="card hidden divide-y">@forelse($worker->assignments as $assignment)<a class="flex justify-between p-4" href="{{ route('admin.assignments.show', $assignment) }}"><span><strong>{{ $assignment->assignment_code }}</strong> · {{ $assignment->customer?->name }} · {{ $assignment->service?->name }}</span><span>{{ $assignment->status->label() }}</span></a>@empty<p class="p-6">No assignments.</p>@endforelse</section>
<section data-tab-panel="agreements" class="card hidden divide-y">@forelse($worker->agreements as $agreement)<a class="flex justify-between p-4" href="{{ route('admin.agreements.show', $agreement) }}"><span><strong>{{ $agreement->agreement_code }}</strong> · {{ $agreement->customer_name_snapshot }} · {{ $agreement->service?->name ?: 'Service' }}</span><span>{{ $agreement->status->label() }}</span></a>@empty<p class="p-6">No agreements.</p>@endforelse</section>
<section data-tab-panel="replacements" class="card hidden divide-y">@forelse($worker->replacementsAsOldWorker->concat($worker->replacementsAsNewWorker)->unique('id') as $replacement)<a class="block p-4" href="{{ route('admin.replacements.show', $replacement) }}"><strong>{{ $replacement->replacement_code }}</strong> · {{ $replacement->customer?->name }} · {{ $replacement->status->label() }}</a>@empty<p class="p-6">No replacement history.</p>@endforelse</section>
<script>document.querySelectorAll('[data-tab-button]').forEach(b=>b.onclick=()=>{document.querySelectorAll('[data-tab-button]').forEach(x=>x.className='rounded-xl px-4 py-2.5 text-sm font-bold text-neutral-500');b.className='rounded-xl bg-charcoal-900 px-4 py-2.5 text-sm font-bold text-white';document.querySelectorAll('[data-tab-panel]').forEach(p=>p.classList.toggle('hidden',p.dataset.tabPanel!==b.dataset.tabButton))})</script>
@endsection
