@extends('layouts.admin')
@section('title','Dashboard') @section('heading','Dashboard') @section('subheading','Live operational command center and performance overview.')
@section('content')
<section class="mb-6 overflow-hidden rounded-3xl bg-charcoal-900 p-6 text-white shadow-md md:p-8">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <p class="mb-1 text-xs font-bold uppercase tracking-wider text-gold-400">Welcome, {{ auth()->user()->name }}</p>
            <h2 class="text-2xl font-black md:text-3xl">Home-care operations at a glance.</h2>
            <p class="mt-1 text-sm text-neutral-400">Live operational metrics and immediate queue actions.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if(auth()->user()->hasPermission('customers.create'))
                <a href="{{ route('admin.customers.create') }}" class="btn-primary text-xs">+ Register Customer</a>
            @endif
            @if(auth()->user()->hasPermission('workers.create'))
                <a href="{{ route('admin.workers.create') }}" class="btn-secondary text-xs">+ Add Worker</a>
            @endif
        </div>
    </div>
</section>

{{-- TIER 1: PRIMARY KPIS --}}
<div class="mb-6">
    <h3 class="mb-3 text-xs font-bold uppercase tracking-wider text-neutral-500">Core Performance KPIs</h3>
    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
        <a href="{{ route('admin.customers.index') }}" class="card block p-6 transition duration-150 hover:border-gold-400 hover:shadow-md">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-neutral-500">Total Customers</span>
                <span class="rounded-lg bg-neutral-100 p-1.5 text-neutral-600"><x-admin-icon name="users" /></span>
            </div>
            <p class="mt-3 text-4xl font-black text-neutral-900">{{ $totalCustomers }}</p>
            <p class="mt-2 text-xs text-neutral-500"><strong class="text-emerald-600">{{ $activeCustomers }}</strong> active accounts</p>
        </a>

        <a href="{{ route('admin.workers.index', ['availability_status' => 'available']) }}" class="card block p-6 transition duration-150 hover:border-gold-400 hover:shadow-md">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-neutral-500">Available Workers</span>
                <span class="rounded-lg bg-neutral-100 p-1.5 text-neutral-600"><x-admin-icon name="calendar" /></span>
            </div>
            <p class="mt-3 text-4xl font-black text-neutral-900">{{ $availableWorkers }}</p>
            <p class="mt-2 text-xs text-neutral-500">Out of <strong>{{ $totalWorkers }}</strong> total registered</p>
        </a>

        <a href="{{ route('admin.customer-requirements.index', ['status' => 'open']) }}" class="card block p-6 transition duration-150 hover:border-gold-400 hover:shadow-md">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-neutral-500">Open Requirements</span>
                <span class="rounded-lg bg-neutral-100 p-1.5 text-neutral-600"><x-admin-icon name="clipboard" /></span>
            </div>
            <p class="mt-3 text-4xl font-black text-neutral-900">{{ $openRequirements }}</p>
            <p class="mt-2 text-xs text-neutral-500"><strong class="text-gold-600">{{ $newRequirements }}</strong> registered today</p>
        </a>

        <a href="{{ route('admin.assignments.active') }}" class="card block p-6 transition duration-150 hover:border-gold-400 hover:shadow-md">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-neutral-500">Active Assignments</span>
                <span class="rounded-lg bg-neutral-100 p-1.5 text-neutral-600"><x-admin-icon name="assignment" /></span>
            </div>
            <p class="mt-3 text-4xl font-black text-neutral-900">{{ $activeAssignments }}</p>
            <p class="mt-2 text-xs text-neutral-500"><strong>{{ $workingWorkers }}</strong> workers deployed on site</p>
        </a>
    </div>
</div>

{{-- TIER 2: OPERATIONAL QUEUES --}}
<div class="mb-8">
    <h3 class="mb-3 text-xs font-bold uppercase tracking-wider text-neutral-500">Operational Attention & Queues</h3>
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @if(auth()->user()->hasPermission('enquiries.view'))
            <a href="{{ route('admin.enquiries.index', ['status' => 'new']) }}" class="card flex items-center justify-between p-4 transition duration-150 hover:border-gold-400 hover:shadow-sm">
                <div>
                    <p class="text-xs font-semibold text-neutral-500">New Enquiries</p>
                    <p class="text-2xl font-black text-neutral-900">{{ $newEnquiries }}</p>
                    <p class="text-[11px] text-neutral-400">{{ $todayEnquiries }} today</p>
                </div>
                <span class="rounded-xl bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-700">Action</span>
            </a>

            <a href="{{ route('admin.enquiries.index', ['status' => 'follow_up']) }}" class="card flex items-center justify-between p-4 transition duration-150 hover:border-gold-400 hover:shadow-sm">
                <div>
                    <p class="text-xs font-semibold text-neutral-500">Pending Follow-Ups</p>
                    <p class="text-2xl font-black text-neutral-900">{{ $pendingFollowUps }}</p>
                    <p class="text-[11px] text-neutral-400">Scheduled</p>
                </div>
                <span class="rounded-xl bg-blue-50 px-2.5 py-1 text-xs font-bold text-blue-700">Review</span>
            </a>
        @endif

        @if(auth()->user()->hasPermission('replacements.view'))
            <a href="{{ route('admin.replacements.index') }}" class="card flex items-center justify-between p-4 transition duration-150 hover:border-gold-400 hover:shadow-sm">
                <div>
                    <p class="text-xs font-semibold text-neutral-500">Pending Replacements</p>
                    <p class="text-2xl font-black text-neutral-900">{{ $pendingReplacements }}</p>
                    <p class="text-[11px] text-neutral-400">Replacement queue</p>
                </div>
                <span class="rounded-xl bg-red-50 px-2.5 py-1 text-xs font-bold text-red-700">Queue</span>
            </a>
        @endif

        @if(auth()->user()->hasPermission('agreements.view'))
            <a href="{{ route('admin.agreements.index') }}" class="card flex items-center justify-between p-4 transition duration-150 hover:border-gold-400 hover:shadow-sm">
                <div>
                    <p class="text-xs font-semibold text-neutral-500">Pending Signature</p>
                    <p class="text-2xl font-black text-neutral-900">{{ $pendingSignatureAgreements }}</p>
                    <p class="text-[11px] text-neutral-400">Drafts: {{ $draftAgreements }}</p>
                </div>
                <span class="rounded-xl bg-purple-50 px-2.5 py-1 text-xs font-bold text-purple-700">Sign</span>
            </a>
        @endif
    </div>
</div>

{{-- TIER 3: GROUPED DOMAIN SECTIONS --}}
<div class="grid gap-6 xl:grid-cols-2 mb-8">
    {{-- CUSTOMER & LEADS OVERVIEW --}}
    <section class="card overflow-hidden">
        <div class="flex items-center justify-between border-b bg-neutral-50/50 px-5 py-4">
            <h3 class="font-black text-neutral-900">Recent Customers</h3>
            <a href="{{ route('admin.customers.index') }}" class="text-xs font-bold text-gold-600 hover:underline">View All →</a>
        </div>
        <div class="divide-y">
            @forelse($recentCustomers as $customer)
                <a class="flex items-center justify-between gap-3 p-4 transition hover:bg-neutral-50" href="{{ route('admin.customers.show',$customer) }}">
                    <div>
                        <strong class="block text-sm font-bold text-neutral-900">{{ $customer->name }}</strong>
                        <small class="text-xs text-neutral-500">{{ $customer->customer_code }} · {{ $customer->city ?: 'No city' }}</small>
                    </div>
                    <span class="text-xs font-medium text-neutral-500">{{ $customer->registration_date->format('d M Y') }}</span>
                </a>
            @empty
                <p class="p-5 text-sm text-neutral-500">No customers registered yet.</p>
            @endforelse
        </div>
        <div class="border-t bg-neutral-50/30 p-3 flex flex-wrap gap-3 text-xs text-neutral-600">
            <span><strong>{{ $activeCustomers }}</strong> Active Customers</span>
            <span>·</span>
            <span><strong>{{ $newRequirements }}</strong> New Requirements</span>
            @if(auth()->user()->hasPermission('enquiries.view'))
                <span>·</span>
                <span><strong>{{ $qualifiedEnquiries }}</strong> Qualified Enquiries</span>
                <span>·</span>
                <span><strong>{{ $todayEnquiries }}</strong> Today's Enquiries</span>
            @endif
        </div>
    </section>

    {{-- REQUIREMENTS OVERVIEW --}}
    <section class="card overflow-hidden">
        <div class="flex items-center justify-between border-b bg-neutral-50/50 px-5 py-4">
            <h3 class="font-black text-neutral-900">Recent Service Requirements</h3>
            <a href="{{ route('admin.customer-requirements.index') }}" class="text-xs font-bold text-gold-600 hover:underline">View All →</a>
        </div>
        <div class="divide-y">
            @forelse($recentRequirements as $item)
                <a class="flex items-center justify-between gap-3 p-4 transition hover:bg-neutral-50" href="{{ route('admin.customer-requirements.show',$item) }}">
                    <div>
                        <strong class="block text-sm font-bold text-neutral-900">{{ $item->service->name }} · {{ $item->customer->name }}</strong>
                        <small class="text-xs text-neutral-500">{{ $item->requirement_code }}</small>
                    </div>
                    <span class="{{ $item->requirement_status->badgeClass() }}">{{ $item->requirement_status->label() }}</span>
                </a>
            @empty
                <p class="p-5 text-sm text-neutral-500">No requirements recorded yet.</p>
            @endforelse
        </div>
        <div class="border-t bg-neutral-50/30 p-3 flex flex-wrap gap-3 text-xs text-neutral-600">
            <a href="{{ route('admin.workers.index') }}" class="hover:text-gold-600"><strong>{{ $totalWorkers }}</strong> Total Workers</a>
            <span>·</span>
            <span><strong>{{ $workingWorkers }}</strong> Workers Working</span>
            <span>·</span>
            <a href="{{ route('admin.workers.index', ['verification_status' => 'verified']) }}" class="hover:text-gold-600"><strong>{{ $verifiedWorkers }}</strong> Verified Workers</a>
            <span>·</span>
            <a href="{{ route('admin.worker-matching.index') }}" class="hover:text-gold-600"><strong>{{ $openWorkerSearches }}</strong> Open Worker Searches</a>
        </div>
    </section>
</div>

{{-- BILLING & FINANCIAL SECTION --}}
@if(auth()->user()->hasPermission('billing.view'))
    <div class="mb-8">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-xs font-bold uppercase tracking-wider text-neutral-500">Financial & Invoicing Summary</h3>
            <a href="{{ route('admin.invoices.index') }}" class="text-xs font-bold text-gold-600 hover:underline">All Invoices →</a>
        </div>
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <a href="{{ route('admin.invoices.index') }}" class="card block p-5 transition duration-150 hover:border-gold-400 hover:shadow-sm">
                <p class="text-xs font-semibold text-neutral-500">Total Invoices</p>
                <p class="mt-2 text-3xl font-black text-neutral-900">{{ $totalInvoices }}</p>
                <p class="mt-1 text-xs text-neutral-400">{{ $pendingInvoices }} pending collection</p>
            </a>

            <div class="card p-5">
                <p class="text-xs font-semibold text-neutral-500">Paid Amount</p>
                <p class="mt-2 text-3xl font-black text-emerald-700">₹{{ number_format((float)$paidAmount,2) }}</p>
                <p class="mt-1 text-xs text-neutral-400">Total received</p>
            </div>

            <a href="{{ route('admin.invoices.index',['payment_status'=>'unpaid']) }}" class="card block p-5 transition duration-150 hover:border-gold-400 hover:shadow-sm">
                <p class="text-xs font-semibold text-neutral-500">Unpaid Amount</p>
                <p class="mt-2 text-3xl font-black text-amber-700">₹{{ number_format((float)$unpaidAmount,2) }}</p>
                <p class="mt-1 text-xs text-neutral-400">Balance due</p>
            </a>

            <a href="{{ route('admin.invoices.index',['payment_status'=>'overdue']) }}" class="card block p-5 transition duration-150 hover:border-gold-400 hover:shadow-sm">
                <p class="text-xs font-semibold text-neutral-500">Overdue Amount</p>
                <p class="mt-2 text-3xl font-black text-red-600">₹{{ number_format((float)$overdueAmount,2) }}</p>
                <p class="mt-1 text-xs text-neutral-400">Past due date</p>
            </a>
        </div>
    </div>
@endif

{{-- RECENT PAYMENTS & AGREEMENT HEALTH --}}
<div class="grid gap-6 xl:grid-cols-2">
    @if(auth()->user()->hasPermission('billing.payments.view'))
        <section class="card overflow-hidden">
            <div class="flex items-center justify-between border-b bg-neutral-50/50 px-5 py-4">
                <div>
                    <h3 class="font-black text-neutral-900">Recent Payments</h3>
                    <p class="text-xs text-neutral-500">Payments Received Today: <strong>₹{{ number_format((float)$paymentsToday,2) }}</strong></p>
                </div>
                @if(auth()->user()->hasPermission('reports.payments'))
                    <a class="text-xs font-bold text-gold-600 hover:underline" href="{{ route('admin.reports.payments') }}">Full Report →</a>
                @endif
            </div>
            <div class="divide-y">
                @forelse($recentPayments as $payment)
                    <div class="flex items-center justify-between gap-3 p-4 text-sm">
                        <div>
                            <strong class="block font-bold text-neutral-900">{{ $payment->customer?->name ?: 'Customer' }}</strong>
                            <small class="text-xs text-neutral-500">{{ $payment->payment_code }} · {{ $payment->payment_date->format('d M Y') }}</small>
                        </div>
                        <strong class="text-emerald-700">₹{{ number_format((float)$payment->amount,2) }}</strong>
                    </div>
                @empty
                    <p class="p-5 text-sm text-neutral-500">No completed payments recorded yet.</p>
                @endforelse
            </div>
            <div class="border-t bg-neutral-50/30 p-3 flex flex-wrap gap-3 text-xs text-neutral-600">
                <span>Payments This Month: <strong>₹{{ number_format((float)$paymentsThisMonth,2) }}</strong></span>
                <span>·</span>
                <span>Outstanding Balance: <strong>₹{{ number_format((float)$outstandingBalance,2) }}</strong></span>
                <span>·</span>
                <span>Pending / Unpaid Invoices: <strong>{{ $pendingInvoices }}</strong></span>
            </div>
        </section>
    @endif

    {{-- AGREEMENTS & OPERATIONAL HEALTH --}}
    <section class="card p-5">
        <div class="flex items-center justify-between border-b pb-3 mb-4">
            <h3 class="font-black text-neutral-900">Agreements & System Status</h3>
            <a href="{{ route('admin.agreements.index') }}" class="text-xs font-bold text-gold-600 hover:underline">All Agreements →</a>
        </div>
        <div class="grid grid-cols-2 gap-3 mb-4">
            <div class="rounded-xl bg-neutral-50 p-3">
                <span class="text-xs text-neutral-500">Total Agreements</span>
                <p class="text-xl font-black text-neutral-900 mt-1">{{ $totalAgreements }}</p>
            </div>
            <div class="rounded-xl bg-neutral-50 p-3">
                <span class="text-xs text-neutral-500">Active Agreements</span>
                <p class="text-xl font-black text-emerald-700 mt-1">{{ $activeAgreements }}</p>
            </div>
        </div>
        <h4 class="text-xs font-bold uppercase tracking-wider text-neutral-500 mb-2">Attention List</h4>
        <div class="space-y-2.5 text-sm">
            <div class="flex items-center justify-between rounded-lg border border-neutral-100 p-2.5">
                <span class="text-neutral-600">Pending worker verification</span>
                <span class="rounded-full bg-amber-50 px-2 py-0.5 text-xs font-bold text-amber-700">{{ $pendingVerification }}</span>
            </div>
            <div class="flex items-center justify-between rounded-lg border border-neutral-100 p-2.5">
                <span class="text-neutral-600">Pending worker replacements</span>
                <span class="rounded-full bg-red-50 px-2 py-0.5 text-xs font-bold text-red-700">{{ $pendingReplacements }}</span>
            </div>
            <div class="flex items-center justify-between rounded-lg border border-neutral-100 p-2.5">
                <span class="text-neutral-600">Pending / unpaid invoices</span>
                <span class="rounded-full bg-blue-50 px-2 py-0.5 text-xs font-bold text-blue-700">{{ $pendingInvoices }}</span>
            </div>
            <div class="flex items-center justify-between rounded-lg border border-neutral-100 p-2.5">
                <span class="text-neutral-600">Draft agreements</span>
                <span class="rounded-full bg-neutral-100 px-2 py-0.5 text-xs font-bold text-neutral-700">{{ $draftAgreements }}</span>
            </div>
        </div>
    </section>
</div>
@endsection
