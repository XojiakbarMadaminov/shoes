<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <title>Savdo Cheki #{{ $sale->id }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color:#000; }
        .center { text-align: center; }
        .bold { font-weight: 700; }
        .line { border-top: 1px dashed #000; margin: 6px 0; }
        .item-row { display: flex; justify-content: space-between; margin: 2px 0; }
        .item-name { max-width: 70%; }
        .item-total { min-width: 30%; text-align: right; }
    </style>
    @php
        $paymentLabel = match($sale->payment_type){
            'cash' => 'Naqd', 'card' => 'Karta', 'debt' => 'Qarz', 'transfer' => 'O‘tkazma', 'partial' => 'Qisman', 'mixed' => 'Karta + Naqd', default => ($sale->payment_type ?? '-')
        };
        $items = $sale->items ?? collect();
    @endphp
</head>
<body>
<div class="center" style="margin-bottom:6px; margin-top:4px;">
    <h3 style="margin:0;">Savdo Cheki</h3>
    <div style="font-size: 11px;">#{{ $sale->id }}</div>
    <div style="font-size: 11px;">{{ optional($sale->created_at)->format('Y-m-d H:i') }}</div>
    <div class="line"></div>
</div>

<div style="font-size:12px;">
    <div>Klient: <strong>{{ $sale->client?->full_name ?? '-' }}</strong></div>
    @if($sale->client?->phone)
        <div>Telefon: <strong>{{ $sale->client->phone }}</strong></div>
    @endif
    <div>To‘lov turi: <strong>{{ $paymentLabel }}</strong></div>
</div>
@if((float) $sale->paid_amount)
    <div class="item-row">
        <span>To‘langan:</span>
        <span>{{ number_format((float) $sale->paid_amount, 0, '.', ' ') }} so'm</span>
    </div>
@endif

@if((float) $sale->remaining_amount)
    <div class="item-row">
        <span>Qolgan:</span>
        <span>{{ number_format((float) $sale->remaining_amount, 0, '.', ' ') }} so'm</span>
    </div>
@endif

<div class="line"></div>

@foreach($items as $item)
    @php
        $name = $item->product?->name ?? 'Mahsulot';
        $qty  = (int) $item->quantity;
        $price = (float) $item->price;
        $total = $qty * $price;
    @endphp
    <div class="item-row">
        <span class="item-name">
            {{ $name }}<br>
            <span style="font-size:11px;">{{ $qty }} x {{ number_format($price, 0, '.', ' ') }}</span>
        </span>
        <span class="item-total bold">{{ number_format($total, 0, '.', ' ') }} so'm</span>
    </div>
@endforeach

<div class="line"></div>

<div class="item-row">
    <span>Jami mahsulotlar:</span>
    <span>{{ (int) $items->sum('quantity') }} dona</span>
    </div>
<div class="item-row bold">
    <span>JAMI SUMMA:</span>
    <span>{{ number_format((float) $sale->total_amount, 0, '.', ' ') }} so'm</span>
</div>


<div class="center" style="margin-top:12px; font-size:11px;">
    Xaridingiz uchun rahmat!<br>
    Yana tashrifingizni kutamiz
    </div>
</body>
</html>

