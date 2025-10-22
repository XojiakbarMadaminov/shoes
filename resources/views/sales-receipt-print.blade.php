@php use App\Support\ReceiptData; @endphp
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <title>Savdo Cheki #{{ $sale->id }}</title>
    <style>
        @page { size: 80mm auto; margin: 0; }
        body {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            margin: 0;
            padding: 0;
            background: #fff;
        }
        .receipt {
            width: 76mm;
            margin: 0 auto;
            padding-left: 2mm;
            padding-right: 2mm;
            page-break-inside: avoid;
        }
        .center { text-align: center; }
        .bold { font-weight: 700; }
        .line { border-bottom: 1px dashed #000; margin: 2mm 0; }
        .item-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin: 1mm 0;
            page-break-inside: avoid;
        }
        .item-name { flex: 1; }
        .item-total { text-align: right; min-width: 24mm; }
        .receipt-footer-printonly img {
            display: block;
            margin: 0 auto 2mm auto;
            max-width: 40mm;
            max-height: 40mm;
        }
    </style>
</head>
<body>
    @php
        $receiptData = ReceiptData::fromSale($sale);
    @endphp
    <div class="receipt" id="sale-receipt">
        @include('receipts.partials.default', [
            'receiptData' => $receiptData,
            'qrPath' => asset('images/traktor-qr.png'),
        ])
    </div>

    <script>
        window.addEventListener('load', () => {
            window.print();
            setTimeout(() => window.close(), 300);
        });
    </script>
</body>
</html>
