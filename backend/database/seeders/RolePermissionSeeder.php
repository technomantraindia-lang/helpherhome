<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['name' => 'Super Admin', 'slug' => 'super-admin', 'description' => 'Full system access.'],
            ['name' => 'Admin', 'slug' => 'admin', 'description' => 'Operational administration access.'],
            ['name' => 'Staff', 'slug' => 'staff', 'description' => 'Limited dashboard access by default.'],
        ] as $role) {
            Role::updateOrCreate(['slug' => $role['slug']], $role + ['is_system' => true, 'is_active' => true]);
        }

        foreach ([
            ['dashboard.view', 'Dashboard access', 'Dashboard'],
            ['settings.view', 'View agency settings', 'Settings'], ['settings.edit', 'Edit agency settings', 'Settings'],
            ['services.view', 'View services', 'Services'], ['services.create', 'Create services', 'Services'],
            ['services.edit', 'Edit services', 'Services'], ['services.delete', 'Delete services', 'Services'],
            ['duty-types.view', 'View duty types', 'Duty Types'], ['duty-types.create', 'Create duty types', 'Duty Types'],
            ['duty-types.edit', 'Edit duty types', 'Duty Types'], ['duty-types.delete', 'Delete duty types', 'Duty Types'],
            ['users.view', 'View users', 'Users'], ['users.create', 'Create users', 'Users'],
            ['users.edit', 'Edit users', 'Users'], ['users.activate', 'Activate or deactivate users', 'Users'],
            ['roles.view', 'View roles and permissions', 'Roles'], ['roles.edit', 'Edit role permissions', 'Roles'],
            ['workers.view', 'View workers', 'Workers'], ['workers.create', 'Create workers', 'Workers'],
            ['workers.edit', 'Edit workers', 'Workers'], ['workers.status', 'Change worker status', 'Workers'],
            ['worker-resumes.view', 'View worker resumes', 'Workers'], ['worker-resumes.generate', 'Generate worker resumes', 'Workers'],
            ['worker-resumes.download', 'Download worker resumes', 'Workers'], ['worker-resumes.print', 'Print worker resumes', 'Workers'],
            ['worker-resumes.share', 'Share worker resumes', 'Workers'],
            ['worker-registration-documents.view', 'View worker registration forms', 'Workers'],
            ['worker-registration-documents.generate', 'Generate worker registration forms', 'Workers'],
            ['worker-registration-documents.download', 'Download worker registration forms', 'Workers'],
            ['worker-registration-documents.print', 'Print worker registration forms', 'Workers'],
            ['worker-registration-documents.share', 'Share worker registration forms', 'Workers'],
            ['worker-generated-documents.view', 'View generated worker documents', 'Workers'],
            ['customer-registration-documents.view', 'View customer registration forms', 'Customers'],
            ['customer-registration-documents.generate', 'Generate customer registration forms', 'Customers'],
            ['customer-registration-documents.download', 'Download customer registration forms', 'Customers'],
            ['customer-registration-documents.print', 'Print customer registration forms', 'Customers'],
            ['customer-registration-documents.share', 'Share customer registration forms', 'Customers'],
            ['documents.view', 'View document center', 'Documents'], ['documents.download', 'Download documents', 'Documents'],
            ['documents.print', 'Print documents', 'Documents'], ['documents.share', 'Share documents', 'Documents'],
            ['worker-documents.view', 'View worker documents', 'Worker Documents'], ['worker-documents.create', 'Upload worker documents', 'Worker Documents'],
            ['worker-documents.verify', 'Verify worker documents', 'Worker Documents'],
            ['worker-interviews.view', 'View worker interviews', 'Worker Interviews'], ['worker-interviews.create', 'Create worker interviews', 'Worker Interviews'],
            ['worker-interviews.edit', 'Edit worker interviews', 'Worker Interviews'],
            ['worker-verification.view', 'View worker verification', 'Worker Verification'], ['worker-verification.edit', 'Edit worker verification', 'Worker Verification'],
            ['worker-availability.view', 'View worker availability', 'Worker Availability'], ['worker-availability.edit', 'Edit worker availability', 'Worker Availability'],
            ['customers.view', 'View customers', 'Customers'], ['customers.create', 'Create customers', 'Customers'],
            ['customers.edit', 'Edit customers', 'Customers'], ['customers.status', 'Change customer status', 'Customers'],
            ['customer-requirements.view', 'View customer requirements', 'Customer Requirements'], ['customer-requirements.create', 'Create customer requirements', 'Customer Requirements'],
            ['customer-requirements.edit', 'Edit customer requirements', 'Customer Requirements'], ['customer-requirements.status', 'Change requirement status', 'Customer Requirements'],
            ['worker-matching.view', 'View worker matching', 'Assignments'],
            ['worker-shortlists.view', 'View worker shortlists', 'Assignments'], ['worker-shortlists.manage', 'Manage worker shortlists', 'Assignments'],
            ['assignments.view', 'View assignments', 'Assignments'], ['assignments.create', 'Create assignments', 'Assignments'],
            ['assignments.edit', 'Edit assignments', 'Assignments'], ['assignments.status', 'Change assignment status', 'Assignments'],
            ['replacements.view', 'View replacements', 'Replacements'], ['replacements.create', 'Create replacements', 'Replacements'],
            ['replacements.manage', 'Manage replacements', 'Replacements'],
            ['agreements.view', 'View agreements', 'Agreements'], ['agreements.create', 'Create agreements', 'Agreements'],
            ['agreements.edit', 'Edit agreements', 'Agreements'], ['agreements.generate', 'Generate agreement PDFs', 'Agreements'],
            ['agreements.download', 'Download agreement PDFs', 'Agreements'], ['agreements.print', 'Print agreements', 'Agreements'],
            ['agreements.share', 'Share agreements', 'Agreements'], ['agreements.sign', 'Capture agreement signatures', 'Agreements'],
            ['agreements.cancel', 'Cancel agreements', 'Agreements'], ['agreement-templates.view', 'View agreement templates', 'Agreements'],
            ['agreement-templates.edit', 'Edit agreement templates', 'Agreements'],
            ['billing.view', 'View billing navigation', 'Billing'],
            ['invoices.view', 'View invoices', 'Billing'], ['invoices.create', 'Create invoices', 'Billing'],
            ['invoices.edit', 'Edit invoices', 'Billing'], ['invoices.generate', 'Generate invoice PDFs', 'Billing'],
            ['invoices.download', 'Download invoice PDFs', 'Billing'], ['invoices.print', 'Print invoices', 'Billing'],
            ['invoices.share', 'Share invoices', 'Billing'], ['invoices.cancel', 'Cancel invoices', 'Billing'],
            ['billing.payments.view', 'View payment billing', 'Billing'], ['payments.view', 'View payments', 'Billing'],
            ['payments.create', 'Record payments', 'Billing'], ['payments.complete', 'Complete payments', 'Billing'], ['payments.cancel', 'Cancel payments', 'Billing'],
            ['payment-receipts.view', 'View payment receipts', 'Billing'], ['payment-receipts.generate', 'Generate payment receipt PDFs', 'Billing'],
            ['payment-receipts.download', 'Download payment receipts', 'Billing'], ['payment-receipts.print', 'Print payment receipts', 'Billing'], ['payment-receipts.share', 'Share payment receipts', 'Billing'],
            ['reports.view', 'View management reports', 'Reports'], ['reports.workers', 'View worker reports', 'Reports'], ['reports.customers', 'View customer reports', 'Reports'],
            ['reports.requirements', 'View requirement reports', 'Reports'], ['reports.assignments', 'View assignment reports', 'Reports'], ['reports.replacements', 'View replacement reports', 'Reports'],
            ['reports.agreements', 'View agreement reports', 'Reports'], ['reports.invoices', 'View invoice reports', 'Reports'], ['reports.payments', 'View payment reports', 'Reports'],
            ['reports.outstanding', 'View outstanding reports', 'Reports'], ['reports.documents', 'View document reports', 'Reports'], ['reports.export', 'Export reports', 'Reports'],
            ['global-search.use', 'Use global search', 'Search'],
            ['enquiries.view', 'View enquiries', 'Enquiries'], ['enquiries.create', 'Create enquiries', 'Enquiries'], ['enquiries.edit', 'Edit enquiries', 'Enquiries'],
            ['enquiries.assign', 'Assign enquiries', 'Enquiries'], ['enquiries.status', 'Change enquiry status', 'Enquiries'], ['enquiries.convert', 'Convert enquiries', 'Enquiries'], ['enquiries.follow-up', 'Manage enquiry follow-ups', 'Enquiries'],
        ] as [$slug, $name, $module]) {
            Permission::updateOrCreate(['slug' => $slug], compact('slug', 'name', 'module'));
        }

        $allPermissionIds = Permission::pluck('id');
        Role::where('slug', 'super-admin')->firstOrFail()->permissions()->sync($allPermissionIds);
        Role::where('slug', 'admin')->firstOrFail()->permissions()->sync($allPermissionIds);
        // Keep permissions explicitly granted to staff when this idempotent seeder is re-run.
        Role::where('slug', 'staff')->firstOrFail()->permissions()->syncWithoutDetaching(Permission::where('slug', 'dashboard.view')->pluck('id'));
    }
}
