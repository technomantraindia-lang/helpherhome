<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}"><title>@yield('title', 'Admin') · Helper Home</title>@vite(['resources/css/app.css', 'resources/js/app.js'])</head>
<body class="bg-ivory-50 text-neutral-800 antialiased"><div class="min-h-screen lg:flex">
<div id="sidebarBackdrop" class="fixed inset-0 z-30 hidden bg-black/60 backdrop-blur-sm lg:hidden"></div>
<aside id="sidebar" class="fixed inset-y-0 left-0 z-40 flex w-72 -translate-x-full flex-col overflow-y-auto bg-charcoal-900 px-3.5 py-5 transition-transform duration-200 ease-in-out lg:static lg:translate-x-0">
    <div class="mb-6 flex items-center justify-between px-2">
        <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2">
            <img src="{{ asset('images/helper-home-logo.png') }}" alt="Helper Home - Home Care & Domestic Services" class="h-12 w-44 object-contain object-left" width="224" height="224">
        </a>
        <button id="closeSidebar" class="rounded-lg p-1.5 text-neutral-400 hover:bg-white/10 hover:text-white lg:hidden" aria-label="Close navigation">✕</button>
    </div>

    <nav class="flex-1 space-y-1 text-sm font-medium">
        @if(auth()->user()->hasPermission('dashboard.view'))
            <a href="{{ route('admin.dashboard') }}" class="nav-link mb-2 {{ request()->routeIs('admin.dashboard') ? 'nav-link-active' : '' }}">
                <x-admin-icon name="dashboard" /> <span>Dashboard</span>
            </a>
        @endif

        {{-- CUSTOMERS MODULE --}}
        @php
            $hasCustPerm = auth()->user()->hasPermission('customers.view') || auth()->user()->hasPermission('customers.create') || auth()->user()->hasPermission('customer-requirements.view') || auth()->user()->hasPermission('enquiries.view');
            $custActive = request()->routeIs('admin.customers.*', 'admin.customer-requirements.*') && !request()->routeIs('admin.reports.*');
        @endphp
        @if($hasCustPerm)
            <div class="nav-module" data-module="customers">
                <button type="button" class="nav-module-btn {{ $custActive ? 'text-gold-400 font-bold' : '' }}" onclick="toggleModule('customers')">
                    <span class="flex items-center gap-2.5 text-xs tracking-wider">
                        <x-admin-icon name="users" /> <span>CUSTOMERS</span>
                    </span>
                    <span class="module-chevron transition-transform duration-200 {{ $custActive ? 'rotate-180' : '' }}"><x-admin-icon name="chevron-down" /></span>
                </button>
                <div id="module-customers" class="nav-module-items space-y-0.5 pl-3 pt-1 {{ $custActive ? 'block' : 'hidden' }}">
                    @if(auth()->user()->hasPermission('customers.view'))
                        <a href="{{ route('admin.customers.index') }}" class="nav-sublink {{ request()->routeIs('admin.customers.index','admin.customers.show','admin.customers.edit') ? 'nav-sublink-active' : '' }}">All Customers</a>
                    @endif
                    @if(auth()->user()->hasPermission('customers.create'))
                        <a href="{{ route('admin.customers.create') }}" class="nav-sublink {{ request()->routeIs('admin.customers.create') ? 'nav-sublink-active' : '' }}">Add Customer</a>
                    @endif
                    @if(auth()->user()->hasPermission('customer-requirements.view'))
                        <a href="{{ route('admin.customer-requirements.index') }}" class="nav-sublink {{ request()->routeIs('admin.customer-requirements.*','admin.customers.requirements.*') ? 'nav-sublink-active' : '' }}">Service Requirements</a>
                    @endif
                    @if(auth()->user()->hasPermission('enquiries.view'))
                        <a href="{{ route('admin.enquiries.index', ['status' => 'new']) }}" class="nav-sublink {{ request()->routeIs('admin.enquiries.*') && request('status') === 'new' ? 'nav-sublink-active' : '' }}">New Enquiries</a>
                        <a href="{{ route('admin.enquiries.index', ['status' => 'follow_up']) }}" class="nav-sublink {{ request()->routeIs('admin.enquiries.*') && request('status') === 'follow_up' ? 'nav-sublink-active' : '' }}">Follow-Ups</a>
                    @endif
                </div>
            </div>
        @endif

        {{-- WORKERS MODULE --}}
        @php
            $hasWorkerPerm = auth()->user()->hasPermission('workers.view') || auth()->user()->hasPermission('workers.create') || auth()->user()->hasPermission('worker-documents.view') || auth()->user()->hasPermission('worker-interviews.view') || auth()->user()->hasPermission('worker-verification.view') || auth()->user()->hasPermission('worker-availability.view') || auth()->user()->hasPermission('worker-generated-documents.view');
            $workerActive = (request()->routeIs('admin.workers.*', 'admin.worker-documents.*', 'admin.worker-interviews.*', 'admin.worker-verifications.*', 'admin.worker-availability.*') || (request()->routeIs('admin.worker-generated-documents.*') && request('document_type'))) && !request()->routeIs('admin.reports.*');
        @endphp
        @if($hasWorkerPerm)
            <div class="nav-module" data-module="workers">
                <button type="button" class="nav-module-btn {{ $workerActive ? 'text-gold-400 font-bold' : '' }}" onclick="toggleModule('workers')">
                    <span class="flex items-center gap-2.5 text-xs tracking-wider">
                        <x-admin-icon name="user-plus" /> <span>WORKERS</span>
                    </span>
                    <span class="module-chevron transition-transform duration-200 {{ $workerActive ? 'rotate-180' : '' }}"><x-admin-icon name="chevron-down" /></span>
                </button>
                <div id="module-workers" class="nav-module-items space-y-0.5 pl-3 pt-1 {{ $workerActive ? 'block' : 'hidden' }}">
                    @if(auth()->user()->hasPermission('workers.view'))
                        <a href="{{ route('admin.workers.index') }}" class="nav-sublink {{ request()->routeIs('admin.workers.index','admin.workers.show','admin.workers.edit') ? 'nav-sublink-active' : '' }}">All Workers</a>
                    @endif
                    @if(auth()->user()->hasPermission('workers.create'))
                        <a href="{{ route('admin.workers.create') }}" class="nav-sublink {{ request()->routeIs('admin.workers.create') ? 'nav-sublink-active' : '' }}">Add Worker</a>
                    @endif
                    @if(auth()->user()->hasPermission('worker-availability.view'))
                        <a href="{{ route('admin.worker-availability.index') }}" class="nav-sublink {{ request()->routeIs('admin.worker-availability.*') ? 'nav-sublink-active' : '' }}">Worker Availability</a>
                    @endif
                    @if(auth()->user()->hasPermission('worker-documents.view'))
                        <a href="{{ route('admin.worker-documents.index') }}" class="nav-sublink {{ request()->routeIs('admin.worker-documents.*') ? 'nav-sublink-active' : '' }}">Worker Documents</a>
                    @endif
                    @if(auth()->user()->hasPermission('worker-verification.view'))
                        <a href="{{ route('admin.worker-verifications.index') }}" class="nav-sublink {{ request()->routeIs('admin.worker-verifications.*','admin.workers.verification.*') ? 'nav-sublink-active' : '' }}">Worker Verification</a>
                    @endif
                    @if(auth()->user()->hasPermission('worker-interviews.view'))
                        <a href="{{ route('admin.worker-interviews.index') }}" class="nav-sublink {{ request()->routeIs('admin.worker-interviews.*') ? 'nav-sublink-active' : '' }}">Worker Interviews</a>
                    @endif
                    @if(auth()->user()->hasPermission('worker-resumes.view') || auth()->user()->hasPermission('worker-generated-documents.view'))
                        <a href="{{ route('admin.worker-generated-documents.index', ['document_type' => 'worker_resume']) }}" class="nav-sublink {{ request()->routeIs('admin.worker-generated-documents.*') && request('document_type') === 'worker_resume' ? 'nav-sublink-active' : '' }}">Worker Resumes</a>
                    @endif
                    @if(auth()->user()->hasPermission('worker-registration-documents.view') || auth()->user()->hasPermission('worker-generated-documents.view'))
                        <a href="{{ route('admin.worker-generated-documents.index', ['document_type' => 'worker_registration_form']) }}" class="nav-sublink {{ request()->routeIs('admin.worker-generated-documents.*') && request('document_type') === 'worker_registration_form' ? 'nav-sublink-active' : '' }}">Worker Registration Forms</a>
                    @endif
                </div>
            </div>
        @endif

        {{-- ASSIGNMENTS MODULE --}}
        @php
            $hasAssignPerm = auth()->user()->hasPermission('assignments.view') || auth()->user()->hasPermission('worker-matching.view') || auth()->user()->hasPermission('replacements.view') || auth()->user()->hasPermission('worker-shortlists.view');
            $assignActive = request()->routeIs('admin.assignments.*', 'admin.worker-matching.*', 'admin.worker-shortlists.*', 'admin.replacements.*') && !request()->routeIs('admin.reports.*');
        @endphp
        @if($hasAssignPerm)
            <div class="nav-module" data-module="assignments">
                <button type="button" class="nav-module-btn {{ $assignActive ? 'text-gold-400 font-bold' : '' }}" onclick="toggleModule('assignments')">
                    <span class="flex items-center gap-2.5 text-xs tracking-wider">
                        <x-admin-icon name="assignment" /> <span>ASSIGNMENTS</span>
                    </span>
                    <span class="module-chevron transition-transform duration-200 {{ $assignActive ? 'rotate-180' : '' }}"><x-admin-icon name="chevron-down" /></span>
                </button>
                <div id="module-assignments" class="nav-module-items space-y-0.5 pl-3 pt-1 {{ $assignActive ? 'block' : 'hidden' }}">
                    @if(auth()->user()->hasPermission('worker-matching.view'))
                        <a href="{{ route('admin.worker-matching.index') }}" class="nav-sublink {{ request()->routeIs('admin.worker-matching.*') ? 'nav-sublink-active' : '' }}">Worker Matching</a>
                    @endif
                    @if(auth()->user()->hasPermission('worker-shortlists.view'))
                        <a href="{{ route('admin.worker-shortlists.index') }}" class="nav-sublink {{ request()->routeIs('admin.worker-shortlists.*') ? 'nav-sublink-active' : '' }}">Shortlisted Workers</a>
                    @endif
                    @if(auth()->user()->hasPermission('assignments.view'))
                        <a href="{{ route('admin.assignments.index') }}" class="nav-sublink {{ request()->routeIs('admin.assignments.index','admin.assignments.show') ? 'nav-sublink-active' : '' }}">All Assignments</a>
                        <a href="{{ route('admin.assignments.active') }}" class="nav-sublink {{ request()->routeIs('admin.assignments.active') ? 'nav-sublink-active' : '' }}">Active Assignments</a>
                    @endif
                    @if(auth()->user()->hasPermission('replacements.view'))
                        <a href="{{ route('admin.replacements.index') }}" class="nav-sublink {{ request()->routeIs('admin.replacements.*') ? 'nav-sublink-active' : '' }}">Replacement Requests</a>
                    @endif
                    @if(auth()->user()->hasPermission('assignments.view'))
                        <a href="{{ route('admin.assignments.history') }}" class="nav-sublink {{ request()->routeIs('admin.assignments.history') ? 'nav-sublink-active' : '' }}">Assignment History</a>
                    @endif
                </div>
            </div>
        @endif

        {{-- AGREEMENTS MODULE --}}
        @php
            $hasAgreePerm = auth()->user()->hasPermission('agreements.view') || auth()->user()->hasPermission('agreements.create') || auth()->user()->hasPermission('agreement-templates.view');
            $agreeActive = request()->routeIs('admin.agreements.*', 'admin.agreement-templates.*') && !request()->routeIs('admin.reports.*');
        @endphp
        @if($hasAgreePerm)
            <div class="nav-module" data-module="agreements">
                <button type="button" class="nav-module-btn {{ $agreeActive ? 'text-gold-400 font-bold' : '' }}" onclick="toggleModule('agreements')">
                    <span class="flex items-center gap-2.5 text-xs tracking-wider">
                        <x-admin-icon name="file" /> <span>AGREEMENTS</span>
                    </span>
                    <span class="module-chevron transition-transform duration-200 {{ $agreeActive ? 'rotate-180' : '' }}"><x-admin-icon name="chevron-down" /></span>
                </button>
                <div id="module-agreements" class="nav-module-items space-y-0.5 pl-3 pt-1 {{ $agreeActive ? 'block' : 'hidden' }}">
                    @if(auth()->user()->hasPermission('agreements.view'))
                        <a href="{{ route('admin.agreements.index') }}" class="nav-sublink {{ request()->routeIs('admin.agreements.index','admin.agreements.show','admin.agreements.edit') ? 'nav-sublink-active' : '' }}">All Agreements</a>
                    @endif
                    @if(auth()->user()->hasPermission('agreements.create'))
                        <a href="{{ route('admin.agreements.create') }}" class="nav-sublink {{ request()->routeIs('admin.agreements.create') ? 'nav-sublink-active' : '' }}">Create Agreement</a>
                    @endif
                    @if(auth()->user()->hasPermission('agreement-templates.view'))
                        <a href="{{ route('admin.agreement-templates.index') }}" class="nav-sublink {{ request()->routeIs('admin.agreement-templates.*') ? 'nav-sublink-active' : '' }}">Agreement Templates</a>
                        <a href="{{ route('admin.agreement-templates.index') }}" class="nav-sublink">Terms & Conditions</a>
                    @endif
                </div>
            </div>
        @endif

        {{-- BILLING & PAYMENTS MODULE --}}
        @php
            $hasBillingPerm = auth()->user()->hasPermission('billing.view') || auth()->user()->hasPermission('invoices.view') || auth()->user()->hasPermission('payments.view') || auth()->user()->hasPermission('payment-receipts.view');
            $billingActive = request()->routeIs('admin.invoices.*', 'admin.payments.*', 'admin.payment-receipts.*') && !request()->routeIs('admin.reports.*');
        @endphp
        @if($hasBillingPerm)
            <div class="nav-module" data-module="billing">
                <button type="button" class="nav-module-btn {{ $billingActive ? 'text-gold-400 font-bold' : '' }}" onclick="toggleModule('billing')">
                    <span class="flex items-center gap-2.5 text-xs tracking-wider">
                        <x-admin-icon name="invoice" /> <span>BILLING & PAYMENTS</span>
                    </span>
                    <span class="module-chevron transition-transform duration-200 {{ $billingActive ? 'rotate-180' : '' }}"><x-admin-icon name="chevron-down" /></span>
                </button>
                <div id="module-billing" class="nav-module-items space-y-0.5 pl-3 pt-1 {{ $billingActive ? 'block' : 'hidden' }}">
                    @if(auth()->user()->hasPermission('invoices.view'))
                        <a href="{{ route('admin.invoices.index') }}" class="nav-sublink {{ request()->routeIs('admin.invoices.index') && !request('payment_status') ? 'nav-sublink-active' : '' }}">All Invoices</a>
                    @endif
                    @if(auth()->user()->hasPermission('invoices.create'))
                        <a href="{{ route('admin.invoices.create') }}" class="nav-sublink {{ request()->routeIs('admin.invoices.create') ? 'nav-sublink-active' : '' }}">Create Invoice</a>
                    @endif
                    @if(auth()->user()->hasPermission('invoices.view'))
                        <a href="{{ route('admin.invoices.index', ['payment_status' => 'unpaid']) }}" class="nav-sublink {{ request()->routeIs('admin.invoices.index') && request('payment_status') === 'unpaid' ? 'nav-sublink-active' : '' }}">Pending Payments</a>
                        <a href="{{ route('admin.invoices.index', ['payment_status' => 'paid']) }}" class="nav-sublink {{ request()->routeIs('admin.invoices.index') && request('payment_status') === 'paid' ? 'nav-sublink-active' : '' }}">Paid Invoices</a>
                    @endif
                    @if(auth()->user()->hasPermission('payments.view'))
                        <a href="{{ route('admin.payments.index') }}" class="nav-sublink {{ request()->routeIs('admin.payments.*') ? 'nav-sublink-active' : '' }}">Payments</a>
                    @endif
                    @if(auth()->user()->hasPermission('payment-receipts.view'))
                        <a href="{{ route('admin.payment-receipts.index') }}" class="nav-sublink {{ request()->routeIs('admin.payment-receipts.*') ? 'nav-sublink-active' : '' }}">Payment Receipts</a>
                    @endif
                </div>
            </div>
        @endif

        {{-- DOCUMENTS MODULE --}}
        @php
            $hasDocPerm = auth()->user()->hasPermission('documents.view') || auth()->user()->hasPermission('worker-generated-documents.view');
            $docActive = request()->routeIs('admin.documents.*') || (request()->routeIs('admin.worker-generated-documents.*') && !request('document_type'));
        @endphp
        @if($hasDocPerm)
            <div class="nav-module" data-module="documents">
                <button type="button" class="nav-module-btn {{ $docActive ? 'text-gold-400 font-bold' : '' }}" onclick="toggleModule('documents')">
                    <span class="flex items-center gap-2.5 text-xs tracking-wider">
                        <x-admin-icon name="folder" /> <span>DOCUMENTS</span>
                    </span>
                    <span class="module-chevron transition-transform duration-200 {{ $docActive ? 'rotate-180' : '' }}"><x-admin-icon name="chevron-down" /></span>
                </button>
                <div id="module-documents" class="nav-module-items space-y-0.5 pl-3 pt-1 {{ $docActive ? 'block' : 'hidden' }}">
                    <a href="{{ route('admin.documents.index') }}" class="nav-sublink {{ request()->routeIs('admin.documents.index') && !request('document_type') ? 'nav-sublink-active' : '' }}">All Documents</a>
                    @if(auth()->user()->hasPermission('worker-generated-documents.view'))
                        <a href="{{ route('admin.worker-generated-documents.index') }}" class="nav-sublink {{ request()->routeIs('admin.worker-generated-documents.index') && !request('document_type') ? 'nav-sublink-active' : '' }}">Worker Documents</a>
                    @endif
                    @if(auth()->user()->hasPermission('customer-registration-documents.view') || auth()->user()->hasPermission('documents.view'))
                        <a href="{{ route('admin.documents.index', ['document_type' => 'customer_registration_form']) }}" class="nav-sublink {{ request()->routeIs('admin.documents.index') && request('document_type') === 'customer_registration_form' ? 'nav-sublink-active' : '' }}">Customer Documents</a>
                    @endif
                    @if(auth()->user()->hasPermission('agreements.view'))
                        <a href="{{ route('admin.agreements.index') }}" class="nav-sublink">Agreements</a>
                    @endif
                    @if(auth()->user()->hasPermission('invoices.view'))
                        <a href="{{ route('admin.invoices.index') }}" class="nav-sublink">Invoices</a>
                    @endif
                    @if(auth()->user()->hasPermission('payment-receipts.view'))
                        <a href="{{ route('admin.payment-receipts.index') }}" class="nav-sublink">Payment Receipts</a>
                    @endif
                </div>
            </div>
        @endif

        {{-- LEADS / ENQUIRIES MODULE --}}
        @php
            $hasEnqPerm = auth()->user()->hasPermission('enquiries.view');
            $enqActive = request()->routeIs('admin.enquiries.*') && !request()->routeIs('admin.reports.*');
        @endphp
        @if($hasEnqPerm)
            <div class="nav-module" data-module="enquiries">
                <button type="button" class="nav-module-btn {{ $enqActive ? 'text-gold-400 font-bold' : '' }}" onclick="toggleModule('enquiries')">
                    <span class="flex items-center gap-2.5 text-xs tracking-wider">
                        <x-admin-icon name="message" /> <span>ENQUIRIES</span>
                    </span>
                    <span class="module-chevron transition-transform duration-200 {{ $enqActive ? 'rotate-180' : '' }}"><x-admin-icon name="chevron-down" /></span>
                </button>
                <div id="module-enquiries" class="nav-module-items space-y-0.5 pl-3 pt-1 {{ $enqActive ? 'block' : 'hidden' }}">
                    <a href="{{ route('admin.enquiries.index') }}" class="nav-sublink {{ request()->routeIs('admin.enquiries.index') && !request('status') ? 'nav-sublink-active' : '' }}">All Enquiries</a>
                    <a href="{{ route('admin.enquiries.index', ['status' => 'new']) }}" class="nav-sublink {{ request()->routeIs('admin.enquiries.index') && request('status') === 'new' ? 'nav-sublink-active' : '' }}">New Enquiries</a>
                    <a href="{{ route('admin.enquiries.index', ['status' => 'follow_up']) }}" class="nav-sublink {{ request()->routeIs('admin.enquiries.index') && request('status') === 'follow_up' ? 'nav-sublink-active' : '' }}">Follow-Ups</a>
                    <a href="{{ route('admin.enquiries.index', ['status' => 'converted']) }}" class="nav-sublink {{ request()->routeIs('admin.enquiries.index') && request('status') === 'converted' ? 'nav-sublink-active' : '' }}">Converted</a>
                    <a href="{{ route('admin.enquiries.index', ['status' => 'closed']) }}" class="nav-sublink {{ request()->routeIs('admin.enquiries.index') && request('status') === 'closed' ? 'nav-sublink-active' : '' }}">Closed</a>
                </div>
            </div>
        @endif

        {{-- REPORTS MODULE --}}
        @php
            $hasReportPerm = auth()->user()->hasPermission('reports.view');
            $reportActive = request()->routeIs('admin.reports.*');
        @endphp
        @if($hasReportPerm)
            <div class="nav-module" data-module="reports">
                <button type="button" class="nav-module-btn {{ $reportActive ? 'text-gold-400 font-bold' : '' }}" onclick="toggleModule('reports')">
                    <span class="flex items-center gap-2.5 text-xs tracking-wider">
                        <x-admin-icon name="chart" /> <span>REPORTS</span>
                    </span>
                    <span class="module-chevron transition-transform duration-200 {{ $reportActive ? 'rotate-180' : '' }}"><x-admin-icon name="chevron-down" /></span>
                </button>
                <div id="module-reports" class="nav-module-items space-y-0.5 pl-3 pt-1 {{ $reportActive ? 'block' : 'hidden' }}">
                    <a href="{{ route('admin.reports.overview') }}" class="nav-sublink {{ request()->routeIs('admin.reports.overview') ? 'nav-sublink-active' : '' }}">Reports Overview</a>
                    @if(auth()->user()->hasPermission('reports.workers'))
                        <a href="{{ route('admin.reports.workers') }}" class="nav-sublink {{ request()->routeIs('admin.reports.workers') ? 'nav-sublink-active' : '' }}">Worker Reports</a>
                    @endif
                    @if(auth()->user()->hasPermission('reports.customers'))
                        <a href="{{ route('admin.reports.customers') }}" class="nav-sublink {{ request()->routeIs('admin.reports.customers') ? 'nav-sublink-active' : '' }}">Customer Reports</a>
                    @endif
                    @if(auth()->user()->hasPermission('reports.requirements'))
                        <a href="{{ route('admin.reports.requirements') }}" class="nav-sublink {{ request()->routeIs('admin.reports.requirements') ? 'nav-sublink-active' : '' }}">Requirement Reports</a>
                    @endif
                    @if(auth()->user()->hasPermission('reports.assignments'))
                        <a href="{{ route('admin.reports.assignments') }}" class="nav-sublink {{ request()->routeIs('admin.reports.assignments') ? 'nav-sublink-active' : '' }}">Assignment Reports</a>
                    @endif
                    @if(auth()->user()->hasPermission('reports.invoices') || auth()->user()->hasPermission('reports.payments') || auth()->user()->hasPermission('reports.outstanding'))
                        <a href="{{ route('admin.reports.invoices') }}" class="nav-sublink {{ request()->routeIs('admin.reports.invoices','admin.reports.payments','admin.reports.outstanding') ? 'nav-sublink-active' : '' }}">Financial Reports</a>
                    @endif
                    @if(auth()->user()->hasPermission('reports.documents'))
                        <a href="{{ route('admin.reports.documents') }}" class="nav-sublink {{ request()->routeIs('admin.reports.documents') ? 'nav-sublink-active' : '' }}">Document Reports</a>
                    @endif
                </div>
            </div>
        @endif

        {{-- MASTER DATA MODULE --}}
        @php
            $hasMasterPerm = auth()->user()->hasPermission('services.view') || auth()->user()->hasPermission('duty-types.view');
            $masterActive = request()->routeIs('admin.services.*', 'admin.duty-types.*');
        @endphp
        @if($hasMasterPerm)
            <div class="nav-module" data-module="master-data">
                <button type="button" class="nav-module-btn {{ $masterActive ? 'text-gold-400 font-bold' : '' }}" onclick="toggleModule('master-data')">
                    <span class="flex items-center gap-2.5 text-xs tracking-wider">
                        <x-admin-icon name="briefcase" /> <span>MASTER DATA</span>
                    </span>
                    <span class="module-chevron transition-transform duration-200 {{ $masterActive ? 'rotate-180' : '' }}"><x-admin-icon name="chevron-down" /></span>
                </button>
                <div id="module-master-data" class="nav-module-items space-y-0.5 pl-3 pt-1 {{ $masterActive ? 'block' : 'hidden' }}">
                    @if(auth()->user()->hasPermission('services.view'))
                        <a href="{{ route('admin.services.index') }}" class="nav-sublink {{ request()->routeIs('admin.services.*') ? 'nav-sublink-active' : '' }}">Services</a>
                    @endif
                    @if(auth()->user()->hasPermission('duty-types.view'))
                        <a href="{{ route('admin.duty-types.index') }}" class="nav-sublink {{ request()->routeIs('admin.duty-types.*') ? 'nav-sublink-active' : '' }}">Duty Types</a>
                    @endif
                </div>
            </div>
        @endif

        {{-- ADMINISTRATION MODULE --}}
        @php
            $hasAdminPerm = auth()->user()->hasPermission('users.view') || auth()->user()->hasPermission('roles.view');
            $adminActive = request()->routeIs('admin.users.*', 'admin.roles.*');
        @endphp
        @if($hasAdminPerm)
            <div class="nav-module" data-module="administration">
                <button type="button" class="nav-module-btn {{ $adminActive ? 'text-gold-400 font-bold' : '' }}" onclick="toggleModule('administration')">
                    <span class="flex items-center gap-2.5 text-xs tracking-wider">
                        <x-admin-icon name="shield" /> <span>ADMINISTRATION</span>
                    </span>
                    <span class="module-chevron transition-transform duration-200 {{ $adminActive ? 'rotate-180' : '' }}"><x-admin-icon name="chevron-down" /></span>
                </button>
                <div id="module-administration" class="nav-module-items space-y-0.5 pl-3 pt-1 {{ $adminActive ? 'block' : 'hidden' }}">
                    @if(auth()->user()->hasPermission('users.view'))
                        <a href="{{ route('admin.users.index') }}" class="nav-sublink {{ request()->routeIs('admin.users.*') ? 'nav-sublink-active' : '' }}">Users</a>
                    @endif
                    @if(auth()->user()->hasPermission('roles.view'))
                        <a href="{{ route('admin.roles.index') }}" class="nav-sublink {{ request()->routeIs('admin.roles.*') ? 'nav-sublink-active' : '' }}">Roles & Permissions</a>
                    @endif
                </div>
            </div>
        @endif

        {{-- SETTINGS --}}
        @if(auth()->user()->hasPermission('settings.view'))
            <div class="pt-2">
                <a href="{{ route('admin.settings.agency.edit') }}" class="nav-link {{ request()->routeIs('admin.settings.*') ? 'nav-link-active' : '' }}">
                    <x-admin-icon name="settings" /> <span>Agency Settings</span>
                </a>
            </div>
        @endif
    </nav>
</aside>

<div class="min-w-0 flex-1 h-screen overflow-y-auto">
    <header class="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-neutral-200 bg-white/95 px-4 backdrop-blur md:px-8">
        <button id="openSidebar" class="rounded-lg border border-neutral-200 p-2 text-neutral-600 hover:bg-neutral-50 lg:hidden" aria-label="Open navigation">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <div class="ml-auto flex items-center gap-4">
            @if(auth()->user()->hasPermission('global-search.use'))
                <form action="{{ route('admin.search') }}" method="GET" class="hidden md:block">
                    <input name="q" value="{{ request('q') }}" placeholder="Search workers, customers, invoices..." class="w-72">
                </form>
            @endif
            <div class="hidden text-right sm:block">
                <p class="text-sm font-bold leading-tight">{{ auth()->user()->name }}</p>
                <p class="text-xs text-neutral-500">{{ auth()->user()->role?->name }}</p>
            </div>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button class="btn-secondary" type="submit">Sign out</button>
            </form>
        </div>
    </header>

    <main class="p-4 md:p-8">
        <div class="mx-auto max-w-7xl">
            <div class="mb-7 flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
                <div>
                    <p class="mb-1 text-xs font-bold uppercase tracking-[.18em] text-gold-500">Helper Home</p>
                    <h1 class="text-2xl font-black tracking-tight text-neutral-900 md:text-3xl">@yield('heading')</h1>
                    @hasSection('subheading')
                        <p class="mt-2 text-sm text-neutral-500">@yield('subheading')</p>
                    @endif
                </div>
                @yield('actions')
            </div>

            @if(session('success'))
                <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    <strong>Please fix the following:</strong>
                    <ul class="mt-1 list-disc pl-5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </div>
    </main>
</div>
</div>

<script>
    const sidebar = document.getElementById('sidebar');
    const backdrop = document.getElementById('sidebarBackdrop');

    const toggleSidebar = () => {
        sidebar.classList.toggle('-translate-x-full');
        backdrop.classList.toggle('hidden');
    };

    document.getElementById('openSidebar')?.addEventListener('click', toggleSidebar);
    document.getElementById('closeSidebar')?.addEventListener('click', toggleSidebar);
    backdrop?.addEventListener('click', toggleSidebar);

    // Close mobile drawer on navigation click
    sidebar.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth < 1024) {
                sidebar.classList.add('-translate-x-full');
                backdrop.classList.add('hidden');
            }
        });
    });

    // Accordion module toggle function
    function toggleModule(moduleId) {
        const items = document.getElementById('module-' + moduleId);
        if (!items) return;
        const isHidden = items.classList.contains('hidden');
        const btn = items.previousElementSibling;
        const chevron = btn ? btn.querySelector('.module-chevron') : null;

        if (isHidden) {
            items.classList.remove('hidden');
            items.classList.add('block');
            chevron?.classList.add('rotate-180');
        } else {
            items.classList.add('hidden');
            items.classList.remove('block');
            chevron?.classList.remove('rotate-180');
        }
    }
</script>
</body></html>


