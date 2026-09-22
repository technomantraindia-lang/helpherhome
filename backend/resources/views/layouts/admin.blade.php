<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}"><title>@yield('title', 'Admin') · Helper Home</title>@vite(['resources/css/app.css', 'resources/js/app.js'])</head>
<body><div class="min-h-screen lg:flex">
<div id="sidebarBackdrop" class="fixed inset-0 z-30 hidden bg-black/50 lg:hidden"></div>
<aside id="sidebar" class="fixed inset-y-0 left-0 z-40 w-72 -translate-x-full overflow-y-auto bg-charcoal-900 px-4 py-6 transition-transform lg:static lg:translate-x-0">
    <div class="mb-8 flex items-center justify-between px-2"><a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3"><img src="{{ asset('images/helper-home-logo.png') }}" alt="Helper Home - Home Care & Domestic Services" class="h-14 w-48 object-contain object-left" width="224" height="224"></a><button id="closeSidebar" class="text-neutral-400 lg:hidden" aria-label="Close navigation">✕</button></div>
    <nav class="space-y-1">
        @if(auth()->user()->hasPermission('dashboard.view'))<a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'nav-link-active' : '' }}"><x-admin-icon name="dashboard" /> Dashboard</a>@endif
        @if(auth()->user()->hasPermission('customers.view') || auth()->user()->hasPermission('customer-requirements.view'))
            <p class="px-3 pb-1 pt-6 text-[11px] font-bold uppercase tracking-[.18em] text-neutral-500">Customers</p>
            @if(auth()->user()->hasPermission('customers.view'))<a href="{{ route('admin.customers.index') }}" class="nav-link {{ request()->routeIs('admin.customers.index','admin.customers.show','admin.customers.edit') ? 'nav-link-active' : '' }}"><x-admin-icon name="users" /> All Customers</a>@endif
            @if(auth()->user()->hasPermission('customers.create'))<a href="{{ route('admin.customers.create') }}" class="nav-link {{ request()->routeIs('admin.customers.create') ? 'nav-link-active' : '' }}"><x-admin-icon name="user-plus" /> Add Customer</a>@endif
            @if(auth()->user()->hasPermission('customer-requirements.view'))<a href="{{ route('admin.customer-requirements.index') }}" class="nav-link {{ request()->routeIs('admin.customer-requirements.*','admin.customers.requirements.*') ? 'nav-link-active' : '' }}"><x-admin-icon name="clipboard" /> Service Requirements</a>@endif
        @endif
        @if(auth()->user()->hasPermission('assignments.view') || auth()->user()->hasPermission('worker-matching.view') || auth()->user()->hasPermission('replacements.view'))
            <p class="px-3 pb-1 pt-6 text-[11px] font-bold uppercase tracking-[.18em] text-neutral-500">Assignments</p>
            @if(auth()->user()->hasPermission('worker-matching.view'))<a href="{{ route('admin.worker-matching.index') }}" class="nav-link {{ request()->routeIs('admin.worker-matching.*','admin.customer-requirements.matching') ? 'nav-link-active' : '' }}"><x-admin-icon name="search" /> Worker Matching</a>@endif
            @if(auth()->user()->hasPermission('worker-shortlists.view'))<a href="{{ route('admin.worker-shortlists.index') }}" class="nav-link {{ request()->routeIs('admin.worker-shortlists.*') ? 'nav-link-active' : '' }}"><x-admin-icon name="star" /> Shortlisted Workers</a>@endif
            @if(auth()->user()->hasPermission('assignments.view'))<a href="{{ route('admin.assignments.index') }}" class="nav-link {{ request()->routeIs('admin.assignments.index','admin.assignments.show') ? 'nav-link-active' : '' }}"><x-admin-icon name="assignment" /> All Assignments</a><a href="{{ route('admin.assignments.active') }}" class="nav-link {{ request()->routeIs('admin.assignments.active') ? 'nav-link-active' : '' }}"><x-admin-icon name="play" /> Active Assignments</a>@endif
            @if(auth()->user()->hasPermission('replacements.view'))<a href="{{ route('admin.replacements.index') }}" class="nav-link {{ request()->routeIs('admin.replacements.*') ? 'nav-link-active' : '' }}"><x-admin-icon name="refresh" /> Replacement Requests</a>@endif
            @if(auth()->user()->hasPermission('assignments.view'))<a href="{{ route('admin.assignments.history') }}" class="nav-link {{ request()->routeIs('admin.assignments.history') ? 'nav-link-active' : '' }}"><x-admin-icon name="history" /> Assignment History</a>@endif
        @endif
        @if(auth()->user()->hasPermission('agreements.view') || auth()->user()->hasPermission('agreement-templates.view'))
            <p class="px-3 pb-1 pt-6 text-[11px] font-bold uppercase tracking-[.18em] text-neutral-500">Agreements</p>
            @if(auth()->user()->hasPermission('agreements.view'))<a href="{{ route('admin.agreements.index') }}" class="nav-link {{ request()->routeIs('admin.agreements.index','admin.agreements.show','admin.agreements.edit') ? 'nav-link-active' : '' }}"><x-admin-icon name="file" /> All Agreements</a>@endif
            @if(auth()->user()->hasPermission('agreements.create'))<a href="{{ route('admin.agreements.create') }}" class="nav-link {{ request()->routeIs('admin.agreements.create') ? 'nav-link-active' : '' }}"><x-admin-icon name="file-plus" /> Create Agreement</a>@endif
            @if(auth()->user()->hasPermission('agreement-templates.view'))<a href="{{ route('admin.agreement-templates.index') }}" class="nav-link {{ request()->routeIs('admin.agreement-templates.*') ? 'nav-link-active' : '' }}"><x-admin-icon name="template" /> Agreement Templates</a><a href="{{ route('admin.agreement-templates.index') }}" class="nav-link"><x-admin-icon name="document" /> Terms & Conditions</a>@endif
        @endif
        @if(auth()->user()->hasPermission('billing.view'))
            <p class="px-3 pb-1 pt-6 text-[11px] font-bold uppercase tracking-[.18em] text-neutral-500">Billing</p>
            @if(auth()->user()->hasPermission('invoices.view'))<a href="{{ route('admin.invoices.index') }}" class="nav-link"><x-admin-icon name="invoice" /> All Invoices</a>@endif
            @if(auth()->user()->hasPermission('invoices.create'))<a href="{{ route('admin.invoices.create') }}" class="nav-link"><x-admin-icon name="file-plus" /> Create Invoice</a>@endif
            @if(auth()->user()->hasPermission('invoices.view'))<a href="{{ route('admin.invoices.index',['payment_status'=>'unpaid']) }}" class="nav-link"><x-admin-icon name="clock" /> Pending Payments</a><a href="{{ route('admin.invoices.index',['payment_status'=>'paid']) }}" class="nav-link"><x-admin-icon name="check-circle" /> Paid Invoices</a>@endif
            @if(auth()->user()->hasPermission('payments.view'))<a href="{{ route('admin.payments.index') }}" class="nav-link"><x-admin-icon name="card" /> Payments</a>@endif
            @if(auth()->user()->hasPermission('payment-receipts.view'))<a href="{{ route('admin.payment-receipts.index') }}" class="nav-link"><x-admin-icon name="receipt" /> Payment Receipts</a>@endif
        @endif
        @if(auth()->user()->hasPermission('workers.view') || auth()->user()->hasPermission('worker-documents.view') || auth()->user()->hasPermission('worker-interviews.view') || auth()->user()->hasPermission('worker-verification.view') || auth()->user()->hasPermission('worker-availability.view') || auth()->user()->hasPermission('worker-generated-documents.view'))
            <p class="px-3 pb-1 pt-6 text-[11px] font-bold uppercase tracking-[.18em] text-neutral-500">Workers</p>
            @if(auth()->user()->hasPermission('workers.view'))<a href="{{ route('admin.workers.index') }}" class="nav-link {{ request()->routeIs('admin.workers.index','admin.workers.show','admin.workers.edit') ? 'nav-link-active' : '' }}"><x-admin-icon name="users" /> All Workers</a>@endif
            @if(auth()->user()->hasPermission('workers.create'))<a href="{{ route('admin.workers.create') }}" class="nav-link {{ request()->routeIs('admin.workers.create') ? 'nav-link-active' : '' }}"><x-admin-icon name="user-plus" /> Add Worker</a>@endif
            @if(auth()->user()->hasPermission('worker-documents.view'))<a href="{{ route('admin.worker-documents.index') }}" class="nav-link {{ request()->routeIs('admin.worker-documents.*') ? 'nav-link-active' : '' }}"><x-admin-icon name="folder" /> Worker Documents</a>@endif
            @if(auth()->user()->hasPermission('worker-interviews.view'))<a href="{{ route('admin.worker-interviews.index') }}" class="nav-link {{ request()->routeIs('admin.worker-interviews.*') ? 'nav-link-active' : '' }}"><x-admin-icon name="chat" /> Worker Interviews</a>@endif
            @if(auth()->user()->hasPermission('worker-verification.view'))<a href="{{ route('admin.worker-verifications.index') }}" class="nav-link {{ request()->routeIs('admin.worker-verifications.*','admin.workers.verification.*') ? 'nav-link-active' : '' }}"><x-admin-icon name="shield-check" /> Worker Verification</a>@endif
            @if(auth()->user()->hasPermission('worker-availability.view'))<a href="{{ route('admin.worker-availability.index') }}" class="nav-link {{ request()->routeIs('admin.worker-availability.*') ? 'nav-link-active' : '' }}"><x-admin-icon name="calendar" /> Worker Availability</a>@endif
            @if(auth()->user()->hasPermission('worker-resumes.view'))<a href="{{ route('admin.worker-generated-documents.index',['document_type'=>'worker_resume']) }}" class="nav-link"> <x-admin-icon name="file" /> Worker Resumes</a>@endif
            @if(auth()->user()->hasPermission('worker-registration-documents.view'))<a href="{{ route('admin.worker-generated-documents.index',['document_type'=>'worker_registration_form']) }}" class="nav-link"> <x-admin-icon name="file-plus" /> Worker Registration Forms</a>@endif
        @endif
        @if(auth()->user()->hasPermission('documents.view'))
            <p class="px-3 pb-1 pt-6 text-[11px] font-bold uppercase tracking-[.18em] text-neutral-500">Documents</p>
            <a href="{{ route('admin.documents.index') }}" class="nav-link {{ request()->routeIs('admin.documents.*') ? 'nav-link-active' : '' }}"><x-admin-icon name="folder" /> All Documents</a>
            @if(auth()->user()->hasPermission('worker-generated-documents.view'))<a href="{{ route('admin.worker-generated-documents.index') }}" class="nav-link"><x-admin-icon name="folder" /> Worker Documents</a>@endif
            @if(auth()->user()->hasPermission('customer-registration-documents.view'))<a href="{{ route('admin.documents.index',['document_type'=>'customer_registration_form']) }}" class="nav-link"><x-admin-icon name="document" /> Customer Documents</a>@endif
            @if(auth()->user()->hasPermission('agreements.view'))<a href="{{ route('admin.agreements.index') }}" class="nav-link"><x-admin-icon name="file" /> Agreements</a>@endif
            @if(auth()->user()->hasPermission('invoices.view'))<a href="{{ route('admin.invoices.index') }}" class="nav-link"><x-admin-icon name="invoice" /> Invoices</a>@endif
            @if(auth()->user()->hasPermission('payment-receipts.view'))<a href="{{ route('admin.payment-receipts.index') }}" class="nav-link"><x-admin-icon name="receipt" /> Payment Receipts</a>@endif
        @endif
        @if(auth()->user()->hasPermission('enquiries.view'))
            <p class="px-3 pb-1 pt-6 text-[11px] font-bold uppercase tracking-[.18em] text-neutral-500">Leads</p>
            <a href="{{ route('admin.enquiries.index') }}" class="nav-link {{ request()->routeIs('admin.enquiries.*','admin.enquiry-follow-ups.*') ? 'nav-link-active' : '' }}"><x-admin-icon name="message" /> All Enquiries</a>
            <a href="{{ route('admin.enquiries.index',['status'=>'new']) }}" class="nav-link"><x-admin-icon name="inbox" /> New Enquiries</a>
            <a href="{{ route('admin.enquiries.index',['status'=>'follow_up']) }}" class="nav-link"><x-admin-icon name="clock" /> Follow-Ups</a>
            <a href="{{ route('admin.enquiries.index',['status'=>'converted']) }}" class="nav-link"><x-admin-icon name="check-circle" /> Converted</a>
            <a href="{{ route('admin.enquiries.index',['status'=>'closed']) }}" class="nav-link"><x-admin-icon name="archive" /> Closed</a>
        @endif
        @if(auth()->user()->hasPermission('reports.view'))
            <p class="px-3 pb-1 pt-6 text-[11px] font-bold uppercase tracking-[.18em] text-neutral-500">Reports</p>
            <a href="{{ route('admin.reports.overview') }}" class="nav-link"><x-admin-icon name="chart" /> Overview</a>
            @foreach(['workers'=>'Worker Reports','customers'=>'Customer Reports','requirements'=>'Requirement Reports','assignments'=>'Assignment Reports','replacements'=>'Replacement Reports','agreements'=>'Agreement Reports','invoices'=>'Invoice Reports','payments'=>'Payment Reports','outstanding'=>'Outstanding Payments','documents'=>'Document Reports'] as $key=>$label)
                @if(auth()->user()->hasPermission('reports.'.$key))<a href="{{ route('admin.reports.'.$key) }}" class="nav-link"><x-admin-icon name="chart" /> {{ $label }}</a>@endif
            @endforeach
        @endif
        @if(auth()->user()->hasPermission('services.view') || auth()->user()->hasPermission('duty-types.view'))
            <p class="px-3 pb-1 pt-6 text-[11px] font-bold uppercase tracking-[.18em] text-neutral-500">Master Data</p>
            @if(auth()->user()->hasPermission('services.view'))<a href="{{ route('admin.services.index') }}" class="nav-link {{ request()->routeIs('admin.services.*') ? 'nav-link-active' : '' }}"><x-admin-icon name="briefcase" /> Services</a>@endif
            @if(auth()->user()->hasPermission('duty-types.view'))<a href="{{ route('admin.duty-types.index') }}" class="nav-link {{ request()->routeIs('admin.duty-types.*') ? 'nav-link-active' : '' }}"><x-admin-icon name="calendar" /> Duty Types</a>@endif
        @endif
        @if(auth()->user()->hasPermission('users.view') || auth()->user()->hasPermission('roles.view'))
            <p class="px-3 pb-1 pt-6 text-[11px] font-bold uppercase tracking-[.18em] text-neutral-500">Administration</p>
            @if(auth()->user()->hasPermission('users.view'))<a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'nav-link-active' : '' }}"><x-admin-icon name="users" /> Users</a>@endif
            @if(auth()->user()->hasPermission('roles.view'))<a href="{{ route('admin.roles.index') }}" class="nav-link {{ request()->routeIs('admin.roles.*') ? 'nav-link-active' : '' }}"><x-admin-icon name="shield" /> Roles & Permissions</a>@endif
        @endif
        @if(auth()->user()->hasPermission('settings.view'))<p class="px-3 pb-1 pt-6 text-[11px] font-bold uppercase tracking-[.18em] text-neutral-500">Settings</p><a href="{{ route('admin.settings.agency.edit') }}" class="nav-link {{ request()->routeIs('admin.settings.*') ? 'nav-link-active' : '' }}"><x-admin-icon name="settings" /> Agency Settings</a>@endif
    </nav>
</aside>
<div class="min-w-0 flex-1 h-screen overflow-y-auto"><header class="sticky top-0 z-20 flex h-18 items-center justify-between border-b border-neutral-200 bg-white/90 px-4 backdrop-blur md:px-8"><button id="openSidebar" class="rounded-lg border border-neutral-200 px-3 py-2 lg:hidden" aria-label="Open navigation">☰</button><div class="ml-auto flex items-center gap-4">@if(auth()->user()->hasPermission('global-search.use'))<form action="{{ route('admin.search') }}" method="GET" class="hidden md:block"><input name="q" value="{{ request('q') }}" placeholder="Search workers, customers, invoices..." class="w-72"></form>@endif<div class="hidden text-right sm:block"><p class="text-sm font-bold">{{ auth()->user()->name }}</p><p class="text-xs text-neutral-500">{{ auth()->user()->role?->name }}</p></div><form action="{{ route('logout') }}" method="POST">@csrf<button class="btn-secondary" type="submit">Sign out</button></form></div></header>
<main class="p-4 md:p-8"><div class="mx-auto max-w-7xl"><div class="mb-7 flex flex-col justify-between gap-3 sm:flex-row sm:items-end"><div><p class="mb-1 text-xs font-bold uppercase tracking-[.18em] text-gold-500">Helper Home</p><h1 class="text-2xl font-black tracking-tight text-neutral-900 md:text-3xl">@yield('heading')</h1>@hasSection('subheading')<p class="mt-2 text-sm text-neutral-500">@yield('subheading')</p>@endif</div>@yield('actions')</div>
@if(session('success'))<div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('success') }}</div>@endif
@if($errors->any())<div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"><strong>Please fix the following:</strong><ul class="mt-1 list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@yield('content')</div></main></div></div>
<script>const sidebar=document.getElementById('sidebar'),backdrop=document.getElementById('sidebarBackdrop');const toggleSidebar=()=>{sidebar.classList.toggle('-translate-x-full');backdrop.classList.toggle('hidden')};document.getElementById('openSidebar')?.addEventListener('click',toggleSidebar);document.getElementById('closeSidebar')?.addEventListener('click',toggleSidebar);backdrop?.addEventListener('click',toggleSidebar);</script>
</body></html>


