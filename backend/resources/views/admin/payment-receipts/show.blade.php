@extends('layouts.admin')
@section('title', $receipt->receipt_number)
@section('heading', $receipt->receipt_number)
@section('subheading', 'Payment receipt')
@section('actions')
    <div class="flex flex-wrap gap-2">
        <a class="btn-secondary" href="{{ route('admin.payment-receipts.preview', $receipt) }}">Preview</a>
        <a class="btn-secondary" target="_blank" href="{{ route('admin.payment-receipts.print', $receipt) }}">Print</a>
        @if($receipt->pdf_path)<a class="btn-secondary" href="{{ route('admin.payment-receipts.download', $receipt) }}">Download</a>@endif
    </div>
@endsection
@section('content')
    @if($receipt->payment?->status === \App\Enums\PaymentRecordStatus::Cancelled)
        <div class="mb-6 rounded-xl border-2 border-red-700 bg-red-50 p-4 font-black text-red-800">CANCELLED PAYMENT — retained for audit history and not counted as received.</div>
    @endif
    <section class="card p-6">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach([['Receipt', $receipt->receipt_number], ['Invoice', $receipt->invoice?->invoice_number], ['Customer', $receipt->customer_name_snapshot], ['Payment Mode', str($receipt->payment_mode_snapshot)->replace('_', ' ')->headline()], ['Invoice Total', '₹'.$receipt->invoice_total_snapshot], ['Previously Paid', '₹'.$receipt->previously_paid_snapshot], ['Received', '₹'.$receipt->payment_amount_snapshot], ['Balance', '₹'.$receipt->balance_amount_snapshot]] as [$label, $value])
                <div><p class="text-xs uppercase text-neutral-400">{{ $label }}</p><p class="mt-1 font-semibold">{{ $value }}</p></div>
            @endforeach
        </div>
    </section>
    <section class="card mt-6 p-6">
        <h2 class="font-black">Receipt Actions</h2>
        <div class="mt-4 flex flex-wrap gap-3">
            @if(auth()->user()->hasPermission('payment-receipts.generate'))
                <form method="POST" action="{{ route('admin.payment-receipts.generate', $receipt) }}">@csrf @if($receipt->pdf_version)<label class="mr-2"><input required type="checkbox" name="confirm_regenerate" value="1"> Regenerate version {{ $receipt->pdf_version + 1 }}</label>@endif<button class="btn-primary">{{ $receipt->pdf_path ? 'Generate New PDF' : 'Generate PDF' }}</button></form>
            @endif
            @if($receipt->pdf_path && auth()->user()->hasPermission('payment-receipts.share'))
                <form method="POST" action="{{ route('admin.payment-receipts.share.whatsapp', $receipt) }}">@csrf<button class="btn-secondary">WhatsApp Receipt</button></form>
            @endif
        </div>
    </section>
@endsection
