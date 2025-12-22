@php
    /** @var \App\Models\ExchangeOperation|null $operation */
    $difference = $operation?->price_difference ?? 0;
    $differenceLabel = match (true) {
        $difference > 0 => 'Mijozdan qabul qilindi',
        $difference < 0 => 'Mijozga qaytarildi',
        default => 'Narx farqi yo‘q',
    };
    $differenceBadge = match (true) {
        $difference > 0 => 'bg-green-100 text-green-800 dark:bg-green-500/10 dark:text-green-300',
        $difference < 0 => 'bg-red-100 text-red-800 dark:bg-red-500/10 dark:text-red-300',
        default => 'bg-gray-100 text-gray-700 dark:bg-gray-500/10 dark:text-gray-300',
    };
    $handledBy = $operation?->handledBy?->name;
    $inSize = $operation?->inProductSize?->size;
    $outSize = $operation?->outProductSize?->size;
@endphp

<div class="space-y-5">
    @if(!$operation)
        <div class="text-sm text-gray-500">Almashinuv topilmadi.</div>
    @else
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <div class="text-gray-500">ID</div>
                <div class="font-medium">{{ $operation->id }}</div>
            </div>
            <div>
                <div class="text-gray-500">Sana</div>
                <div class="font-medium">{{ optional($operation->created_at)->format('Y-m-d H:i') }}</div>
            </div>
            <div>
                <div class="text-gray-500">Narx farqi</div>
                <div class="font-medium space-x-2">
                    <span>{{ number_format($difference, 0, '.', ' ') }} so‘m</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold {{ $differenceBadge }}">
                        {{ $differenceLabel }}
                    </span>
                </div>
            </div>
            <div>
                <div class="text-gray-500">Kassir</div>
                <div class="font-medium">{{ $handledBy ?? '—' }}</div>
            </div>
        </div>

        <div class="grid md:grid-cols-2 gap-4">
            <div class="border rounded-lg p-4 space-y-2">
                <div class="text-sm font-semibold text-gray-600">Qaytarilgan tovar</div>
                <div class="text-base font-medium">
                    {{ $operation->inProduct?->display_label ?? '—' }}
                    @if($inSize)
                        <span class="text-sm text-gray-500 block">Razmer: {{ $inSize }}</span>
                    @endif
                </div>
            </div>
            <div class="border rounded-lg p-4 space-y-2">
                <div class="text-sm font-semibold text-gray-600">Berilgan tovar</div>
                <div class="text-base font-medium">
                    {{ $operation->outProduct?->display_label ?? '—' }}
                    @if($outSize)
                        <span class="text-sm text-gray-500 block">Razmer: {{ $outSize }}</span>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
