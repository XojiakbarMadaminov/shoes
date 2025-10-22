<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <title>Savdo Cheki #{{ $sale->id }}</title>
    <style>
        @page { margin: 10px; }
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 12px;
            color: #000;
            margin: 0;
            background: #fff;
        }
        .receipt {
            width: 76mm;
            margin: 0 auto;
        }
        .center { text-align: center; }
        .bold { font-weight: 700; }
        .line { border-bottom: 1px dashed #000; margin: 6px 0; }
        .item-row { display: flex; justify-content: space-between; align-items: flex-end; margin: 4px 0; }
        .item-row .item-name { flex: 1; }
        .item-row .item-total { text-align: right; min-width: 60px; }
        .receipt-footer-printonly img { display: block; margin: 0 auto; }
    </style>
    @php
        $receiptData  = \App\Support\ReceiptData::fromSale($sale);
        $qrPublicPath = public_path('images/traktor-qr.png');
        $qrPath       = is_file($qrPublicPath) ? ('file://' . $qrPublicPath) : null;
    @endphp
</head>
<body>
    <div class="receipt">
        @include('receipts.partials.default', [
            'receiptData' => $receiptData,
            'qrPath' => $qrPath,
        ])
    </div>
</body>
</html>
