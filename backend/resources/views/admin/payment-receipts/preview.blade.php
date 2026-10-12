<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $receipt->receipt_number }} · Payment Receipt</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #f1f1f3; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        .actions { display: flex; justify-content: space-between; align-items: center; padding: 12px 24px; background: #171717; box-shadow: 0 4px 14px rgba(0,0,0,0.25); }
        .brand-meta { color: #d6ad60; font-weight: 800; font-size: 15px; }
        .action-btns { display: flex; gap: 10px; }
        .actions button { background: #b88a35; color: #fff; padding: 9px 18px; border: 0; border-radius: 8px; font-weight: 700; font-size: 13px; cursor: pointer; }
        .actions button:hover { background: #9d742b; }
        .actions a { background: transparent; color: #9ca3af; border: 1px solid #374151; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 13px; }
        .actions a:hover { color: #fff; border-color: #6b7280; }
        .paper { background: #fff; max-width: 860px; margin: 28px auto 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); border-radius: 12px; overflow: hidden; border: 1px solid #e5e7eb; }
        .paper iframe { width: 100%; height: 1100px; border: 0; display: block; }
        @media (max-width: 880px) { .paper { margin: 0; border-radius: 0; border: 0; } }
        @media print { .actions { display: none !important; } .paper { margin: 0 !important; max-width: 100% !important; box-shadow: none !important; border: 0 !important; } }
    </style>
</head>
<body>
<div class="actions">
    <div class="brand-meta">Helper Home · Receipt {{ $receipt->receipt_number }}</div>
    <div class="action-btns">
        <button onclick="document.getElementById('receiptFrame').contentWindow.print()">Print Receipt</button>
        <a href="{{ route('admin.payment-receipts.show', $receipt) }}">Back</a>
    </div>
</div>
<div class="paper">
    <iframe id="receiptFrame" title="Payment Receipt" srcdoc="{!! htmlspecialchars(view('admin.payment-receipts.document', compact('receipt','signatureData','stampData'))->render(), ENT_QUOTES, 'UTF-8') !!}"></iframe>
</div>
<script>
    const frame = document.getElementById('receiptFrame');
    if (frame) {
        frame.addEventListener('load', () => {
            try {
                const doc = frame.contentDocument || frame.contentWindow.document;
                const scrollH = doc.documentElement.scrollHeight || doc.body.scrollHeight;
                if (scrollH > 500) frame.style.height = (scrollH + 40) + 'px';
            } catch (err) {}
        });
    }
</script>
</body>
</html>
