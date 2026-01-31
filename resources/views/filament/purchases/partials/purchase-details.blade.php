@php
    /** @var \App\Models\Purchase|null $purchase */
    $paymentLabel = match($purchase->payment_type) {
        'cash' => 'Naqd',
        'card' => 'Karta',
        'debt' => 'Qarz',
        'partial' => 'Qisman',
        default => ucfirst($purchase->payment_type ?? '-')
    };
@endphp

<div class="space-y-4">
    @if(!$purchase)
        <div class="text-sm text-gray-500">Xarid topilmadi.</div>
    @else
        <div class="flex items-center justify-between">
            <div class="text-base font-medium">Xarid #{{ $purchase->id }}</div>
            <a
                href="{{ \App\Filament\Pages\PurchaseEdit::getUrl(['record' => $purchase->id]) }}"
                class="inline-flex items-center gap-2 rounded-md bg-primary-600 px-3 py-1.5 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-600"
            >
                <x-filament::icon icon="heroicon-o-pencil-square" class="h-4 w-4" />
                Tahrirlash
            </a>
        </div>
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <div class="text-gray-500">Purchase ID</div>
                <div class="font-medium">{{ $purchase->id }}</div>
            </div>
            <div>
                <div class="text-gray-500">Sana</div>
                <div class="font-medium">{{ optional($purchase->purchase_date)->format('Y-m-d') }}</div>
            </div>
            <div>
                <div class="text-gray-500">Ta’minotchi</div>
                <div class="font-medium">
                    {{ $purchase->supplier?->full_name ?? '—' }}
                    @if($purchase->supplier?->phone)
                        <span class="text-gray-500"> ({{ $purchase->supplier->phone }})</span>
                    @endif
                </div>
            </div>
            <div>
                <div class="text-gray-500">Sklad</div>
                <div class="font-medium">{{ $purchase->stock?->name ?? '—' }}</div>
            </div>
            <div>
                <div class="text-gray-500">To‘lov turi</div>
                <div class="font-medium">{{ $paymentLabel }}</div>
            </div>
            <div>
                <div class="text-gray-500">Kassir</div>
                <div class="font-medium">{{ $purchase->createdBy?->name ?? '—' }}</div>
            </div>
            <div>
                <div class="text-gray-500">Jami summa</div>
                <div class="font-medium">{{ number_format($purchase->total_amount, 2) }}</div>
            </div>
            <div>
                <div class="text-gray-500">To‘langan</div>
                <div class="font-medium">{{ number_format($purchase->paid_amount, 2) }}</div>
            </div>
            <div>
                <div class="text-gray-500">Qolgan</div>
                <div class="font-medium">{{ number_format($purchase->remaining_amount, 2) }}</div>
            </div>
            @if($purchase->note)
                <div class="col-span-2">
                    <div class="text-gray-500">Izoh</div>
                    <div class="font-medium">{{ $purchase->note }}</div>
                </div>
            @endif
        </div>

        <div class="pt-4">
            <div class="text-sm text-gray-600 mb-2">Mahsulotlar</div>
            <div class="border rounded-md overflow-x-auto">
                <table class="min-w-[640px] w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="px-3 py-2 text-left">Mahsulot</th>
                            <th class="px-3 py-2 text-left">Razmer</th>
                            <th class="px-3 py-2 text-left">Sklad</th>
                            <th class="px-3 py-2 text-right">Miqdor</th>
                            <th class="px-3 py-2 text-right">Narx</th>
                            <th class="px-3 py-2 text-right">Jami</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($purchase->items as $item)
                        <tr class="border-t">
                            <td class="px-3 py-2">
                                {{ $item->product->name ?? ('#'.$item->product_id) }}
                            </td>
                            <td class="px-3 py-2">{{ $item->productSize?->size ?? '—' }}</td>
                            <td class="px-3 py-2">{{ $item->stock->name ?? '—' }}</td>
                            <td class="px-3 py-2 text-right">{{ $item->quantity }}</td>
                            <td class="px-3 py-2 text-right">{{ number_format($item->unit_cost, 2) }}</td>
                            <td class="px-3 py-2 text-right">{{ number_format($item->total_cost, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-3 py-4 text-center text-gray-500">Mahsulotlar mavjud emas</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
