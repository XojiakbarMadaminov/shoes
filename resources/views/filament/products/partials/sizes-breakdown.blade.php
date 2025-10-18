<div class="space-y-4">
    <div class="text-sm text-gray-600 dark:text-gray-300">
        Mahsulot: <strong>{{ $product->name }}</strong>
    </div>

    @php
        $sizeHeaders = $sizes ?? [];
    @endphp

    <div class="overflow-x-auto">
        <table class="min-w-full text-sm border border-gray-200 dark:border-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-3 py-2 text-left">Sklad</th>
                    @foreach($sizeHeaders as $sz)
                        <th class="px-3 py-2 text-center">{{ $sz }}</th>
                    @endforeach
                    <th class="px-3 py-2 text-right">Jami</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($rows as $row)
                    <tr>
                        <td class="px-3 py-2">{{ $row['stock'] }}</td>
                        @foreach($row['sizes'] as $qty)
                            <td class="px-3 py-2 text-center">{{ (int) $qty }}</td>
                        @endforeach
                        <td class="px-3 py-2 text-right font-medium">{{ (int) $row['total'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($sizeHeaders) + 2 }}" class="px-3 py-4 text-center text-gray-500">
                            Ma’lumot topilmadi
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

