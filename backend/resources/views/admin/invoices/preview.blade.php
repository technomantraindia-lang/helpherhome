<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $invoice->invoice_number }} · Invoice Preview</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: #f1f1f3;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: #171717;
        }
        .header-bar {
            position: sticky;
            top: 0;
            z-index: 50;
            background: #171717;
            padding: 12px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 14px rgba(0,0,0,0.25);
        }
        .brand-meta {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .brand-name {
            font-size: 15px;
            font-weight: 800;
            color: #d6ad60;
            letter-spacing: 0.5px;
        }
        .doc-badge {
            background: rgba(214, 173, 96, 0.18);
            color: #f3d48d;
            font-size: 12px;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 999px;
            border: 1px solid rgba(214, 173, 96, 0.35);
        }
        .action-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .btn-print {
            background: #b88a35;
            color: #ffffff;
            border: 0;
            padding: 9px 18px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .btn-print:hover {
            background: #9d742b;
        }
        .btn-download {
            background: #ffffff;
            color: #171717;
            border: 1px solid #d1d5db;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.15s ease;
        }
        .btn-download:hover {
            background: #f9fafb;
        }
        .btn-back {
            background: transparent;
            color: #9ca3af;
            border: 1px solid #374151;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.15s ease;
        }
        .btn-back:hover {
            color: #ffffff;
            border-color: #6b7280;
        }
        .paper-wrapper {
            max-width: 860px;
            margin: 28px auto 40px;
            background: #ffffff;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08), 0 2px 6px rgba(0,0,0,0.04);
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #e5e7eb;
        }
        .paper-wrapper iframe {
            width: 100%;
            height: 1180px;
            border: none;
            display: block;
            background: #ffffff;
        }
        @media (max-width: 880px) {
            .paper-wrapper {
                margin: 0;
                border-radius: 0;
                border: 0;
                box-shadow: none;
            }
        }
        @media print {
            .header-bar { display: none !important; }
            body { background: #ffffff !important; margin: 0 !important; }
            .paper-wrapper { margin: 0 !important; max-width: 100% !important; border: none !important; box-shadow: none !important; border-radius: 0 !important; }
            .paper-wrapper iframe { height: 100vh !important; }
        }
    </style>
</head>
<body>
<div class="header-bar">
    <div class="brand-meta">
        <span class="brand-name">Helper Home</span>
        <span class="doc-badge">{{ $invoice->invoice_number }}</span>
    </div>
    <div class="action-group">
        <button type="button" class="btn-print" onclick="printDoc()">Print Invoice</button>
        @if($invoice->pdf_path)
            <a class="btn-download" href="{{ route('admin.invoices.download', $invoice) }}">Download PDF</a>
        @endif
        <a class="btn-back" href="{{ route('admin.invoices.show', $invoice) }}">Back to Invoice</a>
    </div>
</div>

<div class="paper-wrapper">
    <iframe id="invoiceFrame" title="Invoice {{ $invoice->invoice_number }}" srcdoc="{!! htmlspecialchars(view('admin.invoices.document', compact('invoice','logoData','qrData','signatureData','stampData'))->render(), ENT_QUOTES, 'UTF-8') !!}"></iframe>
</div>

<script>
    function printDoc() {
        const frame = document.getElementById('invoiceFrame');
        if (frame && frame.contentWindow) {
            frame.contentWindow.focus();
            frame.contentWindow.print();
        } else {
            window.print();
        }
    }

    const frame = document.getElementById('invoiceFrame');
    if (frame) {
        frame.addEventListener('load', () => {
            try {
                const doc = frame.contentDocument || frame.contentWindow.document;
                const scrollH = doc.documentElement.scrollHeight || doc.body.scrollHeight;
                if (scrollH > 600) {
                    frame.style.height = (scrollH + 40) + 'px';
                }
            } catch (err) {}
        });
    }

    @if(request()->boolean('print') || request()->routeIs('admin.invoices.print'))
        window.addEventListener('load', () => {
            setTimeout(printDoc, 500);
        });
    @endif
</script>
</body>
</html>
