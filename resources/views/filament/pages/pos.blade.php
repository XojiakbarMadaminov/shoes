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
                        <th class="px-2 py-1 text-left">Barcode</th>
                        <th class="px-2 py-1 text-left">Nomi</th>
                        <th class="px-2 py-1 text-right">Narxi</th>
                        <th class="px-2 py-1"></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($products as $p)
                        <tr wire:key="item-{{ $p->id }}"
                            class="bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-900 dark:text-gray-200">
                            <td class="px-2 py-1">{{ $p->barcode }}</td>
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
                <div class="flow-root">
                    <table class="w-full text-sm divide-y divide-gray-200 dark:divide-gray-700">
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
                                Miqdori
                            </th>
                            <th scope="col"
                                class="px-3 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Yuan narxi
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
                                                @selected($stock->is_main)>
                                                {{ $stock->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>

                                {{-- Miqdor --}}
                                <td class="px-3 py-3 text-center">
                                    <div
                                        wire:key="qty-input-{{ $activeCartId }}-{{ $row['id'] }}"
                                        x-data="{
                                            oldValue: {{ $row['qty'] }},
                                            updateQty(event) {
                                                const newQty = parseInt(event.target.value);
                                                $wire.updateQty({{ $row['id'] }}, newQty)
                                                    .then(result => {
                                                        if (result === false) {
                                                            // ❌ xato bo‘lsa, eski qiymatni qaytarish
                                                            event.target.value = this.oldValue;
                                                        } else {
                                                            // ✅ to‘g‘ri bo‘lsa, yangilash
                                                            this.oldValue = newQty;
                                                        }
                                                    });
                                            }
                                        }"
                                    >
                                        <input type="number" min="1"
                                               value="{{ $row['qty'] }}"
                                               @change="updateQty($event)"
                                               class="w-20 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white
                                                  focus:ring-primary-500 focus:border-primary-500 rounded-md shadow-sm text-center
                                                  py-1.5 px-2 text-sm">
                                    </div>
                                </td>

                                {{-- Yuan narxi --}}
                                <td class="px-3 py-3 text-right">
                                    <div>
                                        <input type="number"
                                               disabled
                                               value="{{ $row['yuan_price'] }}"
                                               class="w-24 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white
                                                  focus:ring-primary-500 focus:border-primary-500 rounded-md shadow-sm text-right
                                                  py-1.5 px-2 text-sm">
                                    </div>
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

                    {{-- Tugmalar satri --}}
                    <div class="mt-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">

                        {{-- Chapda — yopish (kichik, qizil) --}}
                        <x-filament::button
                            wire:click="closeCart({{ $activeCartId }})"
                            size="sm"
                            color="danger"
                            class="sm:w-auto w-full order-2 sm:order-1"
                            wire:confirm="Savat #{{ $activeCartId }} ni yopishni tasdiqlaysizmi?"
                        >
                            Yopish
                        </x-filament::button>

                        {{-- O‘ngda — to‘lovni yakunlash (katta, yashil) --}}
                        <x-filament::button
                            wire:click="checkout"
                            color="success"
                            size="lg"
                            icon="heroicon-o-check-circle"
                            class="sm:w-auto w-full order-1 sm:order-2"
                        >
                            Savat #{{ $activeCartId }} ni yakunlash
                        </x-filament::button>
                    </div>
                </div>
            @endif
        </x-filament::card>
    </div>
</x-filament::page>
