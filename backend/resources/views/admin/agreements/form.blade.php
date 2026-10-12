@extends('layouts.admin')
@section('title', $agreement->exists ? 'Edit Agreement' : 'Create Agreement')
@section('heading', $agreement->exists ? 'Edit Agreement' : 'Create Agreement')
@section('subheading', 'Review auto-loaded records and capture a legal snapshot.')

@section('content')
@php($a = $selectedAssignment)

@if($agreement->exists && $agreement->pdf_version)
    <div class="mb-5 flex items-center gap-3 rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">
        <svg class="h-5 w-5 shrink-0 text-amber-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
        <div>This agreement already has PDF version <strong>v{{ $agreement->pdf_version }}</strong>. Saving changes will update the agreement record without overwriting the generated PDF. You can explicitly generate a new PDF version from the agreement page.</div>
    </div>
@endif

<form id="agreementForm" enctype="multipart/form-data" method="POST" action="{{ $agreement->exists ? route('admin.agreements.update', $agreement) : route('admin.agreements.store') }}">
@csrf
@if($agreement->exists)
    @method('PUT')
@endif

{{-- Wizard Steps Header --}}
<div class="card mb-6 overflow-x-auto p-2">
    <div class="flex min-w-max gap-1">
        @foreach(['source'=>'1. Source','agency'=>'2. Agency','customer'=>'3. Customer','service'=>'4. Worker & Service','financial'=>'5. Service Terms','terms'=>'6. Terms','signatures'=>'7. Signatures','review'=>'8. Review'] as $step=>$label)
            <button type="button" data-step="{{ $step }}" class="rounded-xl px-4 py-2.5 text-sm font-bold transition text-neutral-500 first:bg-charcoal-900 first:text-white">
                {{ $label }}
            </button>
        @endforeach
    </div>
</div>

{{-- STEP 1: SOURCE --}}
<section data-panel="source" class="card p-6 md:p-8">
    <div class="mb-5 border-b border-neutral-100 pb-4">
        <h2 class="text-lg font-black text-neutral-900">1. Agreement Source</h2>
        <p class="text-sm text-neutral-500">Choose whether this agreement originates from an active Assignment or is created manually.</p>
    </div>

    <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
        <div class="lg:col-span-3">
            <label>Assignment Source (Recommended)</label>
            <select id="assignmentSelect" name="assignment_id">
                <option value="">Manual Agreement (No linked assignment)</option>
                @foreach($assignments as $item)
                    <option value="{{ $item->id }}" @selected(old('assignment_id', $agreement->assignment_id ?? $a?->id) == $item->id)>
                        {{ $item->assignment_code }} · Customer: {{ $item->customer->name }} · Worker: {{ $item->worker->name }}
                    </option>
                @endforeach
            </select>
            <p class="mt-1.5 text-xs text-neutral-500">Selecting an assignment automatically pre-fills customer, worker, requirement, duty, and financial details.</p>
        </div>

        @if($a)
            <div>
                <label>Linked Customer</label>
                <input class="bg-neutral-50 font-semibold text-neutral-800" readonly value="{{ $a->customer->name }} ({{ $a->customer->customer_code }})">
                <input type="hidden" name="customer_id" value="{{ $a->customer_id }}">
            </div>
            <div>
                <label>Linked Requirement</label>
                <input class="bg-neutral-50 font-semibold text-neutral-800" readonly value="{{ $a->requirement->requirement_code }} · {{ $a->service?->name }}">
                <input type="hidden" name="customer_requirement_id" value="{{ $a->customer_requirement_id }}">
                <input type="hidden" name="worker_id" value="{{ $a->worker_id }}">
            </div>
        @else
            <div>
                <label>Customer <span class="text-red-500">*</span></label>
                <select name="customer_id" required>
                    <option value="">Select Customer</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}" @selected(old('customer_id', $agreement->customer_id) == $c->id)>
                            {{ $c->customer_code }} · {{ $c->name }} ({{ $c->mobile_number }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>Customer Requirement</label>
                <select name="customer_requirement_id">
                    <option value="">Optional (Select if available)</option>
                    @foreach($requirements as $r)
                        <option value="{{ $r->id }}" @selected(old('customer_requirement_id', $agreement->customer_requirement_id) == $r->id)>
                            {{ $r->requirement_code }} · {{ $r->service?->name }} ({{ $r->customer?->name }})
                        </option>
                    @endforeach
                </select>
            </div>
        @endif

        <div>
            <label>Agreement Date <span class="text-red-500">*</span></label>
            <input required type="date" name="agreement_date" value="{{ old('agreement_date', $agreement->agreement_date?->format('Y-m-d') ?? today()->format('Y-m-d')) }}">
        </div>
    </div>

    <div class="mt-8 flex justify-end border-t border-neutral-100 pt-5">
        <button type="button" data-next-step class="btn-primary">Next: Agency Details →</button>
    </div>
</section>

{{-- STEP 2: AGENCY --}}
<section data-panel="agency" class="card hidden p-6 md:p-8">
    <div class="mb-5 border-b border-neutral-100 pb-4">
        <h2 class="text-lg font-black text-neutral-900">2. Agency Details</h2>
        <p class="text-sm text-neutral-500">Legal branding, business details, logo, and seal are snapshotted from Agency Settings when this agreement is saved.</p>
    </div>

    <div class="grid gap-5 md:grid-cols-2">
        <div>
            <label>Authorized Signatory Person</label>
            <input name="authorized_person_name" value="{{ old('authorized_person_name', $agreement->authorized_person_name ?? $a?->assignedBy?->name ?? 'Helper Home Representative') }}" placeholder="Name of agency authorized signatory">
            <p class="mt-1.5 text-xs text-neutral-500">Appears on the legal signature block of the agreement.</p>
        </div>
    </div>

    <div class="mt-8 flex justify-between border-t border-neutral-100 pt-5">
        <button type="button" data-prev-step class="btn-secondary">← Back</button>
        <button type="button" data-next-step class="btn-primary">Next: Customer Snapshot →</button>
    </div>
</section>

{{-- STEP 3: CUSTOMER --}}
<section data-panel="customer" class="card hidden p-6 md:p-8">
    <div class="mb-5 border-b border-neutral-100 pb-4">
        <h2 class="text-lg font-black text-neutral-900">3. Customer Snapshot</h2>
        <p class="text-sm text-neutral-500">Legal snapshot of customer credentials preserved specifically for this agreement.</p>
    </div>

    <div class="grid gap-5 md:grid-cols-2">
        <div>
            <label>Customer Legal Name</label>
            <input name="customer_name_snapshot" value="{{ old('customer_name_snapshot', $agreement->customer_name_snapshot ?? $a?->customer->name) }}" placeholder="Full Name">
        </div>
        <div>
            <label>Customer Mobile Number</label>
            <input name="customer_mobile_snapshot" value="{{ old('customer_mobile_snapshot', $agreement->customer_mobile_snapshot ?? $a?->customer->mobile_number) }}" placeholder="Mobile Number">
        </div>
        <div>
            <label>Customer Email</label>
            <input type="email" name="customer_email_snapshot" value="{{ old('customer_email_snapshot', $agreement->customer_email_snapshot ?? $a?->customer->email) }}" placeholder="Email Address">
        </div>
        <div class="md:col-span-2">
            <label>Agreement-Specific Residence Address</label>
            <textarea name="customer_address_snapshot" rows="3" placeholder="Complete service location address">{{ old('customer_address_snapshot', $agreement->customer_address_snapshot ?? ($a ? collect([$a->customer->address_line_1, $a->customer->address_line_2, $a->customer->location, $a->customer->city, $a->customer->state, $a->customer->pincode, $a->customer->country])->filter()->join(', ') : '')) }}</textarea>
        </div>
    </div>

    <div class="mt-8 flex justify-between border-t border-neutral-100 pt-5">
        <button type="button" data-prev-step class="btn-secondary">← Back</button>
        <button type="button" data-next-step class="btn-primary">Next: Worker & Service →</button>
    </div>
</section>

{{-- STEP 4: WORKER & SERVICE (Fix for Image 2) --}}
<section data-panel="service" class="card hidden p-6 md:p-8">
    <div class="mb-5 border-b border-neutral-100 pb-4">
        <h2 class="text-lg font-black text-neutral-900">4. Worker & Service Details</h2>
        <p class="text-sm text-neutral-500">Select the worker and specify service specifications for this agreement.</p>
    </div>

    <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
        <div class="md:col-span-2 lg:col-span-3">
            <label>Assigned Worker</label>
            <select id="agreementWorkerSelect" name="worker_id">
                <option value="">Select Worker (or leave blank if pending)</option>
                @foreach($workers as $w)
                    <option value="{{ $w->id }}" data-name="{{ $w->name }}" data-code="{{ $w->worker_code }}" @selected(old('worker_id', $agreement->worker_id ?? $a?->worker_id) == $w->id)>
                        {{ $w->worker_code }} · {{ $w->name }} ({{ $w->city ?: 'Vadodara' }})
                    </option>
                @endforeach
            </select>
            <p class="mt-1.5 text-xs text-neutral-500">Selecting a worker auto-populates the Worker Name and Worker Code snapshots below.</p>
        </div>

        <div>
            <label>Worker Name (Snapshot)</label>
            <input id="workerNameSnapshot" name="worker_name_snapshot" value="{{ old('worker_name_snapshot', $agreement->worker_name_snapshot ?? $a?->worker?->name) }}" placeholder="e.g. Kartik">
        </div>

        <div>
            <label>Worker Code (Snapshot)</label>
            <input id="workerCodeSnapshot" name="worker_code_snapshot" value="{{ old('worker_code_snapshot', $agreement->worker_code_snapshot ?? $a?->worker?->worker_code) }}" placeholder="e.g. HH-WRK-000001">
        </div>

        <div>
            <label>Service Category</label>
            <select name="service_id">
                <option value="">Select Service</option>
                @foreach($services as $srv)
                    <option value="{{ $srv->id }}" @selected(old('service_id', $agreement->service_id ?? $a?->service_id) == $srv->id)>
                        {{ $srv->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label>Duty Type</label>
            <select name="duty_type_id">
                <option value="">Select Duty Type</option>
                @foreach($dutyTypes as $dt)
                    <option value="{{ $dt->id }}" @selected(old('duty_type_id', $agreement->duty_type_id ?? $a?->duty_type_id) == $dt->id)>
                        {{ $dt->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label>Gender Preference</label>
            <select name="gender_preference">
                @foreach(['no_preference'=>'No Preference','male'=>'Male','female'=>'Female','both'=>'Both'] as $k=>$v)
                    <option value="{{ $k }}" @selected(old('gender_preference', $agreement->gender_preference ?? $a?->requirement?->required_gender ?? 'no_preference') === $k)>{{ $v }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label>House Type</label>
            <input name="house_type" value="{{ old('house_type', $agreement->house_type ?? $a?->requirement?->householdDetail?->house_type) }}" placeholder="e.g. 2 BHK, 3 BHK, Villa">
        </div>
    </div>

    <div class="mt-8 flex justify-between border-t border-neutral-100 pt-5">
        <button type="button" data-prev-step class="btn-secondary">← Back</button>
        <button type="button" data-next-step class="btn-primary">Next: Financial & Terms →</button>
    </div>
</section>

{{-- STEP 5: FINANCIAL --}}
<section data-panel="financial" class="card hidden p-6 md:p-8">
    <div class="mb-5 border-b border-neutral-100 pb-4">
        <h2 class="text-lg font-black text-neutral-900">5. Financial & Service Terms</h2>
        <p class="text-sm text-neutral-500">Salaries, agency commission, tenure, and working schedule.</p>
    </div>

    <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
        <div>
            <label>Monthly Salary (₹)</label>
            <input type="number" min="0" step=".01" name="monthly_salary" value="{{ old('monthly_salary', $agreement->monthly_salary ?? $a?->monthly_salary) }}" placeholder="e.g. 15000">
        </div>
        <div>
            <label>Monthly Agency Service Charge (₹)</label>
            <input type="number" min="0" step=".01" name="monthly_agency_service_charge" value="{{ old('monthly_agency_service_charge', $agreement->monthly_agency_service_charge ?? $a?->agency_service_charge) }}" placeholder="e.g. 1500">
        </div>
        <div>
            <label>Monthly Leave / Off</label>
            <input name="monthly_leave_text" value="{{ old('monthly_leave_text', $agreement->monthly_leave_text ?? $a?->requirement?->workingCondition?->leave_details) }}" placeholder="e.g. 2 Days / Month">
        </div>
        <div>
            <label>Start Date</label>
            <input type="date" name="start_date" value="{{ old('start_date', $agreement->start_date?->format('Y-m-d') ?? $a?->assignment_start_date?->format('Y-m-d')) }}">
        </div>
        <div>
            <label>End Date</label>
            <input type="date" name="end_date" value="{{ old('end_date', $agreement->end_date?->format('Y-m-d') ?? $a?->assignment_end_date?->format('Y-m-d')) }}">
        </div>
        <div>
            <label>Tenure Description</label>
            <input name="tenure_text" value="{{ old('tenure_text', $agreement->tenure_text) }}" placeholder="Calculated automatically if blank">
        </div>
        <div>
            <label>Working Hours</label>
            <input name="working_hours_text" value="{{ old('working_hours_text', $agreement->working_hours_text ?? $a?->working_hours_text) }}" placeholder="e.g. 8 Hours (9:00 AM - 5:00 PM)">
        </div>
        <div class="md:col-span-2">
            <label>Work Location</label>
            <input name="work_location" value="{{ old('work_location', $agreement->work_location ?? $a?->work_location) }}" placeholder="e.g. Vadodara, Gujarat">
        </div>
    </div>

    <div class="mt-8 flex justify-between border-t border-neutral-100 pt-5">
        <button type="button" data-prev-step class="btn-secondary">← Back</button>
        <button type="button" data-next-step class="btn-primary">Next: Terms & Conditions →</button>
    </div>
</section>

{{-- STEP 6: TERMS --}}
<section data-panel="terms" class="card hidden p-6 md:p-8">
    <div class="mb-5 border-b border-neutral-100 pb-4">
        <h2 class="text-lg font-black text-neutral-900">6. Terms & Conditions</h2>
        <p class="text-sm text-neutral-500">Legal clauses snapshot. You may customize any clauses before locking the agreement.</p>
    </div>

    <textarea required name="terms_snapshot" rows="20" class="font-mono text-xs leading-relaxed">{{ old('terms_snapshot', $agreement->terms_snapshot ?? $template?->terms_content) }}</textarea>

    <div class="mt-8 flex justify-between border-t border-neutral-100 pt-5">
        <button type="button" data-prev-step class="btn-secondary">← Back</button>
        <button type="button" data-next-step class="btn-primary">Next: Signatures →</button>
    </div>
</section>

{{-- STEP 7: SIGNATURES (Fix for Image 3) --}}
<section data-panel="signatures" class="card hidden p-6 md:p-8">
    <div class="mb-5 border-b border-neutral-100 pb-4">
        <h2 class="text-lg font-black text-neutral-900">7. Digital Signature Capture & Upload</h2>
        <p class="text-sm text-neutral-500">Upload signature images or draw the customer signature directly on the canvas.</p>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        {{-- CUSTOMER SIGNATURE --}}
        <div class="rounded-2xl border border-neutral-200 bg-neutral-50/50 p-5">
            <div class="mb-3 flex items-center justify-between">
                <h3 class="font-black text-neutral-900">Customer Signature</h3>
                @if($agreement->customer_signature_path)
                    <span class="badge-active">Signature Captured</span>
                @else
                    <span class="badge-inactive">Pending</span>
                @endif
            </div>

            @if($agreement->customer_signature_path)
                <div class="mb-4 rounded-xl border border-neutral-200 bg-white p-3 text-center">
                    <p class="mb-2 text-xs font-bold uppercase text-neutral-400">Current Saved Signature</p>
                    <img class="mx-auto max-h-20 object-contain" src="{{ Storage::disk('local')->exists($agreement->customer_signature_path) ? route('admin.agreements.preview', $agreement) : '' }}" alt="Customer signature preview" onerror="this.parentElement.style.display='none'">
                </div>
            @endif

            <label class="text-xs font-bold text-neutral-700">Upload Customer Signature Image</label>
            <input type="file" name="customer_signature" accept="image/png,image/jpeg,image/webp">
            <p class="mt-1 text-xs text-neutral-400">PNG, JPG, or WebP up to 5 MB</p>

            <div class="my-4 text-center">
                <span class="rounded-full bg-neutral-200 px-3 py-1 text-xs font-bold uppercase tracking-wider text-neutral-600">OR Draw On Screen</span>
            </div>

            <canvas id="customerCanvas" class="h-32 w-full touch-none rounded-xl border border-neutral-300 bg-white shadow-inner" width="500" height="140"></canvas>
            <input id="customerSignatureData" type="hidden" name="customer_signature_data">

            <div class="mt-2 flex justify-end">
                <button type="button" id="clearCustomerCanvas" class="rounded-lg border border-neutral-300 bg-white px-3 py-1.5 text-xs font-bold text-neutral-700 hover:bg-neutral-100">
                    Clear Drawing
                </button>
            </div>
        </div>

        {{-- AGENCY SIGNATURE & STAMP --}}
        <div class="space-y-6">
            <div class="rounded-2xl border border-neutral-200 bg-neutral-50/50 p-5">
                <div class="mb-3 flex items-center justify-between">
                    <h3 class="font-black text-neutral-900">Agency Authorized Signature</h3>
                    @if($agreement->agency_signature_path)
                        <span class="badge-active">Signature Saved</span>
                    @else
                        <span class="badge-inactive">Pending / Using Default</span>
                    @endif
                </div>

                <label class="text-xs font-bold text-neutral-700">Upload Agency Signature</label>
                <input type="file" name="agency_signature" accept="image/png,image/jpeg,image/webp">
                <p class="mt-1.5 text-xs text-neutral-500">If left blank, the agency signature from Agency Settings will be used automatically.</p>
            </div>

            <div class="rounded-2xl border border-neutral-200 bg-neutral-50/50 p-5">
                <div class="mb-3 flex items-center justify-between">
                    <h3 class="font-black text-neutral-900">Agency Official Stamp / Seal</h3>
                    @if($agreement->agency_stamp_path)
                        <span class="badge-active">Stamp Saved</span>
                    @else
                        <span class="badge-inactive">Pending / Using Default</span>
                    @endif
                </div>

                <label class="text-xs font-bold text-neutral-700">Upload Agency Stamp</label>
                <input type="file" name="agency_stamp" accept="image/png,image/jpeg,image/webp">
                <p class="mt-1.5 text-xs text-neutral-500">If left blank, the official stamp from Agency Settings will be used automatically.</p>
            </div>
        </div>
    </div>

    <div class="mt-8 flex justify-between border-t border-neutral-100 pt-5">
        <button type="button" data-prev-step class="btn-secondary">← Back</button>
        <button type="button" data-next-step class="btn-primary">Next: Review & Save →</button>
    </div>
</section>

{{-- STEP 8: REVIEW --}}
<section data-panel="review" class="card hidden p-6 md:p-8">
    <div class="mb-5 border-b border-neutral-100 pb-4">
        <h2 class="text-lg font-black text-neutral-900">8. Review & Finalize Agreement</h2>
        <p class="text-sm text-neutral-500">Please verify the agreement details before confirming.</p>
    </div>

    <div class="rounded-2xl border border-neutral-200 bg-neutral-50 p-5 text-sm text-neutral-700">
        <p class="font-bold text-neutral-900">Agreement Confirmation Checklist:</p>
        <ul class="mt-2 list-inside list-disc space-y-1 text-xs">
            <li>Customer legal snapshot will be locked upon saving.</li>
            <li>Assigned worker snapshot will be preserved with legal tenure.</li>
            <li>Terms and conditions snapshot is saved independently from future template edits.</li>
            <li>PDF generation is available immediately after saving this draft.</li>
        </ul>
    </div>

    <div class="mt-8 flex justify-between border-t border-neutral-100 pt-5">
        <button type="button" data-prev-step class="btn-secondary">← Back</button>
        <button type="submit" class="btn-primary px-6 py-3 text-base">
            {{ $agreement->exists ? 'Update Agreement Draft' : 'Create Agreement Draft' }}
        </button>
    </div>
</section>

</form>

<script>
(() => {
    const buttons = [...document.querySelectorAll('[data-step]')];
    const panels = [...document.querySelectorAll('[data-panel]')];

    const showStep = (index) => {
        if (index < 0 || index >= buttons.length) return;
        buttons.forEach((b, n) => {
            b.className = 'rounded-xl px-4 py-2.5 text-sm font-bold transition ' +
                (index === n ? 'bg-charcoal-900 text-white shadow-sm' : 'text-neutral-500 hover:text-neutral-900 hover:bg-neutral-100');
        });
        panels.forEach((p, n) => {
            p.classList.toggle('hidden', n !== index);
        });
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    buttons.forEach((b, i) => b.onclick = () => showStep(i));

    // Next / Prev step button handlers
    document.querySelectorAll('[data-next-step]').forEach(btn => {
        btn.onclick = () => {
            const currentPanel = btn.closest('[data-panel]');
            const currentIndex = panels.indexOf(currentPanel);
            showStep(currentIndex + 1);
        };
    });

    document.querySelectorAll('[data-prev-step]').forEach(btn => {
        btn.onclick = () => {
            const currentPanel = btn.closest('[data-panel]');
            const currentIndex = panels.indexOf(currentPanel);
            showStep(currentIndex - 1);
        };
    });

    // Auto-reload on assignment change
    document.getElementById('assignmentSelect')?.addEventListener('change', e => {
        if (e.target.value) {
            location.href = "{{ route('admin.agreements.create') }}?assignment=" + e.target.value;
        } else {
            location.href = "{{ route('admin.agreements.create') }}";
        }
    });

    // Auto-fill worker snapshot inputs when worker select changes in Step 4
    const workerSelect = document.getElementById('agreementWorkerSelect');
    const workerNameInput = document.getElementById('workerNameSnapshot');
    const workerCodeInput = document.getElementById('workerCodeSnapshot');

    if (workerSelect && workerNameInput && workerCodeInput) {
        workerSelect.addEventListener('change', () => {
            const selected = workerSelect.options[workerSelect.selectedIndex];
            if (selected && selected.value) {
                if (selected.dataset.name) workerNameInput.value = selected.dataset.name;
                if (selected.dataset.code) workerCodeInput.value = selected.dataset.code;
            }
        });
    }

    // Customer signature canvas drawing
    const canvas = document.getElementById('customerCanvas');
    if (canvas) {
        const ctx = canvas.getContext('2d');
        ctx.lineWidth = 2.5;
        ctx.lineCap = 'round';
        ctx.strokeStyle = '#171717';
        let drawing = false, hasDrawn = false;

        const getPos = (e) => {
            const rect = canvas.getBoundingClientRect();
            const clientX = e.touches ? e.touches[0].clientX : e.clientX;
            const clientY = e.touches ? e.touches[0].clientY : e.clientY;
            return [
                (clientX - rect.left) * (canvas.width / rect.width),
                (clientY - rect.top) * (canvas.height / rect.height)
            ];
        };

        const startDraw = (e) => {
            drawing = true;
            hasDrawn = true;
            ctx.beginPath();
            ctx.moveTo(...getPos(e));
            if (e.type === 'touchstart') e.preventDefault();
        };

        const moveDraw = (e) => {
            if (!drawing) return;
            ctx.lineTo(...getPos(e));
            ctx.stroke();
            if (e.type === 'touchmove') e.preventDefault();
        };

        const endDraw = () => { drawing = false; };

        canvas.addEventListener('mousedown', startDraw);
        canvas.addEventListener('mousemove', moveDraw);
        window.addEventListener('mouseup', endDraw);

        canvas.addEventListener('touchstart', startDraw, { passive: false });
        canvas.addEventListener('touchmove', moveDraw, { passive: false });
        canvas.addEventListener('touchend', endDraw);

        document.getElementById('clearCustomerCanvas')?.addEventListener('click', () => {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            hasDrawn = false;
            document.getElementById('customerSignatureData').value = '';
        });

        document.getElementById('agreementForm')?.addEventListener('submit', () => {
            if (hasDrawn) {
                document.getElementById('customerSignatureData').value = canvas.toDataURL('image/png');
            }
        });
    }

    showStep(0);
})();
</script>
@endsection
