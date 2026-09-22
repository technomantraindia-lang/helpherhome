<?php

use App\Http\Controllers\Admin\AgencySettingsController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\CustomerRequirementController;
use App\Http\Controllers\Admin\WorkerMatchingController;
use App\Http\Controllers\Admin\WorkerShortlistController;
use App\Http\Controllers\Admin\AssignmentController;
use App\Http\Controllers\Admin\ReplacementController;
use App\Http\Controllers\Admin\AgreementController;
use App\Http\Controllers\Admin\AgreementTemplateController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\PaymentReceiptController;
use App\Http\Controllers\SharedAgreementController;
use App\Http\Controllers\SharedInvoiceController;
use App\Http\Controllers\SharedPaymentReceiptController;
use App\Http\Controllers\SharedWorkerGeneratedDocumentController;
use App\Http\Controllers\SharedCustomerGeneratedDocumentController;
use App\Http\Controllers\Admin\DutyTypeController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\WorkerAvailabilityController;
use App\Http\Controllers\Admin\WorkerController;
use App\Http\Controllers\Admin\WorkerDocumentController;
use App\Http\Controllers\Admin\WorkerInterviewController;
use App\Http\Controllers\Admin\WorkerVerificationController;
use App\Http\Controllers\Admin\WorkerGeneratedDocumentController;
use App\Http\Controllers\Admin\CustomerRegistrationDocumentController;
use App\Http\Controllers\Admin\DocumentCenterController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\GlobalSearchController;
use App\Http\Controllers\Admin\EnquiryController;
use App\Http\Controllers\Admin\EnquiryFollowUpController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');
Route::get('/shared/agreement/{token}', SharedAgreementController::class)->name('shared.agreements.download');
Route::get('/shared/invoice/{token}', SharedInvoiceController::class)->name('shared.invoices.download');
Route::get('/shared/payment-receipt/{token}', SharedPaymentReceiptController::class)->name('shared.payment-receipts.download');
Route::get('/shared/worker-generated-document/{token}', SharedWorkerGeneratedDocumentController::class)->name('shared.worker-generated-documents.download');
Route::get('/shared/customer-generated-document/{token}', SharedCustomerGeneratedDocumentController::class)->name('shared.customer-generated-documents.download');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
});

Route::post('/logout', [AuthController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'active'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', DashboardController::class)->middleware('permission:dashboard.view')->name('dashboard');

    Route::get('/invoices', [InvoiceController::class, 'index'])->middleware(['permission:billing.view', 'permission:invoices.view'])->name('invoices.index');
    Route::get('/invoices/create', [InvoiceController::class, 'create'])->middleware('permission:invoices.create')->name('invoices.create');
    Route::get('/agreements/{agreement}/invoice/create', [InvoiceController::class, 'fromAgreement'])->middleware('permission:invoices.create')->name('agreements.invoice.create');
    Route::get('/assignments/{assignment}/invoice/create', [InvoiceController::class, 'fromAssignment'])->middleware('permission:invoices.create')->name('assignments.invoice.create');
    Route::post('/invoices', [InvoiceController::class, 'store'])->middleware('permission:invoices.create')->name('invoices.store');
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->middleware('permission:invoices.view')->name('invoices.show');
    Route::get('/invoices/{invoice}/edit', [InvoiceController::class, 'edit'])->middleware('permission:invoices.edit')->name('invoices.edit');
    Route::put('/invoices/{invoice}', [InvoiceController::class, 'update'])->middleware('permission:invoices.edit')->name('invoices.update');
    Route::get('/invoices/{invoice}/preview', [InvoiceController::class, 'preview'])->middleware('permission:invoices.view')->name('invoices.preview');
    Route::post('/invoices/{invoice}/generate', [InvoiceController::class, 'generate'])->middleware('permission:invoices.generate')->name('invoices.generate');
    Route::get('/invoices/{invoice}/download', [InvoiceController::class, 'download'])->middleware('permission:invoices.download')->name('invoices.download');
    Route::get('/invoices/{invoice}/print', [InvoiceController::class, 'print'])->middleware('permission:invoices.print')->name('invoices.print');
    Route::post('/invoices/{invoice}/share/whatsapp', [InvoiceController::class, 'whatsapp'])->middleware('permission:invoices.share')->name('invoices.share.whatsapp');
    Route::post('/invoices/{invoice}/mark-sent', [InvoiceController::class, 'markSent'])->middleware('permission:invoices.share')->name('invoices.mark-sent');
    Route::post('/invoices/{invoice}/cancel', [InvoiceController::class, 'cancel'])->middleware('permission:invoices.cancel')->name('invoices.cancel');
    Route::get('/payments', [PaymentController::class, 'index'])->middleware(['permission:billing.payments.view','permission:payments.view'])->name('payments.index');
    Route::get('/invoices/{invoice}/payments/create', [PaymentController::class, 'create'])->middleware('permission:payments.create')->name('invoices.payments.create');
    Route::post('/invoices/{invoice}/payments', [PaymentController::class, 'store'])->middleware('permission:payments.create')->name('invoices.payments.store');
    Route::get('/payments/{payment}', [PaymentController::class, 'show'])->middleware('permission:payments.view')->name('payments.show');
    Route::post('/payments/{payment}/complete', [PaymentController::class, 'complete'])->middleware('permission:payments.complete')->name('payments.complete');
    Route::post('/payments/{payment}/cancel', [PaymentController::class, 'cancel'])->middleware('permission:payments.cancel')->name('payments.cancel');
    Route::get('/payment-receipts', [PaymentReceiptController::class, 'index'])->middleware('permission:payment-receipts.view')->name('payment-receipts.index');
    Route::get('/payment-receipts/{receipt}', [PaymentReceiptController::class, 'show'])->middleware('permission:payment-receipts.view')->name('payment-receipts.show');
    Route::get('/payment-receipts/{receipt}/preview', [PaymentReceiptController::class, 'preview'])->middleware('permission:payment-receipts.view')->name('payment-receipts.preview');
    Route::get('/payment-receipts/{receipt}/print', [PaymentReceiptController::class, 'print'])->middleware('permission:payment-receipts.print')->name('payment-receipts.print');
    Route::post('/payment-receipts/{receipt}/generate', [PaymentReceiptController::class, 'generate'])->middleware('permission:payment-receipts.generate')->name('payment-receipts.generate');
    Route::get('/payment-receipts/{receipt}/download', [PaymentReceiptController::class, 'download'])->middleware('permission:payment-receipts.download')->name('payment-receipts.download');
    Route::post('/payment-receipts/{receipt}/share/whatsapp', [PaymentReceiptController::class, 'whatsapp'])->middleware('permission:payment-receipts.share')->name('payment-receipts.share.whatsapp');

    Route::get('/customers', [CustomerController::class, 'index'])->middleware('permission:customers.view')->name('customers.index');
    Route::get('/customers/create', [CustomerController::class, 'create'])->middleware('permission:customers.create')->name('customers.create');
    Route::post('/customers', [CustomerController::class, 'store'])->middleware('permission:customers.create')->name('customers.store');
    Route::get('/customers/{customer}', [CustomerController::class, 'show'])->middleware('permission:customers.view')->name('customers.show');
    Route::get('/customers/{customer}/edit', [CustomerController::class, 'edit'])->middleware('permission:customers.edit')->name('customers.edit');
    Route::put('/customers/{customer}', [CustomerController::class, 'update'])->middleware('permission:customers.edit')->name('customers.update');
    Route::get('/customers/{customer}/registration-form/preview', [CustomerRegistrationDocumentController::class, 'customerPreview'])->middleware('permission:customer-registration-documents.view')->name('customers.registration-form.preview');
    Route::get('/customer-requirements', [CustomerRequirementController::class, 'index'])->middleware('permission:customer-requirements.view')->name('customer-requirements.index');
    Route::get('/customers/{customer}/requirements/create', [CustomerRequirementController::class, 'create'])->middleware('permission:customer-requirements.create')->name('customers.requirements.create');
    Route::post('/customers/{customer}/requirements', [CustomerRequirementController::class, 'store'])->middleware('permission:customer-requirements.create')->name('customers.requirements.store');
    Route::get('/customer-requirements/{requirement}', [CustomerRequirementController::class, 'show'])->middleware('permission:customer-requirements.view')->name('customer-requirements.show');
    Route::get('/customer-requirements/{requirement}/registration-form/preview', [CustomerRegistrationDocumentController::class, 'requirementPreview'])->middleware('permission:customer-registration-documents.view')->name('customer-requirements.registration-form.preview');
    Route::post('/customer-requirements/{requirement}/registration-form/generate', [CustomerRegistrationDocumentController::class, 'generate'])->middleware('permission:customer-registration-documents.generate')->name('customer-requirements.registration-form.generate');
    Route::get('/customer-requirements/{requirement}/edit', [CustomerRequirementController::class, 'edit'])->middleware('permission:customer-requirements.edit')->name('customer-requirements.edit');
    Route::put('/customer-requirements/{requirement}', [CustomerRequirementController::class, 'update'])->middleware('permission:customer-requirements.edit')->name('customer-requirements.update');

    Route::get('/worker-matching', [WorkerMatchingController::class, 'index'])->middleware('permission:worker-matching.view')->name('worker-matching.index');
    Route::get('/customer-requirements/{requirement}/matching', [WorkerMatchingController::class, 'requirement'])->middleware('permission:worker-matching.view')->name('customer-requirements.matching');
    Route::post('/customer-requirements/{requirement}/shortlist/{worker}', [WorkerShortlistController::class, 'store'])->middleware('permission:worker-shortlists.manage')->name('customer-requirements.shortlist');
    Route::get('/worker-shortlists', [WorkerShortlistController::class, 'index'])->middleware('permission:worker-shortlists.view')->name('worker-shortlists.index');
    Route::patch('/worker-shortlists/{shortlist}', [WorkerShortlistController::class, 'update'])->middleware('permission:worker-shortlists.manage')->name('worker-shortlists.update');
    Route::get('/customer-requirements/{requirement}/assign/{worker}', [AssignmentController::class, 'create'])->middleware('permission:assignments.create')->name('assignments.create');
    Route::post('/customer-requirements/{requirement}/assign/{worker}', [AssignmentController::class, 'store'])->middleware('permission:assignments.create')->name('assignments.store');
    Route::get('/assignments', [AssignmentController::class, 'index'])->middleware('permission:assignments.view')->name('assignments.index');
    Route::get('/assignments/active', [AssignmentController::class, 'active'])->middleware('permission:assignments.view')->name('assignments.active');
    Route::get('/assignments/history', [AssignmentController::class, 'history'])->middleware('permission:assignments.view')->name('assignments.history');
    Route::get('/assignments/{assignment}', [AssignmentController::class, 'show'])->middleware('permission:assignments.view')->name('assignments.show');
    Route::post('/assignments/{assignment}/start', [AssignmentController::class, 'start'])->middleware('permission:assignments.status')->name('assignments.start');
    Route::post('/assignments/{assignment}/complete', [AssignmentController::class, 'complete'])->middleware('permission:assignments.status')->name('assignments.complete');
    Route::post('/assignments/{assignment}/cancel', [AssignmentController::class, 'cancel'])->middleware('permission:assignments.status')->name('assignments.cancel');
    Route::get('/replacements', [ReplacementController::class, 'index'])->middleware('permission:replacements.view')->name('replacements.index');
    Route::post('/assignments/{assignment}/replacement', [ReplacementController::class, 'store'])->middleware('permission:replacements.create')->name('assignments.replacements.store');
    Route::get('/replacements/{replacement}', [ReplacementController::class, 'show'])->middleware('permission:replacements.view')->name('replacements.show');
    Route::post('/replacements/{replacement}/status', [ReplacementController::class, 'status'])->middleware('permission:replacements.manage')->name('replacements.status');
    Route::get('/replacements/{replacement}/matching', [ReplacementController::class, 'matching'])->middleware('permission:worker-matching.view')->name('replacements.matching');
    Route::post('/replacements/{replacement}/assign/{worker}', [ReplacementController::class, 'assign'])->middleware('permission:replacements.manage')->name('replacements.assign');

    Route::get('/agreements', [AgreementController::class, 'index'])->middleware('permission:agreements.view')->name('agreements.index');
    Route::get('/agreements/create', [AgreementController::class, 'create'])->middleware('permission:agreements.create')->name('agreements.create');
    Route::get('/assignments/{assignment}/agreement/create', [AgreementController::class, 'create'])->middleware('permission:agreements.create')->name('assignments.agreement.create');
    Route::post('/agreements', [AgreementController::class, 'store'])->middleware('permission:agreements.create')->name('agreements.store');
    Route::get('/agreements/{agreement}', [AgreementController::class, 'show'])->middleware('permission:agreements.view')->name('agreements.show');
    Route::get('/agreements/{agreement}/edit', [AgreementController::class, 'edit'])->middleware('permission:agreements.edit')->name('agreements.edit');
    Route::put('/agreements/{agreement}', [AgreementController::class, 'update'])->middleware('permission:agreements.edit')->name('agreements.update');
    Route::get('/agreements/{agreement}/preview', [AgreementController::class, 'preview'])->middleware('permission:agreements.view')->name('agreements.preview');
    Route::post('/agreements/{agreement}/generate', [AgreementController::class, 'generate'])->middleware('permission:agreements.generate')->name('agreements.generate');
    Route::get('/agreements/{agreement}/download', [AgreementController::class, 'download'])->middleware('permission:agreements.download')->name('agreements.download');
    Route::get('/agreements/{agreement}/print', [AgreementController::class, 'print'])->middleware('permission:agreements.print')->name('agreements.print');
    Route::post('/agreements/{agreement}/signature/customer', [AgreementController::class, 'customerSignature'])->middleware('permission:agreements.sign')->name('agreements.signature.customer');
    Route::post('/agreements/{agreement}/signature/agency', [AgreementController::class, 'agencySignature'])->middleware('permission:agreements.sign')->name('agreements.signature.agency');
    Route::post('/agreements/{agreement}/status', [AgreementController::class, 'status'])->name('agreements.status');
    Route::post('/agreements/{agreement}/share/whatsapp', [AgreementController::class, 'whatsapp'])->middleware('permission:agreements.share')->name('agreements.share.whatsapp');
    Route::get('/agreement-templates', [AgreementTemplateController::class, 'index'])->middleware('permission:agreement-templates.view')->name('agreement-templates.index');
    Route::get('/agreement-templates/{template}/edit', [AgreementTemplateController::class, 'edit'])->middleware('permission:agreement-templates.edit')->name('agreement-templates.edit');
    Route::put('/agreement-templates/{template}', [AgreementTemplateController::class, 'update'])->middleware('permission:agreement-templates.edit')->name('agreement-templates.update');

    Route::get('/workers', [WorkerController::class, 'index'])->middleware('permission:workers.view')->name('workers.index');
    Route::get('/workers/create', [WorkerController::class, 'create'])->middleware('permission:workers.create')->name('workers.create');
    Route::post('/workers', [WorkerController::class, 'store'])->middleware('permission:workers.create')->name('workers.store');
    Route::get('/workers/{worker}', [WorkerController::class, 'show'])->middleware('permission:workers.view')->name('workers.show');
    Route::get('/workers/{worker}/edit', [WorkerController::class, 'edit'])->middleware('permission:workers.edit')->name('workers.edit');
    Route::put('/workers/{worker}', [WorkerController::class, 'update'])->middleware('permission:workers.edit')->name('workers.update');
    Route::patch('/workers/{worker}/status', [WorkerController::class, 'updateStatus'])->middleware('permission:workers.status')->name('workers.status.update');

    Route::get('/workers/{worker}/resume/preview', [WorkerGeneratedDocumentController::class, 'resumePreview'])->middleware('permission:worker-resumes.view')->name('workers.resume.preview');
    Route::post('/workers/{worker}/resume/generate', [WorkerGeneratedDocumentController::class, 'generateResume'])->middleware('permission:worker-resumes.generate')->name('workers.resume.generate');
    Route::get('/workers/{worker}/registration-form/preview', [WorkerGeneratedDocumentController::class, 'registrationPreview'])->middleware('permission:worker-registration-documents.view')->name('workers.registration-form.preview');
    Route::post('/workers/{worker}/registration-form/generate', [WorkerGeneratedDocumentController::class, 'generateRegistrationForm'])->middleware('permission:worker-registration-documents.generate')->name('workers.registration-form.generate');
    Route::get('/worker-generated-documents', [WorkerGeneratedDocumentController::class, 'index'])->middleware('permission:worker-generated-documents.view')->name('worker-generated-documents.index');
    Route::get('/worker-generated-documents/{document}', [WorkerGeneratedDocumentController::class, 'show'])->middleware('permission:worker-generated-documents.view')->name('worker-generated-documents.show');
    Route::get('/worker-generated-documents/{document}/preview', [WorkerGeneratedDocumentController::class, 'preview'])->middleware('permission:worker-generated-documents.view')->name('worker-generated-documents.preview');
    Route::get('/worker-generated-documents/{document}/print', [WorkerGeneratedDocumentController::class, 'print'])->name('worker-generated-documents.print');
    Route::get('/worker-generated-documents/{document}/download', [WorkerGeneratedDocumentController::class, 'download'])->name('worker-generated-documents.download');
    Route::post('/worker-generated-documents/{document}/share/whatsapp', [WorkerGeneratedDocumentController::class, 'whatsapp'])->name('worker-generated-documents.share.whatsapp');
    Route::post('/worker-generated-documents/{document}/archive', [WorkerGeneratedDocumentController::class, 'archive'])->middleware('permission:worker-generated-documents.view')->name('worker-generated-documents.archive');

    Route::get('/customer-generated-documents/{document}', [CustomerRegistrationDocumentController::class, 'show'])->middleware('permission:customer-registration-documents.view')->name('customer-generated-documents.show');
    Route::get('/customer-generated-documents/{document}/preview', [CustomerRegistrationDocumentController::class, 'preview'])->middleware('permission:customer-registration-documents.view')->name('customer-generated-documents.preview');
    Route::get('/customer-generated-documents/{document}/print', [CustomerRegistrationDocumentController::class, 'print'])->name('customer-generated-documents.print');
    Route::get('/customer-generated-documents/{document}/download', [CustomerRegistrationDocumentController::class, 'download'])->name('customer-generated-documents.download');
    Route::post('/customer-generated-documents/{document}/share/whatsapp', [CustomerRegistrationDocumentController::class, 'whatsapp'])->name('customer-generated-documents.share.whatsapp');
    Route::post('/customer-generated-documents/{document}/archive', [CustomerRegistrationDocumentController::class, 'archive'])->middleware('permission:customer-registration-documents.view')->name('customer-generated-documents.archive');
    Route::get('/documents', [DocumentCenterController::class, 'index'])->middleware('permission:documents.view')->name('documents.index');

    Route::get('/enquiries', [EnquiryController::class, 'index'])->middleware('permission:enquiries.view')->name('enquiries.index');
    Route::get('/enquiries/create', [EnquiryController::class, 'create'])->middleware('permission:enquiries.create')->name('enquiries.create');
    Route::post('/enquiries', [EnquiryController::class, 'store'])->middleware('permission:enquiries.create')->name('enquiries.store');
    Route::get('/enquiries/{enquiry}', [EnquiryController::class, 'show'])->middleware('permission:enquiries.view')->name('enquiries.show');
    Route::put('/enquiries/{enquiry}', [EnquiryController::class, 'update'])->middleware('permission:enquiries.edit')->name('enquiries.update');
    Route::post('/enquiries/{enquiry}/status', [EnquiryController::class, 'status'])->middleware('permission:enquiries.status')->name('enquiries.status');
    Route::post('/enquiries/{enquiry}/assign', [EnquiryController::class, 'assign'])->middleware('permission:enquiries.assign')->name('enquiries.assign');
    Route::get('/enquiries/{enquiry}/whatsapp', [EnquiryController::class, 'whatsapp'])->middleware('permission:enquiries.view')->name('enquiries.whatsapp');
    Route::post('/enquiries/{enquiry}/follow-ups', [EnquiryFollowUpController::class, 'store'])->middleware('permission:enquiries.follow-up')->name('enquiries.follow-ups.store');
    Route::post('/enquiry-follow-ups/{followUp}/complete', [EnquiryFollowUpController::class, 'complete'])->middleware('permission:enquiries.follow-up')->name('enquiry-follow-ups.complete');
    Route::post('/enquiries/{enquiry}/convert', [EnquiryController::class, 'convert'])->middleware('permission:enquiries.convert')->name('enquiries.convert');

    Route::get('/reports', [ReportController::class, 'overview'])->middleware('permission:reports.view')->name('reports.overview');
    foreach (['workers','customers','requirements','assignments','replacements','agreements','invoices','payments','outstanding','documents'] as $report) {
        Route::get('/reports/'.$report, [ReportController::class, 'report'])->middleware(['permission:reports.view','permission:reports.'.$report])->name('reports.'.$report);
    }
    foreach (['workers','customers','requirements','assignments','invoices','payments'] as $report) {
        Route::get('/reports/'.$report.'/export', [ReportController::class, 'export'])->middleware(['permission:reports.view','permission:reports.export'])->name('reports.'.$report.'.export');
    }
    Route::get('/search', GlobalSearchController::class)->middleware('permission:global-search.use')->name('search');

    Route::get('/worker-documents', [WorkerDocumentController::class, 'index'])->middleware('permission:worker-documents.view')->name('worker-documents.index');
    Route::post('/workers/{worker}/documents', [WorkerDocumentController::class, 'store'])->middleware('permission:worker-documents.create')->name('worker-documents.store');
    Route::get('/worker-documents/{document}/view', [WorkerDocumentController::class, 'view'])->middleware('permission:worker-documents.view')->name('worker-documents.view');
    Route::get('/worker-documents/{document}/download', [WorkerDocumentController::class, 'download'])->middleware('permission:worker-documents.view')->name('worker-documents.download');
    Route::patch('/worker-documents/{document}/verification', [WorkerDocumentController::class, 'verify'])->middleware('permission:worker-documents.verify')->name('worker-documents.verify');

    Route::get('/worker-interviews', [WorkerInterviewController::class, 'index'])->middleware('permission:worker-interviews.view')->name('worker-interviews.index');
    Route::get('/worker-interviews/create', [WorkerInterviewController::class, 'create'])->middleware('permission:worker-interviews.create')->name('worker-interviews.create');
    Route::post('/worker-interviews', [WorkerInterviewController::class, 'store'])->middleware('permission:worker-interviews.create')->name('worker-interviews.store');
    Route::get('/worker-interviews/{interview}/edit', [WorkerInterviewController::class, 'edit'])->middleware('permission:worker-interviews.edit')->name('worker-interviews.edit');
    Route::put('/worker-interviews/{interview}', [WorkerInterviewController::class, 'update'])->middleware('permission:worker-interviews.edit')->name('worker-interviews.update');

    Route::get('/worker-verification', [WorkerVerificationController::class, 'index'])->middleware('permission:worker-verification.view')->name('worker-verifications.index');
    Route::get('/workers/{worker}/verification', [WorkerVerificationController::class, 'edit'])->middleware('permission:worker-verification.view')->name('workers.verification.edit');
    Route::put('/workers/{worker}/verification', [WorkerVerificationController::class, 'update'])->middleware('permission:worker-verification.edit')->name('workers.verification.update');

    Route::get('/worker-availability', [WorkerAvailabilityController::class, 'index'])->middleware('permission:worker-availability.view')->name('worker-availability.index');
    Route::patch('/worker-availability/{worker}', [WorkerAvailabilityController::class, 'update'])->middleware('permission:worker-availability.edit')->name('worker-availability.update');

    Route::get('/services', [ServiceController::class, 'index'])->middleware('permission:services.view')->name('services.index');
    Route::get('/services/create', [ServiceController::class, 'create'])->middleware('permission:services.create')->name('services.create');
    Route::post('/services', [ServiceController::class, 'store'])->middleware('permission:services.create')->name('services.store');
    Route::get('/services/{service}/edit', [ServiceController::class, 'edit'])->middleware('permission:services.edit')->name('services.edit');
    Route::put('/services/{service}', [ServiceController::class, 'update'])->middleware('permission:services.edit')->name('services.update');

    Route::get('/duty-types', [DutyTypeController::class, 'index'])->middleware('permission:duty-types.view')->name('duty-types.index');
    Route::get('/duty-types/create', [DutyTypeController::class, 'create'])->middleware('permission:duty-types.create')->name('duty-types.create');
    Route::post('/duty-types', [DutyTypeController::class, 'store'])->middleware('permission:duty-types.create')->name('duty-types.store');
    Route::get('/duty-types/{dutyType}/edit', [DutyTypeController::class, 'edit'])->middleware('permission:duty-types.edit')->name('duty-types.edit');
    Route::put('/duty-types/{dutyType}', [DutyTypeController::class, 'update'])->middleware('permission:duty-types.edit')->name('duty-types.update');

    Route::get('/users', [UserController::class, 'index'])->middleware('permission:users.view')->name('users.index');
    Route::get('/users/create', [UserController::class, 'create'])->middleware('permission:users.create')->name('users.create');
    Route::post('/users', [UserController::class, 'store'])->middleware('permission:users.create')->name('users.store');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->middleware('permission:users.edit')->name('users.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])->middleware('permission:users.edit')->name('users.update');

    Route::get('/roles', [RoleController::class, 'index'])->middleware('permission:roles.view')->name('roles.index');
    Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])->middleware('permission:roles.edit')->name('roles.edit');
    Route::put('/roles/{role}', [RoleController::class, 'update'])->middleware('permission:roles.edit')->name('roles.update');

    Route::get('/settings/agency', [AgencySettingsController::class, 'edit'])->middleware('permission:settings.view')->name('settings.agency.edit');
    Route::get('/settings/agency/assets/{asset}', [AgencySettingsController::class, 'asset'])->middleware('permission:settings.view')->name('settings.agency.asset');
    Route::put('/settings/agency', [AgencySettingsController::class, 'update'])->middleware('permission:settings.edit')->name('settings.agency.update');
});
