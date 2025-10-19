<x-filament::page class="bg-gray-100 dark:bg-gray-950">
    {{-- Auto-focus script --}}
    <script src="{{asset('js/pos.js')}}"></script>


    {{-- Receipt Modal --}}
    @if($showReceipt)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div
                class="bg-white text-black dark:bg-gray-800 rounded-lg p-6 max-w-md w-full mx-4 max-h-[90vh] overflow-y-auto"
                style="max-height:90vh;"
                wire:click.stop
            >
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Savat Cheki</h3>
                    <button wire:click="closeReceipt"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <x-heroicon-o-x-mark class="w-6 h-6"/>
                    </button>
                </div>

                <div id="receipt-content" class="receipt-content">
                    <div class="center" style="margin-bottom:10px; margin-top:5px;">
                        <h3>Traktor ehtiyot qismlari</h3>
                    </div>
                    <div class="center bold" style="font-size:18px; margin-bottom:6px;">SAVDO CHEKI</div>
                    <div style="text-align:center; margin-bottom:4px;">{{ $receiptData['receipt_number'] ?? '' }}</div>
                    <div style="text-align:center; margin-bottom:8px;">{{ $receiptData['date'] ?? '' }}</div>

                    <div>Savat: #{{ $receiptData['cart_id'] ?? '' }}</div>
                    <div style="margin-top:8px; font-size:12px;">
                        <div>ID: <strong>{{ $receiptData['meta']['sale_id'] ?? '—' }}</strong></div>
                        <div>Klient: <strong>{{ $receiptData['meta']['client_name'] ?? 'Tanlanmagan' }}</strong></div>
                        <div>To'lov turi: <strong>
                            {{ match($receiptData['meta']['payment_type'] ?? null) {
                                'cash' => 'Naqd',
                                'card' => 'Karta',
                                'debt' => 'Qarz',
                                'transfer' => 'O\'tkazma',
                                'partial' => 'Qisman',
                                'mixed' => 'Karta + Naqd',
                                default => 'Noma\'lum'
                            } }}
                        </strong></div>
                        @if(($receiptData['meta']['payment_type'] ?? null) === 'partial')
                            <div>To'langan summa: <strong>{{ number_format($receiptData['meta']['paid_amount'] ?? 0, 2, '.', ' ') }} so'm</strong></div>
                        @endif
                        @if(($receiptData['meta']['remaining_amount'] ?? 0) > 0)
                            <div>Qolgan qarz: <strong>{{ number_format($receiptData['meta']['remaining_amount'], 2, '.', ' ') }} so'm</strong></div>
                        @endif
                    </div>

                    <div class="line"></div>

                    @if(isset($receiptData['items']))
                        @foreach($receiptData['items'] as $item)
                            <div class="item-row">
                <span class="item-name">
                    {{ $item['name'] }}<br>
                    <span
                        style="font-size:11px;">{{ $item['qty'] }} x {{ number_format($item['price'], 0, '.', ' ') }}</span>
                </span>
                                <span class="item-total bold">{{ number_format($item['qty'] * $item['price'], 0, '.', ' ') }} so'm</span>
                            </div>
                        @endforeach
                    @endif

                    <div class="line"></div>
                    <div class="item-row">
                        <span>Jami mahsulotlar:</span>
                        <span>{{ $receiptData['totals']['qty'] ?? 0 }} dona</span>
                    </div>
                    <div class="item-row bold" style="font-size:15px;">
                        <span>JAMI SUMMA:</span>
                        <span>{{ number_format($receiptData['totals']['amount'] ?? 0, 0, '.', ' ') }} so'm</span>
                    </div>
                    <div class="center" style="margin-top:18px; font-size:12px;">
                        Xaridingiz uchun rahmat!<br>
                        Yana tashrifingizni kutamiz
                    </div>
                    <div class="receipt-footer-printonly">
                        <img src="{{ asset('images/traktor-qr.png') }}" alt="QR code" style="max-width:32mm; max-height:32mm;">
                    </div>
                </div>


                <div class="flex gap-3 mt-6">
                    <button wire:click="printReceipt"
                            class="flex-1 bg-blue-600 hover:bg-blue-700 text-blue py-2 px-4 rounded-lg font-medium">
                        <x-heroicon-o-printer class="w-5 h-5 inline mr-2"/>
                        Chop etish
                    </button>
                    <button wire:click="closeReceipt"
                            class="flex-1 bg-gray-600 hover:bg-gray-700 text-blue py-2 px-4 rounded-lg font-medium">
                        Yopish
                    </button>
                </div>

            </div>
        </div>
    @endif

    {{-- Cart Management Section --}}
    <x-filament::card class="mb-6" wire:key="cart-header-{{ $activeCartId }}-{{ $totals['qty'] }}">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-4">
            <h3 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-3 sm:mb-0">Faol savatlar</h3>
            <x-filament::button wire:click="createNewCart" size="md" color="success" icon="heroicon-o-plus-circle">
                Yangi savat
            </x-filament::button>
        </div>

        @if(count($activeCarts) > 0)
            <div class="flex flex-wrap gap-2 mb-4 pb-4 border-b border-gray-200 dark:border-gray-700">
                @foreach($activeCarts as $cartId => $cartTotals)
                    <div wire:key="cart-{{ $cartId }}" class="relative group">
                        <x-filament::button
                            wire:click="switchCart({{ $cartId }})"
                            size="sm"
                            :color="$activeCartId === $cartId ? 'primary' : 'gray'"
                            :outlined="$activeCartId !== $cartId"
                            class="relative {{ count($activeCarts) > 1 ? 'pr-10' : '' }}"
                            tag="button"
                        >
                            Savat #{{ $cartId }}
                            @if($cartTotals['qty'] > 0)
                                <span
                                    class="ml-1.5 bg-danger-500 text-white text-xs rounded-full px-1.5 py-0.5 font-medium">
                                    {{ $cartTotals['qty'] }}
                                </span>
                            @endif
                        </x-filament::button>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="text-sm text-gray-600 dark:text-gray-400">
            Joriy savat: <strong class="text-gray-900 dark:text-white">#{{ $activeCartId }}</strong>
            @if(isset($totals['qty']) && $totals['qty'] > 0)
                <span class="mx-1 text-gray-400 dark:text-gray-600">|</span>
                {{ $totals['qty'] }} mahsulot,
                <span class="font-semibold text-gray-800 dark:text-gray-200">{{ number_format($totals['amount'], 0, '.', ' ') }} so'm</span>
            @else
                <span class="mx-1 text-gray-400 dark:text-gray-600">|</span> Savat bo'sh
            @endif
        </div>
    </x-filament::card>

    {{-- Main content --}}
    <div class="space-y-12 lg:grid lg:grid-cols-1 lg:gap-8 lg:space-y-0">
        <div>
            {{-- Search input --}}
            <x-filament::input.wrapper class="mb-4">
                <x-slot name="prefix">
                    <x-heroicon-o-magnifying-glass class="w-5 h-5 text-gray-400 dark:text-gray-500"/>
                </x-slot>
                <x-filament::input
                    name="search"
                    x-data="{
                        focusInput() {
                            this.$refs.searchInput.focus();
                        }
                    }"
                    x-ref="searchInput"
                    x-init="
                        $nextTick(() => focusInput());
                        document.addEventListener('visibilitychange', () => {
                            if (!document.hidden) {
                                setTimeout(() => focusInput(), 100);
                            }
                        });
                    "
                    x-on:keydown.enter="$wire.addByBarcode($event.target.value); $event.target.value=''; $nextTick(() => focusInput())"
                    wire:model.live="search"
                    placeholder="Skanerlash yoki qo'lda kiriting..."
                    autofocus
                />
            </x-filament::input.wrapper>

            {{-- Search results --}}
            @if($products->isNotEmpty())
                <table class="w-full mt-4 text-sm">
                    <thead class="bg-gray-100 dark:bg-gray-800">
                    <tr>
                        <th class="px-2 py-1 text-left">Rasm</th>
                        <th class="px-2 py-1 text-left">Nomi</th>
                        <th class="px-2 py-1 text-right">Narxi</th>
                        <th class="px-2 py-1"></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($products as $p)
                        <tr wire:key="item-{{ $p->id }}"
                             class="bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-900 dark:text-gray-200">
                            <td class="px-2 py-1">
                                @php
                                    $thumb = $p->getFirstMediaUrl('images', 'optimized') ?: $p->getFirstMediaUrl('images');
                                    $urls = $p->getMedia('images')->map(function ($m) { return $m->getUrl('optimized') ?: $m->getUrl(); })->values()->all();
                                @endphp
                                @if($thumb)
                                    <img src="{{ $thumb }}"
                                         alt="{{ $p->name }}"
                                         class="w-12 h-12 object-cover rounded cursor-zoom-in"
                                         x-on:click="$dispatch('open-img-zoom', { urls: @js($urls) })"
                                    />
                                @else
                                    <div class="w-12 h-12 bg-gray-200 dark:bg-gray-700 rounded"></div>
                                @endif
                            </td>
                            <td class="px-2 py-1">{{ $p->name }}</td>
                            <td class="px-2 py-1 text-right">{{ number_format($p->price, 2, '.', ' ') }}</td>
                            <td class="px-2 py-1">
                                <x-filament::button wire:click="add({{ $p->id }})" size="sm">
                                    Qo'shish
                                </x-filament::button>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        {{-- Current Cart --}}
        <x-filament::card class="lg:sticky lg:top-6 h-fit">
            <div class="flex justify-between items-center mb-4 pb-3 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-200">Savat #{{ $activeCartId }}</h2>
                @if(isset($totals['qty']) && $totals['qty'] > 0)
                    <span
                        class="text-sm text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded-md">{{ $totals['qty'] }} mahsulot</span>
                @endif
            </div>

            @if(empty($cart))
                <div class="text-center py-10 text-gray-500 dark:text-gray-400">
                    <x-heroicon-o-shopping-cart class="w-16 h-16 mx-auto mb-3 text-gray-400 dark:text-gray-500"/>
                    <p>Savatda hozircha mahsulot yo'q.</p>
                    <p class="text-xs mt-1">Qidiruv maydonidan mahsulot qo'shing.</p>
                </div>
            @else
                <div class="flow-root overflow-x-auto">
                    <table class="min-w-[980px] w-full text-sm divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th scope="col"
                                class="w-full px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Nomi
                            </th>
                            <th scope="col"
                                class="px-3 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Sklad
                            </th>
                            <th scope="col"
                                class="px-3 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Razmerlar va miqdori
                            </th>
                            <th scope="col"
                                class="px-3 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Kelgan narxi
                            </th>
                            <th scope="col"
                                class="px-3 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Sotish narxi
                            </th>
                            <th scope="col"
                                class="px-3 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Jami
                            </th>
                            <th scope="col"
                                class="px-3 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Amal
                            </th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($cart as $index => $row)
                            <tr>
                                <td class="w-full px-3 py-3 font-medium text-gray-900 dark:text-gray-100 whitespace-normal break-words">
                                    {{ $row['name'] }}
                                </td>

                                {{-- ✅ Har bir mahsulot uchun sklad select --}}
                                <td class="px-3 py-3 text-center">
                                    <select
                                        class="min-w-[220px] w-auto px-3 py-1.5
                                           border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white
                                           focus:ring-primary-500 focus:border-primary-500 rounded-md shadow-sm text-sm"
                                        x-on:change="$wire.updateStock({{ $row['id'] }}, $event.target.value);"
                                    >
                                        @foreach(App\Models\Stock::scopes(['active'])->get() as $stock)
                                            <option value="{{ $stock->id }}"
                                                @selected(($row['stock_id'] ?? null) == $stock->id)>
                                                {{ $stock->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>

                                {{-- Razmerlar va miqdor --}}
                                <td class="px-3 py-3 text-center">
                                    @if($this->isPackageProduct((int) $row['id']))
                                        @php
                                            $currentStockId = (int) ($row['stock_id'] ?? 0);
                                            $availablePkg = $this->getPackageAvailable((int) $row['id'], $currentStockId);
                                        @endphp
                                        <div x-data="{ maxQty: {{ (int) $availablePkg }} }">
                                            <input type="number"
                                                   min="0"
                                                   max="{{ (int) $availablePkg }}"
                                                   value="{{ (int) ($row['qty'] ?? 0) }}"
                                                   class="w-24 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500 rounded-md shadow-sm text-right py-1.5 px-2 text-sm"
                                                   x-on:change="
                                                        let v = parseInt($event.target.value || 0);
                                                        if (isNaN(v) || v < 0) v = 0;
                                                        if (v > maxQty) v = maxQty;
                                                        $event.target.value = v;
                                                        $wire.updatePackageQty({{ (int) $row['id'] }}, v);
                                                   "
                                            />
                                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                Mavjud: {{ (int) $availablePkg }} dona
                                            </div>
                                        </div>
                                    @endif
                                    @unless($this->isPackageProduct((int) $row['id']))
                                    <x-filament::button
                                        tag="button"
                                        size="xs"
                                        :color="isset($row['sizes']) && count(array_filter($row['sizes'])) > 0 ? 'success' : 'danger'"
                                        class="whitespace-nowrap"
                                        x-on:click.prevent="
                                            $wire.getProductSizes({{ (int) $row['id'] }}).then(data => {
                                                window.dispatchEvent(new CustomEvent('open-size-modal', { detail: data }));
                                            });
                                        "
                                    >
                                        ⚙️ Razmerlar
                                    </x-filament::button>
                                    @endunless


                                </td>

                                {{-- Kelgan narxi --}}
                                <td class="px-3 py-3 text-right">
                                    <div>
                                        <input type="number"
                                               disabled
                                               value="{{ $row['initial_price'] }}"
                                               class="w-24 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white
                                                  focus:ring-primary-500 focus:border-primary-500 rounded-md shadow-sm text-right
                                                  py-1.5 px-2 text-sm">
                                    </div>
                                </td>

                                {{-- Narx --}}
                                <td class="px-3 py-3 text-right">
                                    <div
                                        wire:key="price-input-{{ $activeCartId }}-{{ $row['id'] }}"
                                        x-data="{
                                            oldValue: {{ $row['price'] }},
                                            updatePrice(event) {
                                                const newPrice = parseFloat(event.target.value);
                                                $wire.updatePrice({{ $row['id'] }}, newPrice)
                                                    .then(result => {
                                                        if (result === false) {
                                                            // ❌ Xato bo‘lsa, eski qiymatni qaytarish
                                                            event.target.value = this.oldValue;
                                                        } else {
                                                            // ✅ To‘g‘ri bo‘lsa, yangi qiymatni saqlash
                                                            this.oldValue = newPrice;
                                                        }
                                                    });
                                            }
                                        }"
                                    >
                                        <input type="number"
                                               min="1"
                                               value="{{ $row['price'] }}"
                                               @change="updatePrice($event)"
                                               class="w-24 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white
                                                  focus:ring-primary-500 focus:border-primary-500 rounded-md shadow-sm text-right
                                                  py-1.5 px-2 text-sm">
                                    </div>
                                </td>


                                <td class="px-3 py-3 text-right font-semibold text-gray-800 dark:text-gray-200 whitespace-nowrap">
                                    {{ number_format($row['qty'] * $row['price'], 0,'.',' ') }}
                                </td>

                                <td class="px-3 py-3 text-center">
                                    <button wire:click="remove({{ $row['id'] }})"
                                            class="text-danger-600 hover:text-danger-800 dark:text-danger-500 dark:hover:text-danger-400
                                                p-1.5 rounded-md hover:bg-danger-50 dark:hover:bg-danger-900/50"
                                            title="O'chirish">
                                        <x-heroicon-o-trash class="w-5 h-5"/>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>

                </div>

                {{-- === Totallar va tugmalar === --}}
                <div class="border-t border-gray-200 dark:border-gray-700 mt-4 pt-4 space-y-2">

                    {{-- Totallar --}}
                    <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400">
                        <span>Mahsulotlar soni:</span>
                        <span class="font-medium text-gray-800 dark:text-gray-200">{{ $totals['qty'] }} dona</span>
                    </div>
                    <div class="flex justify-between text-lg font-semibold text-gray-900 dark:text-white">
                        <span>Jami summa:</span>
                        <span>{{ number_format($totals['amount'], 0, '.', ' ') }} so'm</span>
                    </div>
                    @if($selectedClientId)
                        <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                            Klient: <span class="font-medium text-gray-900 dark:text-gray-200">{{ collect($clients)->firstWhere('id', $selectedClientId)?->full_name ?? \App\Models\Client::find($selectedClientId)?->full_name }}</span>
                            — To'lov: <span class="font-medium text-gray-900 dark:text-gray-200">
                                {{ match($paymentType) {
                                    'cash' => 'Naqd',
                                    'card' => 'Karta',
                                    'debt' => 'Qarz',
                                    'partial' => 'Qisman',
                                    'mixed' => 'Karta + Naqd',
                                    'transfer' => "O'tkazma",
                                    default => 'Tanlanmagan'
                                } }}
                            </span>
                            @if($paymentType === 'partial' && filled($partialPaymentAmount))
                                — To'langan: <span class="font-medium text-gray-900 dark:text-gray-200">{{ number_format($partialPaymentAmount, 2, '.', ' ') }} so'm</span>
                            @endif
                        </div>
                    @endif

                    {{-- Tugmalar satri --}}
                    <div class="mt-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        {{-- Yopish --}}
                        <x-filament::button
                            wire:click="closeCart({{ $activeCartId }})"
                            size="sm"
                            color="danger"
                            class="sm:w-auto w-full order-2 sm:order-1"
                            wire:confirm="Savat #{{ $activeCartId }} ni yopishni tasdiqlaysizmi?"
                        >
                            Yopish
                        </x-filament::button>

                        {{-- 🧍 Klient tanlash --}}
                        <x-filament::button
                            color="warning"
                            size="sm"
                            class="sm:w-auto w-full order-1 sm:order-2"
                            wire:click="openClientPanel"
                        >
                            🧍 Klient tanlash
                        </x-filament::button>

                        {{-- Checkout --}}
                        <x-filament::button
                            wire:click="checkout"
                            color="success"
                            size="lg"
                            icon="heroicon-o-check-circle"
                            class="sm:w-auto w-full order-1 sm:order-3"
                        >
                            Savat #{{ $activeCartId }} ni yakunlash
                        </x-filament::button>
                    </div>

                </div>
            @endif
        </x-filament::card>
    </div>

    {{-- 🔹 Razmer tanlash modal --}}
    <div
        x-data="{ open: false, product: null, sizes: [], quantities: {} }"
        x-on:open-size-modal.window="
        open = true;
        product = $event.detail.product;
        sizes = $event.detail.sizes;
        quantities = $event.detail.quantities ?? {};
    "
        x-show="open"
        class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50"
        x-transition
    >
        <div
            class="bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6 w-full max-w-md mx-4 relative"
            @click.outside="open = false"
            x-transition
        >
            <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">
                <span x-text="product?.name ?? 'Mahsulot'"></span> — Razmerlar
            </h2>

            <template x-if="sizes.length > 0">
                <div class="space-y-3 max-h-64 overflow-y-auto pr-2">
                    <template x-for="(size, index) in sizes" :key="index">
                        <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 pb-2">
                            <span class="text-sm text-gray-700 dark:text-gray-300" x-text="size.name"></span>
                            <input
                                type="number"
                                min="0"
                                class="w-24 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm text-right py-1.5 px-2 text-sm focus:ring-primary-500 focus:border-primary-500"
                                x-model.number="quantities[size.id]"
                            >
                        </div>
                    </template>
                </div>
            </template>

            <template x-if="sizes.length === 0">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Bu mahsulot uchun razmerlar mavjud emas
                </p>
            </template>

            <div class="flex justify-end gap-2 mt-6">
                <button
                    @click="open = false"
                    class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded-md text-sm"
                >
                    Yopish
                </button>
                <button
                    wire:key="qty-size-input-{{ $activeCartId }}-product.id"
                    @click="$wire.updateSizes(product.id, quantities); open = false;"
                    class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-md text-sm font-semibold"
                >
                    Saqlash
                </button>
            </div>
        </div>
    </div>

    {{-- ðŸ§'â€�ðŸ'¼ Klient tanlash Slide-over Panel --}}
    @if($showClientPanel)
        <div class="fixed inset-0 bg-black bg-opacity-30 z-50 flex justify-end" wire:click="$set('showClientPanel', false)">
            <div
                class="w-full sm:w-1/3 bg-white dark:bg-gray-900 shadow-2xl h-full overflow-y-auto"
                wire:click.stop
                x-data
                x-init="$nextTick(() => $refs.clientSearch?.focus())"
            >
                {{-- Header --}}
                <div class="sticky top-0 bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700 p-4 z-10">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">
                            Klient tanlash
                        </h2>
                        <button
                            wire:click="$set('showClientPanel', false)"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                        >
                            <x-heroicon-o-x-mark class="w-6 h-6"/>
                        </button>
                    </div>

                    {{-- Qidiruv --}}
                    <x-filament::input.wrapper>
                        <x-slot name="prefix">
                            <x-heroicon-o-magnifying-glass class="w-5 h-5 text-gray-400"/>
                        </x-slot>
                        <x-filament::input
                            x-ref="clientSearch"
                            wire:model.live.debounce.300ms="searchClient"
                            placeholder="Ism yoki telefon raqam..."
                        />
                    </x-filament::input.wrapper>

                    {{-- Yangi klient qo'shish tugmasi --}}
                    <x-filament::button
                        wire:click="toggleCreateClientForm"
                        size="sm"
                        color="success"
                        icon="heroicon-o-plus"
                        class="w-full mt-3"
                    >
                        Yangi klient qo'shish
                    </x-filament::button>
                </div>

                {{-- Content --}}
                <div class="p-4 space-y-4">
                    {{-- Yangi klient yaratish formasi --}}
                    @if($showCreateClientForm)
                        <x-filament::card>
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">
                                Yangi klient yaratish
                            </h3>

                            <div class="space-y-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        To'liq ism *
                                    </label>
                                    <x-filament::input
                                        wire:model="newClient.full_name"
                                        placeholder="Masalan: Aliyev Ali"
                                        required
                                    />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Telefon raqam
                                    </label>
                                    <x-filament::input
                                        wire:model="newClient.phone"
                                        placeholder="+998 XX XXX XX XX"
                                    />
                                </div>

                                <div class="flex gap-2 mt-4">
                                    <x-filament::button
                                        wire:click="createClient"
                                        color="success"
                                        class="flex-1"
                                    >
                                        Saqlash
                                    </x-filament::button>
                                    <x-filament::button
                                        wire:click="toggleCreateClientForm"
                                        color="gray"
                                        class="flex-1"
                                    >
                                        Bekor qilish
                                    </x-filament::button>
                                </div>
                            </div>
                        </x-filament::card>
                    @endif

                    {{-- Klientlar ro'yxati --}}
                    @if(!empty($clients) && count($clients) > 0)
                        <div class="space-y-2">
                            <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400">
                                Klientlar ({{ count($clients) }})
                            </h3>

                            @foreach($clients as $client)
                                <div
                                    wire:click="selectClient({{ $client->id }})"
                                    class="p-3 rounded-lg border cursor-pointer transition-all
                                        {{ $selectedClientId === $client->id
                                            ? 'bg-primary-50 border-primary-500 dark:bg-primary-900/20 dark:border-primary-600'
                                            : 'bg-white border-gray-200 hover:border-primary-300 dark:bg-gray-800 dark:border-gray-700 dark:hover:border-primary-700'
                                        }}"
                                >
                                    <div class="flex items-center justify-between">
                                        <div class="flex-1">
                                            <p class="font-medium text-gray-900 dark:text-gray-100">
                                                {{ $client->full_name }}
                                            </p>
                                            @if($client->phone)
                                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                                    {{ $client->phone }}
                                                </p>
                                            @endif
                                        </div>
                                        @if($selectedClientId === $client->id)
                                            <x-heroicon-o-check-circle class="w-6 h-6 text-primary-600 dark:text-primary-500"/>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8">
                            <x-heroicon-o-user-group class="w-16 h-16 mx-auto mb-3 text-gray-400"/>
                            <p class="text-gray-500 dark:text-gray-400">
                                Klientlar topilmadi
                            </p>
                        </div>
                    @endif

                    {{-- To'lov usullari --}}
                    @if($selectedClientId)
                        <x-filament::card class="mt-6">
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">
                                To'lov usulini tanlang
                            </h3>

                            <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                                {{-- Naqd --}}
                                <button
                                    wire:click="selectPaymentType('cash')"
                                    class="p-4 rounded-lg border-2 transition-all text-center
                                        {{ $paymentType === 'cash'
                                            ? 'border-success-500 bg-success-50 dark:bg-success-900/20'
                                            : 'border-gray-300 hover:border-success-400 dark:border-gray-600'
                                        }}"
                                >
                                    <div class="text-3xl mb-2">💵</div>
                                    <div class="font-medium text-gray-900 dark:text-gray-100">Naqd</div>
                                </button>

                                {{-- Karta --}}
                                <button
                                    wire:click="selectPaymentType('card')"
                                    class="p-4 rounded-lg border-2 transition-all text-center
                                        {{ $paymentType === 'card'
                                            ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20'
                                            : 'border-gray-300 hover:border-primary-400 dark:border-gray-600'
                                        }}"
                                >
                                    <div class="text-3xl mb-2">💳</div>
                                    <div class="font-medium text-gray-900 dark:text-gray-100">Karta</div>
                                </button>

                                {{-- Qarz --}}
                                <button
                                    wire:click="selectPaymentType('debt')"
                                    class="p-4 rounded-lg border-2 transition-all text-center
                                        {{ $paymentType === 'debt'
                                            ? 'border-warning-500 bg-warning-50 dark:bg-warning-900/20'
                                            : 'border-gray-300 hover:border-warning-400 dark:border-gray-600'
                                        }}"
                                >
                                    <div class="text-3xl mb-2">📋</div>
                                    <div class="font-medium text-gray-900 dark:text-gray-100">Qarz</div>
                                </button>

                                {{-- Qisman --}}
                                <button
                                    wire:click="selectPaymentType('partial')"
                                    class="p-4 rounded-lg border-2 transition-all text-center
                                        {{ $paymentType === 'partial'
                                            ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20'
                                            : 'border-gray-300 hover:border-purple-400 dark:border-gray-600'
                                        }}"
                                >
                                    <div class="text-3xl mb-2">🔀</div>
                                    <div class="font-medium text-gray-900 dark:text-gray-100">Qisman</div>
                                </button>

                                {{-- Naqd + Karta --}}
                                <button
                                    wire:click="selectPaymentType('mixed')"
                                    class="p-4 rounded-lg border-2 transition-all text-center
                                        {{ $paymentType === 'mixed'
                                            ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20'
                                            : 'border-gray-300 hover:border-purple-400 dark:border-gray-600'
                                        }}"
                                >
                                    <div class="text-3xl mb-2">💳+💵</div>
                                    <div class="font-medium text-gray-900 dark:text-gray-100">Karta + Naqd</div>
                                </button>
                            </div>

                            @if($paymentType === 'partial')
                                <div class="mt-4">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Qisman to'lov summasi
                                    </label>
                                    <x-filament::input
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        wire:model.live.debounce.300ms="partialPaymentAmount"
                                        placeholder="Masalan: 150000"
                                    />
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        Qolgan summa qarz sifatida saqlanadi.
                                    </p>
                                </div>
                            @endif

                            @if(in_array($paymentType, ['debt','partial']))
                                <div class="mt-4">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Izoh (majburiy emas)
                                    </label>
                                    <textarea
                                        wire:model.live.debounce.300ms="paymentNote"
                                        rows="2"
                                        class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm py-1.5 px-2 text-sm focus:ring-primary-500 focus:border-primary-500"
                                        placeholder="Masalan: Qarzga olindi yoki qisman to'lov tafsiloti"
                                    ></textarea>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        Bu izoh qarzdor tarixida saqlanadi.
                                    </p>
                                </div>
                            @endif

                            @if($paymentType === 'mixed')
                                <div class="mt-4 grid gap-3 md:grid-cols-2">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Kartada to'lanadi
                                        </label>
                                        <x-filament::input
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            wire:model.live.debounce.300ms="mixedPayment.card"
                                            placeholder="Masalan: 200000"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Naqdda to'lanadi
                                        </label>
                                        <x-filament::input
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            wire:model.live.debounce.300ms="mixedPayment.cash"
                                            placeholder="Masalan: 150000"
                                        />
                                    </div>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                    Karta summasi kiritilganda qolgan qismi avtomatik naqdga taqsimlanadi.
                                </p>
                            @endif

                            {{-- Tanlangan ma'lumotlar --}}
                            <div class="mt-4 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-600 dark:text-gray-400">Klient:</span>
                                    <span class="font-medium text-gray-900 dark:text-gray-100">
                                        {{ collect($clients)->firstWhere('id', $selectedClientId)?->full_name ?? 'Tanlanmagan' }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between text-sm mt-2">
                                    <span class="text-gray-600 dark:text-gray-400">To'lov:</span>
                                    <span class="font-medium text-gray-900 dark:text-gray-100">
                                        {{ match($paymentType) {
                                            'cash' => '💵 Naqd',
                                            'card' => '💳 Karta',
                                            'debt' => '📋 Qarz',
                                            'partial' => '🔀 Qisman',
                                            'mixed' => '💳+💵 Karta + Naqd',
                                            default => 'Tanlanmagan'
                                        } }}
                                    </span>
                                </div>
                                @if($paymentType === 'partial' && filled($partialPaymentAmount))
                                    <div class="flex items-center justify-between text-sm mt-2">
                                        <span class="text-gray-600 dark:text-gray-400">To'langan summa:</span>
                                        <span class="font-medium text-gray-900 dark:text-gray-100">
                                            {{ number_format($partialPaymentAmount, 2, '.', ' ') }} so'm
                                        </span>
                                    </div>
                                @endif

                                @if($paymentType === 'mixed')
                                    @php
                                        $mixedCash = $mixedPayment['cash'] ?? null;
                                        $mixedCard = $mixedPayment['card'] ?? null;
                                    @endphp
                                    @if(filled($mixedCard) || filled($mixedCash))
                                        <div class="flex flex-col gap-1 mt-2 text-sm">
                                            @if(filled($mixedCard))
                                                <div class="flex items-center justify-between">
                                                    <span class="text-gray-600 dark:text-gray-400">Karta:</span>
                                                    <span class="font-medium text-gray-900 dark:text-gray-100">
                                                        {{ number_format($mixedCard, 2, '.', ' ') }} so'm
                                                    </span>
                                                </div>
                                            @endif
                                            @if(filled($mixedCash))
                                                <div class="flex items-center justify-between">
                                                    <span class="text-gray-600 dark:text-gray-400">Naqd:</span>
                                                    <span class="font-medium text-gray-900 dark:text-gray-100">
                                                        {{ number_format($mixedCash, 2, '.', ' ') }} so'm
                                                    </span>
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                @endif
                            </div>
                        </x-filament::card>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Global image zoom overlay (must be inside root) --}}
    <div x-data="{ open: false, i: 0, urls: [] }"
     x-on:open-img-zoom.window="urls = $event.detail.urls || []; i = 0; open = true"
     x-show="open"
     x-transition
     class="fixed inset-0 bg-black/70 flex items-center justify-center z-[100]"
     style="display: none;"
>
    <div class="relative max-w-5xl w-full mx-4" @click.outside="open = false">
        <button class="absolute -top-10 right-0 text-white" @click="open = false">✕</button>
        <template x-if="urls.length > 0">
            <div class="relative">
                <img :src="urls[i]" class="max-h-[80vh] w-auto mx-auto rounded" alt="zoom" />
                <button type="button" @click="i = (i - 1 + urls.length) % urls.length"
                        class="absolute left-2 top-1/2 -translate-y-1/2 bg-black/50 text-white rounded-full w-9 h-9 flex items-center justify-center hover:bg-black/70">‹</button>
                <button type="button" @click="i = (i + 1) % urls.length"
                        class="absolute right-2 top-1/2 -translate-y-1/2 bg-black/50 text-white rounded-full w-9 h-9 flex items-center justify-center hover:bg-black/70">›</button>
                <div class="mt-2 text-center text-xs text-gray-200" x-text="(i+1) + ' / ' + urls.length"></div>
            </div>
        </template>
        <template x-if="urls.length === 0">
            <div class="text-center text-gray-300 py-8">Rasm mavjud emas</div>
        </template>
    </div>
    </div>

</x-filament::page>
