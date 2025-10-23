@php
    $data = $receiptData ?? [];
    $meta = $data['meta'] ?? [];
    $paymentType = $meta['payment_type'] ?? null;
    $paymentLabel = match ($paymentType) {
        'cash'     => 'Naqd',
        'card'     => 'Karta',
        'debt'     => 'Qarz',
        'transfer' => 'O\'tkazma',
        'partial'  => 'Qisman',
        'mixed'    => 'Karta + Naqd',
        'preorder' => 'Oldindan buyurtma',
        default    => 'Noma\'lum',
    };

    $items  = $data['items'] ?? [];
    $totals = $data['totals'] ?? ['qty' => 0, 'amount' => 0];

    $cartIdFormatted = $data['cart_id'] ?? null;
    $storeIdFormatted = $data['store_id'] ?? null;
    $showQr          = $showQr ?? true;
    $qrPath          = $qrPath ?? null;
@endphp

@once
    <style>
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
        .item-row .item-name { flex: 1; }
        .item-row .item-total { text-align: right; min-width: 24mm; }
        .receipt-footer-printonly img {
            display: block;
            margin: 0 auto 2mm auto;
            max-width: 40mm;
            max-height: 40mm;
        }
    </style>
@endonce

<div class="center" style="margin-bottom:10px; margin-top:5px;">
    <h3 style="margin:0;">** Mir obuv 9494 **</h3>
</div>
<div class="center bold" style="font-size:18px; margin-bottom:6px;">SAVDO CHEKI</div>
<div class="center" style="margin-bottom:4px;">{{ $data['receipt_number'] ?? '' }}</div>
<div class="center" style="margin-bottom:8px;">{{ $data['date'] ?? '' }}</div>

<div>Store: #{{ $storeIdFormatted !== null ? $storeIdFormatted : '—' }}</div>
<div style="margin-top:8px; font-size:12px;">
    <div>ID: <strong>{{ $meta['sale_id'] ?? '—' }}</strong></div>
    <div>Klient: <strong>{{ $meta['client_name'] ?? 'Tanlanmagan' }}</strong></div>
    @if(!empty($meta['cashier_name']))
        <div>Kassir: <strong>{{ $meta['cashier_name'] }}</strong></div>
    @endif
    <div>To'lov turi: <strong>{{ $paymentLabel }}</strong></div>
    @if(($paymentType) === 'partial')
        <div>To'langan summa: <strong>{{ number_format((float) ($meta['paid_amount'] ?? 0), 2, '.', ' ') }} so'm</strong></div>
    @endif
    @if(($meta['remaining_amount'] ?? 0) > 0)
        <div>Qolgan qarz: <strong>{{ number_format((float) ($meta['remaining_amount'] ?? 0), 2, '.', ' ') }} so'm</strong></div>
    @endif
</div>

<div class="line"></div>

@foreach($items as $item)
    @php
        $itemName  = $item['name'] ?? 'Mahsulot';
        $qty       = (float) ($item['qty'] ?? 0);
        $price     = (float) ($item['price'] ?? 0);
        $lineTotal = $qty * $price;
    @endphp
    <div class="item-row">
        <span class="item-name">
            {{ $itemName }}<br>
            <span style="font-size:11px;">{{ rtrim(rtrim(number_format($qty, 2, '.', ''), '0'), '.') }} x {{ number_format($price, 0, '.', ' ') }}</span>
        </span>
        <span class="item-total bold">{{ number_format($lineTotal, 0, '.', ' ') }} so'm</span>
    </div>
@endforeach

<div class="line"></div>

<div class="item-row">
    <span>Jami mahsulotlar:</span>
    <span>{{ rtrim(rtrim(number_format((float) ($totals['qty'] ?? 0), 2, '.', ''), '0'), '.') }} dona</span>
</div>
<div class="item-row bold" style="font-size:15px;">
    <span>JAMI SUMMA:</span>
    <span>{{ number_format((float) ($totals['amount'] ?? 0), 0, '.', ' ') }} so'm</span>
</div>
<div class="center" style="margin-top:18px; font-size:12px;">
    Xaridingiz uchun rahmat!<br>
    Yana tashrifingizni kutamiz
</div>

@if($showQr && $qrPath)
    <div class="receipt-footer-printonly" style="margin-top:12px;">
        <img src="{{ $qrPath }}" alt="QR code" style="max-width:32mm; max-height:32mm;">
    </div>
@endif
