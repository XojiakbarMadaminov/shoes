@php
    $client = $sale->client;
    $items = $sale->items;
@endphp

<div class="space-y-4">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
        <div>
            <div class="text-gray-500 dark:text-gray-400">Klient</div>
            <div class="font-medium">{{ $client?->full_name ?? '-' }}</div>
            @if($client?->phone)
                <div class="text-gray-500 dark:text-gray-400">Telefon</div>
                <div>{{ $client->phone }}</div>
            @endif
        </div>
        <div>
            <div class="text-gray-500 dark:text-gray-400">To‘lov turi</div>
            <div class="font-medium">{{ $sale->payment_type ?? '-' }}</div>
            <div class="text-gray-500 dark:text-gray-400">Sana</div>
            <div>{{ $sale->created_at?->format('Y-m-d H:i') }}</div>
        </div>
    </div>

    <div class="border rounded-lg overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-3 py-2 text-left">Mahsulot</th>
                    <th class="px-3 py-2 text-center">Sklad</th>
                    <th class="px-3 py-2 text-center">Razmer</th>
                    <th class="px-3 py-2 text-right">Miqdor</th>
                    <th class="px-3 py-2 text-right">Narx</th>
                    <th class="px-3 py-2 text-right">Jami</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($items as $it)
                    <tr>
                        <td class="px-3 py-2">{{ $it->product?->name ?? '-' }}</td>
                        <td class="px-3 py-2 text-center">{{ optional(\App\Models\Stock::find($it->stock_id))->name }}</td>
                        <td class="px-3 py-2 text-center">{{ optional(\App\Models\ProductSize::find($it->product_size_id))->size }}</td>
                        <td class="px-3 py-2 text-right">{{ (int) $it->quantity }}</td>
                        <td class="px-3 py-2 text-right">{{ number_format($it->price, 2, '.', ' ') }}</td>
                        <td class="px-3 py-2 text-right">{{ number_format($it->total, 2, '.', ' ') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm">
        <div></div>
        <div class="text-right">
            <div class="text-gray-500 dark:text-gray-400">Jami summa</div>
            <div class="font-semibold">{{ number_format($sale->total_amount, 2, '.', ' ') }}</div>
        </div>
        <div class="text-right">
            <div class="text-gray-500 dark:text-gray-400">To‘langan</div>
            <div class="font-semibold">{{ number_format($sale->paid_amount, 2, '.', ' ') }}</div>
            <div class="text-gray-500 dark:text-gray-400 mt-1">Qolgan</div>
            <div class="font-semibold">{{ number_format($sale->remaining_amount, 2, '.', ' ') }}</div>
        </div>
    </div>
</div>

