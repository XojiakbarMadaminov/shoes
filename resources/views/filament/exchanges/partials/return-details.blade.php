@php
    /** @var \App\Models\InventoryAdjustment|null $adjustment */
    $product = $adjustment?->product;
    $productSize = $adjustment?->productSize;
    $handledBy = $adjustment?->handledBy;
    $quantity = (int) ($adjustment?->quantity ?? 0);
    $unitPrice = (int) ($adjustment?->unit_price ?? 0);
    $totalValue = $quantity * $unitPrice;
@endphp

<div class="space-y-4 text-sm">
    @if(!$adjustment)
        <p class="text-gray-500">Qaytarish topilmadi.</p>
    @else
        <div class="grid grid-cols-2 gap-4">
            <div>
                <div class="text-gray-500">ID</div>
                <div class="font-medium">{{ $adjustment->id }}</div>
            </div>
            <div>
                <div class="text-gray-500">Sana</div>
                <div class="font-medium">{{ optional($adjustment->created_at)->format('Y-m-d H:i') }}</div>
            </div>
            <div>
                <div class="text-gray-500">Qabul qilgan kassir</div>
                <div class="font-medium">{{ $handledBy?->name ?? '—' }}</div>
            </div>
            <div>
                <div class="text-gray-500">Izoh</div>
                <div class="font-medium">{{ $adjustment->reason ?? '—' }}</div>
            </div>
        </div>

        <div class="border rounded-lg p-4 space-y-2">
            <div class="text-sm font-semibold text-gray-600">Mahsulot</div>
            <div class="text-base font-medium">
                {{ $product?->display_label ?? '#'.$adjustment->product_id }}
                @if($productSize?->size)
                    <span class="text-sm text-gray-500 block">Razmer: {{ $productSize->size }}</span>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-3 gap-3">
            <div class="border rounded-lg p-3">
                <div class="text-gray-500">Miqdor</div>
                <div class="text-lg font-semibold">{{ number_format($quantity, 0, '.', ' ') }}</div>
            </div>
            <div class="border rounded-lg p-3">
                <div class="text-gray-500">Birlik narxi</div>
                <div class="text-lg font-semibold">{{ number_format($unitPrice, 0, '.', ' ') }} so‘m</div>
            </div>
            <div class="border rounded-lg p-3">
                <div class="text-gray-500">Jami</div>
                <div class="text-lg font-semibold">{{ number_format($totalValue, 0, '.', ' ') }} so‘m</div>
            </div>
        </div>
    @endif
</div>
