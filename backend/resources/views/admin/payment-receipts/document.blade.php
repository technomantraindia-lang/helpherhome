<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { size: A4; margin: 20mm; }
        body { font-family: DejaVu Sans; color: #252525; font-size: 11px; }
        .top { display: table; width: 100%; border-bottom: 3px solid #c89835; padding-bottom: 15px; }
        .cell { display: table-cell; width: 50%; vertical-align: top; }
        .right { text-align: right; }
        .brand { font-size: 24px; font-weight: bold; color: #a97718; }
        .title { font-size: 22px; font-weight: bold; color: #a97718; }
        .cancelled { margin-top: 10px; padding: 8px; border: 2px solid #b91c1c; color: #b91c1c; font-size: 16px; font-weight: bold; text-align: center; }
        .box { margin-top: 25px; border: 1px solid #ddd; padding: 18px; }
        .row { display: table; width: 100%; padding: 8px 0; border-bottom: 1px solid #eee; }
        .label { display: table-cell; }
        .value { display: table-cell; text-align: right; font-weight: bold; }
        .footer { margin-top: 60px; }
        .sign { max-width: 110px; max-height: 55px; }
        .stamp { max-width: 90px; max-height: 70px; }
    </style>
</head>
<body>
    <div class="top">
        <div class="cell">
            <div class="brand">{{ $receipt->agency_name_snapshot ?: 'Helper Home' }}</div>
            <div>Home Care &amp; Domestic Services</div>
            <p>{{ $receipt->agency_address_snapshot }}<br>{{ $receipt->agency_phone_snapshot }} · {{ $receipt->agency_email_snapshot }}<br>{{ $receipt->agency_gst_snapshot }}</p>
        </div>
        <div class="cell right">
            <div class="title">PAYMENT RECEIPT</div>
            @if($receipt->payment?->status === \App\Enums\PaymentRecordStatus::Cancelled)
                <div class="cancelled">CANCELLED PAYMENT</div>
            @endif
            <p><strong>Receipt No:</strong> {{ $receipt->receipt_number }}<br><strong>Date:</strong> {{ $receipt->receipt_date->format('d M Y') }}</p>
        </div>
    </div>

    <div class="box">
        <strong>Received From</strong>
        <p>{{ $receipt->customer_name_snapshot }}<br>{{ $receipt->customer_address_snapshot }}<br>{{ $receipt->customer_mobile_snapshot }}<br>{{ $receipt->customer_email_snapshot }}</p>
        <strong>Against Invoice:</strong> {{ $receipt->invoice?->invoice_number }}
    </div>

    <div class="box">
        <div class="row"><span class="label">Invoice Amount</span><span class="value">₹{{ number_format((float)$receipt->invoice_total_snapshot, 2) }}</span></div>
        <div class="row"><span class="label">Previously Paid</span><span class="value">₹{{ number_format((float)$receipt->previously_paid_snapshot, 2) }}</span></div>
        <div class="row"><span class="label">Payment Received</span><span class="value">₹{{ number_format((float)$receipt->payment_amount_snapshot, 2) }}</span></div>
        <div class="row"><span class="label">Balance Due</span><span class="value">₹{{ number_format((float)$receipt->balance_amount_snapshot, 2) }}</span></div>
    </div>

    <div class="box">
        <strong>Payment Details</strong>
        <p>Mode: {{ str($receipt->payment_mode_snapshot)->replace('_', ' ')->headline() }}<br>Reference: {{ $receipt->transaction_reference_snapshot ?: '—' }}<br>Payment Date: {{ $receipt->receipt_date->format('d M Y') }}</p>
        @if($receipt->notes_snapshot)<p>Notes: {{ $receipt->notes_snapshot }}</p>@endif
    </div>

    <div class="footer right">
        @if($signatureData)<img class="sign" src="{{ $signatureData }}">@endif
        @if($stampData)<img class="stamp" src="{{ $stampData }}">@endif
        <br>Authorized Sign &amp; Stamp
    </div>
</body>
</html>
