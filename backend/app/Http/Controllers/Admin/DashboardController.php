<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RequirementStatus;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerRequirement;
use App\Models\Assignment;
use App\Models\ReplacementRequest;
use App\Models\Worker;
use App\Models\Agreement;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\DutyType;
use App\Models\Service;
use App\Models\User;
use App\Models\Enquiry;
use App\Models\EnquiryFollowUp;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'totalUsers' => User::count(),
            'activeServices' => Service::where('is_active', true)->count(),
            'activeDutyTypes' => DutyType::where('is_active', true)->count(),
            'totalCustomers' => Customer::count(),
            'activeCustomers' => Customer::where('status', 'active')->count(),
            'openRequirements' => CustomerRequirement::whereIn('requirement_status', RequirementStatus::openStatuses())->count(),
            'newRequirements' => CustomerRequirement::whereDate('created_at', today())->count(),
            'recentCustomers' => Customer::latest()->limit(5)->get(),
            'recentRequirements' => CustomerRequirement::with(['customer:id,name','service:id,name'])->latest()->limit(5)->get(),
            'activeAssignments' => Assignment::whereIn('status', ['confirmed','active'])->count(),
            'workingWorkers' => Worker::where('availability_status', 'working')->count(),
            'totalWorkers' => Worker::count(),
            'availableWorkers' => Worker::where('availability_status', 'available')->count(),
            'verifiedWorkers' => Worker::whereHas('verification', fn ($q) => $q->where('overall_verification_status', 'verified'))->count(),
            'openWorkerSearches' => CustomerRequirement::whereIn('requirement_status', ['open','worker_search','shortlisted'])->count(),
            'pendingReplacements' => ReplacementRequest::whereIn('status', ['requested','under_review','approved','worker_search','shortlisted'])->count(),
            'totalAgreements' => Agreement::count(),
            'draftAgreements' => Agreement::where('status', 'draft')->count(),
            'pendingSignatureAgreements' => Agreement::whereIn('status', ['generated','sent'])->count(),
            'activeAgreements' => Agreement::where('status', 'active')->count(),
            'totalInvoices' => Invoice::count(),
            'unpaidAmount' => Invoice::where('status', '!=', 'cancelled')->where('payment_status', '!=', 'paid')->sum('balance_amount'),
            'paidAmount' => Invoice::where('status', '!=', 'cancelled')->sum('paid_amount'),
            'overdueAmount' => Invoice::where('status', '!=', 'cancelled')->where('payment_status', '!=', 'paid')->whereDate('due_date', '<', today())->sum('balance_amount'),
            'paymentsToday' => Payment::where('status', 'completed')->whereDate('payment_date', today())->sum('amount'),
            'paymentsThisMonth' => Payment::where('status', 'completed')->whereBetween('payment_date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])->sum('amount'),
            'outstandingBalance' => Invoice::where('status', '!=', 'cancelled')->sum('balance_amount'),
            'pendingInvoices' => Invoice::where('status', '!=', 'cancelled')->where('balance_amount', '>', 0)->count(),
            'pendingVerification' => Worker::whereHas('verification', fn ($q) => $q->where('overall_verification_status', '!=', 'verified'))->count(),
            'recentPayments' => Payment::with('customer:id,name')->where('status', 'completed')->latest('payment_date')->limit(5)->get(),
            'newEnquiries' => Enquiry::where('status', 'new')->count(),
            'todayEnquiries' => Enquiry::whereDate('created_at', today())->count(),
            'pendingFollowUps' => EnquiryFollowUp::where('status', 'pending')->whereDate('follow_up_date', '<=', today())->count(),
            'qualifiedEnquiries' => Enquiry::where('status', 'qualified')->count(),
        ]);
    }
}
