<?php

namespace App\Services;

use App\Models\Agreement;
use App\Models\Assignment;
use App\Models\Customer;
use App\Models\CustomerRequirement;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentReceipt;
use App\Models\CustomerGeneratedDocument;
use App\Models\ReplacementRequest;
use App\Models\Worker;
use App\Models\WorkerGeneratedDocument;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ReportService
{
    public function dateRange(array $filters = []): array
    {
        $start = $filters['start_date'] ?? null;
        $end = $filters['end_date'] ?? null;
        $quick = $filters['quick'] ?? null;
        if ($quick) {
            [$start, $end] = match ($quick) {
                'today' => [today(), today()],
                'yesterday' => [today()->subDay(), today()->subDay()],
                'week' => [now()->startOfWeek(), today()],
                'month' => [now()->startOfMonth(), today()],
                'last_month' => [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()],
                'financial_year' => [Carbon::create(today()->month >= 4 ? today()->year : today()->year - 1, 4, 1), Carbon::create(today()->month >= 4 ? today()->year + 1 : today()->year, 3, 31)],
                default => [$start, $end],
            };
        }
        $start = $start ? Carbon::parse($start)->startOfDay() : now()->startOfMonth()->startOfDay();
        $end = $end ? Carbon::parse($end)->endOfDay() : today()->endOfDay();
        if ($start->gt($end)) [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        return [$start, $end];
    }

    public function overview(array $filters = []): array
    {
        [$start, $end] = $this->dateRange($filters);
        return [
            'range' => [$start, $end],
            'snapshot' => [
                'Total Workers' => Worker::count(), 'Available Workers' => Worker::where('availability_status', 'available')->count(),
                'Working Workers' => Worker::where('availability_status', 'working')->count(), 'Verified Workers' => Worker::whereHas('verification', fn ($q) => $q->where('overall_verification_status', 'verified'))->count(),
                'Total Customers' => Customer::count(), 'Active Customers' => Customer::where('status', 'active')->count(),
                'Open Requirements' => CustomerRequirement::whereIn('requirement_status', ['open','worker_search','shortlisted'])->count(),
                'Assigned Requirements' => CustomerRequirement::where('requirement_status', 'assigned')->count(),
                'Active Assignments' => Assignment::whereIn('status', ['confirmed','active'])->count(),
                'Pending Replacements' => ReplacementRequest::whereNotIn('status', ['completed','rejected','cancelled'])->count(),
                'Total Agreements' => Agreement::count(), 'Active Agreements' => Agreement::where('status', 'active')->count(),
                'Total Invoices' => Invoice::count(), 'Invoice Amount' => Invoice::where('status', '!=', 'cancelled')->sum('total_amount'),
                'Outstanding Amount' => Invoice::where('status', '!=', 'cancelled')->sum('balance_amount'),
                'Overdue Amount' => Invoice::where('status', '!=', 'cancelled')->where('balance_amount', '>', 0)->whereDate('due_date', '<', today())->sum('balance_amount'),
            ],
            'activity' => [
                'Payments Received' => Payment::where('status', 'completed')->whereBetween('payment_date', [$start->toDateString(), $end->toDateString()])->sum('amount'),
                'New Customers' => Customer::whereBetween('registration_date', [$start->toDateString(), $end->toDateString()])->count(),
                'New Requirements' => CustomerRequirement::whereBetween('created_at', [$start, $end])->count(),
                'Assignments Created' => Assignment::whereBetween('created_at', [$start, $end])->count(),
            ],
            'serviceDemand' => CustomerRequirement::query()->join('services', 'services.id', '=', 'customer_requirements.service_id')->whereBetween('customer_requirements.created_at', [$start, $end])->selectRaw('services.name, COUNT(*) as total')->groupBy('services.id','services.name')->orderByDesc('total')->get(),
            'dutyDemand' => CustomerRequirement::query()->leftJoin('duty_types', 'duty_types.id', '=', 'customer_requirements.duty_type_id')->whereBetween('customer_requirements.created_at', [$start, $end])->selectRaw("COALESCE(duty_types.name, 'Custom / Other') as name, COUNT(*) as total")->groupBy('duty_types.id','duty_types.name')->orderByDesc('total')->get(),
            'paymentModes' => Payment::where('status', 'completed')->whereBetween('payment_date', [$start->toDateString(), $end->toDateString()])->selectRaw('payment_mode, SUM(amount) as total')->groupBy('payment_mode')->get(),
            'monthlyTrend' => $this->monthlyTrend($start, $end),
        ];
    }

    public function report(string $name, array $filters = [], int $perPage = 25): array
    {
        if ($name === 'documents') {
            $all = app(DocumentCenterService::class)->aggregate()->filter(function (array $row) use ($filters) {
                if (!empty($filters['document_type']) && $row['document_type'] !== $filters['document_type']) return false;
                if (!empty($filters['status']) && $row['status'] !== $filters['status']) return false;
                return true;
            })->values();
            $page = max(1, (int) ($filters['page'] ?? request()->integer('page', 1)));
            $paginator = new \Illuminate\Pagination\LengthAwarePaginator($all->forPage($page, $perPage)->values(), $all->count(), $perPage, $page, ['path' => request()->url(), 'query' => request()->query()]);
            return ['rows' => $paginator, 'summary' => ['Documents Generated' => $all->count(), 'Shared Documents' => $all->where('status','shared')->count(), 'Archived Documents' => $all->where('status','archived')->count()], 'columns' => $this->columns($name), 'range' => $this->dateRange($filters)];
        }
        $query = $this->query($name, $filters);
        $paginator = $query->paginate($perPage)->withQueryString();
        return ['rows' => $paginator, 'summary' => $this->summary($name, $filters), 'columns' => $this->columns($name), 'range' => $this->dateRange($filters)];
    }

    public function rowsForExport(string $name, array $filters = []): Collection
    {
        if ($name === 'documents') return app(DocumentCenterService::class)->aggregate()->values();
        return $this->query($name, $filters)->get()->map(fn ($row) => $this->normalize($name, $row));
    }

    public function displayRow(string $name, $row): array
    {
        if ($name === 'documents') return [$row['document_number'] ?? null, str(($row['document_type'] ?? ''))->replace('_',' ')->headline()->toString(), $row['related_name'] ?? null, optional($row['generated_at'])->format('d M Y'), $row['generated_by'] ?? null, $row['version_number'] ?? null, $row['status'] ?? null];
        return $this->normalize($name, $row);
    }

    public function globalSearch(string $term): array
    {
        $term = trim($term);
        if ($term === '') return [];
        $like = '%'.addcslashes($term, '%_').'%';
        $documents = WorkerGeneratedDocument::with('worker:id,name,worker_code')->where('document_number','like',$like)->limit(10)->get(['id','worker_id','document_type','document_number','status']);
        $documents = $documents->concat(CustomerGeneratedDocument::with('customer:id,name,customer_code')->where('document_number','like',$like)->limit(10)->get(['id','customer_id','document_type','document_number','status']))
            ->concat(PaymentReceipt::where('receipt_number','like',$like)->limit(10)->get(['id','receipt_number','customer_name_snapshot']));
        return [
            'Workers' => Worker::where(fn ($q) => $q->where('worker_code','like',$like)->orWhere('name','like',$like)->orWhere('mobile_number','like',$like))->limit(10)->get(['id','worker_code','name','mobile_number']),
            'Customers' => Customer::where(fn ($q) => $q->where('customer_code','like',$like)->orWhere('name','like',$like)->orWhere('mobile_number','like',$like))->limit(10)->get(['id','customer_code','name','mobile_number']),
            'Requirements' => CustomerRequirement::with('customer:id,name')->where('requirement_code','like',$like)->limit(10)->get(['id','requirement_code','customer_id','requirement_status']),
            'Assignments' => Assignment::with(['customer:id,name','worker:id,name'])->where('assignment_code','like',$like)->limit(10)->get(['id','assignment_code','customer_id','worker_id','status']),
            'Agreements' => Agreement::where(fn ($q) => $q->where('agreement_code','like',$like)->orWhere('customer_name_snapshot','like',$like)->orWhere('worker_name_snapshot','like',$like))->limit(10)->get(['id','agreement_code','customer_name_snapshot','worker_name_snapshot','status']),
            'Invoices' => Invoice::where(fn ($q) => $q->where('invoice_number','like',$like)->orWhere('customer_name_snapshot','like',$like))->limit(10)->get(['id','invoice_number','customer_name_snapshot','status','total_amount']),
            'Payments' => Payment::where('payment_code','like',$like)->limit(10)->get(['id','payment_code','status','amount']),
            'Documents' => $documents,
        ];
    }

    public function workerUtilization(int $limit = 100): Collection
    {
        return Worker::query()->with(['assignments' => fn ($q) => $q->whereIn('status', ['confirmed','active'])->latest('assignment_start_date')])->withCount('assignments')->withCount(['assignments as completed_assignments_count' => fn ($q) => $q->where('status','completed')])->withCount('replacementsAsOldWorker as replacement_count')->limit($limit)->get();
    }

    public function columns(string $name): array
    {
        return match ($name) {
            'workers' => ['Worker Code','Name','Mobile','Service(s)','City','Experience','Availability','Status','Verification','Registration Date'],
            'customers' => ['Customer Code','Customer Name','Mobile','City','Registration Date','Active Requirements','Latest Service','Status'],
            'requirements' => ['Requirement Code','Customer','Mobile','Service','Duty Type','Required Persons','Salary Budget','Agency Charge','Preferred Start','Status'],
            'assignments' => ['Assignment Code','Customer','Worker','Service','Duty Type','Start Date','End Date','Salary','Agency Charge','Status'],
            'replacements' => ['Replacement Code','Customer','Old Worker','New Worker','Service','Reason','Requested Date','Completed Date','Status'],
            'agreements' => ['Agreement Code','Customer','Worker','Service','Agreement Date','Start Date','End Date','Agency Charge','Status'],
            'invoices' => ['Invoice Number','Date','Customer','Subtotal','Tax','Total','Paid','Balance','Payment Status','Invoice Status'],
            'outstanding' => ['Invoice Number','Date','Customer','Subtotal','Tax','Total','Paid','Balance','Days Overdue','Payment Status','Invoice Status'],
            'payments' => ['Payment Code','Date','Customer','Invoice','Mode','Amount','Reference','Received By','Status'],
            'documents' => ['Document Number','Type','Related Person','Generated Date','Generated By','Version','Status'],
            default => [],
        };
    }

    private function query(string $name, array $f): Builder
    {
        [$start, $end] = $this->dateRange($f);
        return match ($name) {
            'workers' => $this->workers($f),
            'customers' => Customer::query()->withCount(['requirements as active_requirements_count' => fn ($q) => $q->whereIn('requirement_status',['open','worker_search','shortlisted','assigned'])])->with(['requirements.service'])->when($f['status'] ?? null, fn($q,$v)=>$q->where('status',$v))->when($f['city'] ?? null, fn($q,$v)=>$q->where('city',$v))->when($f['state'] ?? null, fn($q,$v)=>$q->where('state',$v))->when($f['start_date'] ?? null, fn($q,$v)=>$q->whereDate('registration_date','>=',$v))->when($f['end_date'] ?? null, fn($q,$v)=>$q->whereDate('registration_date','<=',$v))->orderByDesc('registration_date'),
            'requirements' => CustomerRequirement::query()->with(['customer','service','dutyType'])->when($f['status'] ?? null, fn($q,$v)=>$q->where('requirement_status',$v))->when($f['service_id'] ?? null, fn($q,$v)=>$q->where('service_id',$v))->when($f['duty_type_id'] ?? null, fn($q,$v)=>$q->where('duty_type_id',$v))->when($f['required_gender'] ?? null, fn($q,$v)=>$q->where('required_gender',$v))->when($f['city'] ?? null, fn($q,$v)=>$q->whereHas('customer',fn($x)=>$x->where('city',$v)))->when($f['start_date'] ?? null, fn($q,$v)=>$q->whereDate('created_at','>=',$v))->when($f['end_date'] ?? null, fn($q,$v)=>$q->whereDate('created_at','<=',$v))->orderByDesc('created_at'),
            'assignments' => Assignment::query()->with(['customer','worker','service','dutyType'])->when($f['status'] ?? null, fn($q,$v)=>$q->where('status',$v))->when($f['service_id'] ?? null, fn($q,$v)=>$q->where('service_id',$v))->when($f['duty_type_id'] ?? null, fn($q,$v)=>$q->where('duty_type_id',$v))->when($f['customer_id'] ?? null, fn($q,$v)=>$q->where('customer_id',$v))->when($f['worker_id'] ?? null, fn($q,$v)=>$q->where('worker_id',$v))->when($f['start_date'] ?? null, fn($q,$v)=>$q->whereDate('assignment_start_date','>=',$v))->when($f['end_date'] ?? null, fn($q,$v)=>$q->whereDate('assignment_start_date','<=',$v))->orderByDesc('assignment_start_date'),
            'replacements' => ReplacementRequest::query()->with(['customer','oldWorker','newWorker','requirement.service'])->when($f['status'] ?? null, fn($q,$v)=>$q->where('status',$v))->when($f['reason'] ?? null, fn($q,$v)=>$q->where('reason',$v))->when($f['service_id'] ?? null, fn($q,$v)=>$q->whereHas('requirement',fn($x)=>$x->where('service_id',$v)))->when($f['customer_id'] ?? null, fn($q,$v)=>$q->where('customer_id',$v))->when($f['start_date'] ?? null, fn($q,$v)=>$q->whereDate('requested_date','>=',$v))->when($f['end_date'] ?? null, fn($q,$v)=>$q->whereDate('requested_date','<=',$v))->orderByDesc('requested_date'),
            'agreements' => Agreement::query()->with(['customer','worker','service'])->when($f['status'] ?? null, fn($q,$v)=>$q->where('status',$v))->when($f['service_id'] ?? null, fn($q,$v)=>$q->where('service_id',$v))->when($f['customer_id'] ?? null, fn($q,$v)=>$q->where('customer_id',$v))->when($f['worker_id'] ?? null, fn($q,$v)=>$q->where('worker_id',$v))->when($f['start_date'] ?? null, fn($q,$v)=>$q->whereDate('agreement_date','>=',$v))->when($f['end_date'] ?? null, fn($q,$v)=>$q->whereDate('agreement_date','<=',$v))->orderByDesc('agreement_date'),
            'invoices','outstanding' => Invoice::query()->with('customer')->when($name==='outstanding',fn($q)=>$q->where('balance_amount','>',0)->where('status','!=','cancelled'))->when(($f['overdue_only'] ?? null), fn($q)=>$q->where('balance_amount','>',0)->whereDate('due_date','<',today()))->when(($f['status'] ?? null), fn($q,$v)=>$q->where('status',$v))->when(($f['payment_status'] ?? null) === 'overdue', fn($q)=>$q->where('balance_amount','>',0)->whereDate('due_date','<',today()))->when(($f['payment_status'] ?? null) && ($f['payment_status'] ?? null) !== 'overdue', fn($q,$v)=>$q->where('payment_status',$v))->when($f['customer_id'] ?? null, fn($q,$v)=>$q->where('customer_id',$v))->when($f['start_date'] ?? null, fn($q,$v)=>$q->whereDate('invoice_date','>=',$v))->when($f['end_date'] ?? null, fn($q,$v)=>$q->whereDate('invoice_date','<=',$v))->orderByDesc('invoice_date'),
            'payments' => Payment::query()->with(['customer','invoice','receiver'])->when($f['status'] ?? null, fn($q,$v)=>$q->where('status',$v))->when($f['payment_mode'] ?? null, fn($q,$v)=>$q->where('payment_mode',$v))->when($f['received_by'] ?? null, fn($q,$v)=>$q->where('received_by',$v))->when($f['customer_id'] ?? null, fn($q,$v)=>$q->where('customer_id',$v))->when($f['start_date'] ?? null, fn($q,$v)=>$q->whereDate('payment_date','>=',$v))->when($f['end_date'] ?? null, fn($q,$v)=>$q->whereDate('payment_date','<=',$v))->orderByDesc('payment_date'),
            default => Customer::query()->whereKey(0),
        };
    }

    private function workers(array $f): Builder
    {
        return Worker::query()->with(['services','preferredDutyType','verification'])->when($f['worker_status'] ?? null, fn($q,$v)=>$q->where('worker_status',$v))->when($f['availability_status'] ?? null, fn($q,$v)=>$q->where('availability_status',$v))->when($f['verification_status'] ?? null, fn($q,$v)=>$q->whereHas('verification',fn($x)=>$x->where('overall_verification_status',$v)))->when($f['gender'] ?? null, fn($q,$v)=>$q->where('gender',$v))->when($f['city'] ?? null, fn($q,$v)=>$q->where('city',$v))->when($f['state'] ?? null, fn($q,$v)=>$q->where('state',$v))->when($f['duty_type_id'] ?? null, fn($q,$v)=>$q->where('preferred_duty_type_id',$v))->when($f['service_id'] ?? null, fn($q,$v)=>$q->whereHas('services',fn($x)=>$x->whereKey($v)))->when($f['start_date'] ?? null, fn($q,$v)=>$q->whereDate('registration_date','>=',$v))->when($f['end_date'] ?? null, fn($q,$v)=>$q->whereDate('registration_date','<=',$v))->orderByDesc('registration_date');
    }

    private function summary(string $name, array $f): array
    {
        return match ($name) {
            'workers' => ['Total Workers'=>Worker::count(),'Available'=>Worker::where('availability_status','available')->count(),'Working'=>Worker::where('availability_status','working')->count(),'Inactive'=>Worker::where('worker_status','inactive')->count(),'Verified'=>Worker::whereHas('verification',fn($q)=>$q->where('overall_verification_status','verified'))->count(),'Pending Verification'=>Worker::whereHas('verification',fn($q)=>$q->where('overall_verification_status','!=','verified'))->count()],
            'customers' => ['Total Customers'=>Customer::count(),'New Customers'=>Customer::whereBetween('registration_date',$this->dateRange($f))->count(),'Active Customers'=>Customer::where('status','active')->count(),'Inactive Customers'=>Customer::where('status','inactive')->count()],
            'requirements' => collect(['open','worker_search','shortlisted','assigned','completed','cancelled'])->mapWithKeys(fn($s)=>[str($s)->headline()->toString()=>CustomerRequirement::where('requirement_status',$s)->count()])->all(),
            'assignments' => collect(['confirmed','active','completed','cancelled','replaced'])->mapWithKeys(fn($s)=>[str($s)->headline()->toString()=>Assignment::where('status',$s)->count()])->all(),
            'replacements' => collect(['requested','under_review','approved','worker_search','shortlisted','completed','rejected','cancelled'])->mapWithKeys(fn($s)=>[str($s)->headline()->toString()=>ReplacementRequest::where('status',$s)->count()])->merge(ReplacementRequest::query()->selectRaw('reason, COUNT(*) as total')->groupBy('reason')->get()->mapWithKeys(fn($r)=>['Reason: '.str($r->reason)->headline()->toString() => $r->total]))->all(),
            'agreements' => collect(['draft','generated','sent','signed','active','expired','cancelled'])->mapWithKeys(fn($s)=>[str($s)->headline()->toString()=>Agreement::where('status',$s)->count()])->all(),
            'invoices','outstanding' => ['Invoice Count'=>Invoice::where('status','!=','cancelled')->count(),'Subtotal'=>Invoice::where('status','!=','cancelled')->sum('subtotal'),'Tax Collected'=>Invoice::where('status','!=','cancelled')->sum('tax_total'),'Invoice Total'=>Invoice::where('status','!=','cancelled')->sum('total_amount'),'Paid'=>Invoice::where('status','!=','cancelled')->sum('paid_amount'),'Outstanding'=>Invoice::where('status','!=','cancelled')->sum('balance_amount'),'Overdue'=>Invoice::where('status','!=','cancelled')->where('balance_amount','>',0)->whereDate('due_date','<',today())->sum('balance_amount')],
            'payments' => ['Completed Payments'=>Payment::where('status','completed')->count(),'Amount Received'=>Payment::where('status','completed')->sum('amount'),'Cash'=>Payment::where('status','completed')->where('payment_mode','cash')->sum('amount'),'UPI'=>Payment::where('status','completed')->where('payment_mode','upi')->sum('amount'),'Bank Transfer'=>Payment::where('status','completed')->where('payment_mode','bank_transfer')->sum('amount'),'Cheque'=>Payment::where('status','completed')->where('payment_mode','cheque')->sum('amount'),'Other'=>Payment::where('status','completed')->where('payment_mode','other')->sum('amount')],
            default => [],
        };
    }

    private function normalize(string $name, $r): array
    {
        return match ($name) {
            'workers' => [$r->worker_code,$r->name,$r->mobile_number,$r->services->pluck('name')->join(', '),$r->city,$r->years_of_experience,$r->availability_status?->value,$r->worker_status?->value,$r->verification?->overall_verification_status?->value,$r->registration_date?->toDateString()],
            'customers' => [$r->customer_code,$r->name,$r->mobile_number,$r->city,$r->registration_date?->toDateString(),$r->active_requirements_count,$r->requirements->sortByDesc('created_at')->first()?->service?->name,$r->status?->value],
            'requirements' => [$r->requirement_code,$r->customer?->name,$r->customer?->mobile_number,$r->service?->name,$r->dutyType?->name ?: $r->custom_duty_type,$r->number_of_persons,$r->monthly_salary_budget,$r->monthly_agency_service_charge,$r->preferred_start_date?->toDateString(),$r->requirement_status?->value],
            'assignments' => [$r->assignment_code,$r->customer?->name,$r->worker?->name,$r->service?->name,$r->dutyType?->name,$r->assignment_start_date?->toDateString(),$r->assignment_end_date?->toDateString(),$r->monthly_salary,$r->agency_service_charge,$r->status?->value],
            'replacements' => [$r->replacement_code,$r->customer?->name,$r->oldWorker?->name,$r->newWorker?->name,$r->requirement?->service?->name,$r->reason,$r->requested_date?->toDateString(),$r->completed_at?->toDateString(),$r->status?->value],
            'agreements' => [$r->agreement_code,$r->customer_name_snapshot ?: $r->customer?->name,$r->worker_name_snapshot ?: $r->worker?->name,$r->service?->name,$r->agreement_date?->toDateString(),$r->start_date?->toDateString(),$r->end_date?->toDateString(),$r->monthly_agency_service_charge,$r->status?->value],
            'invoices' => [$r->invoice_number,$r->invoice_date?->toDateString(),$r->customer_name_snapshot ?: $r->customer?->name,$r->subtotal,$r->tax_total,$r->total_amount,$r->paid_amount,$r->balance_amount,$r->effectivePaymentStatus()->value,$r->status?->value],
            'outstanding' => [$r->invoice_number,$r->invoice_date?->toDateString(),$r->customer_name_snapshot ?: $r->customer?->name,$r->subtotal,$r->tax_total,$r->total_amount,$r->paid_amount,$r->balance_amount,($r->balance_amount > 0 && $r->due_date?->isBefore(today())) ? $r->due_date->diffInDays(today()) : 0,$r->effectivePaymentStatus()->value,$r->status?->value],
            'payments' => [$r->payment_code,$r->payment_date?->toDateString(),$r->customer?->name,$r->invoice?->invoice_number,$r->payment_mode?->value,$r->amount,$r->transaction_reference,$r->receiver?->name,$r->status?->value],
            default => (array) $r,
        };
    }

    private function monthlyTrend(Carbon $start, Carbon $end): Collection
    {
        return Customer::whereBetween('registration_date', [$start->toDateString(),$end->toDateString()])->selectRaw("strftime('%Y-%m', registration_date) as month, COUNT(*) as customers")->groupBy('month')->orderBy('month')->get();
    }
}
