<x-filament::page>
    <div class="space-y-6">
        {{-- Yuqoridagi filter qismi o'zgarishsiz qoladi --}}
        <div class="flex flex-col gap-4 md:flex-row md:items-end">
            {{-- Filter inputlari... --}}
            <label class="w-full">
                <span class="sr-only">Qidiruv</span>
                <input type="search" wire:model.live.debounce.400ms="search" placeholder="Mahsulot nomi yoki bar kodi..." class="w-full rounded-xl border border-gray-200 bg-white/80 px-4 py-2 text-sm shadow-sm outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
            </label>
            <label class="w-full md:w-72">
                <span class="sr-only">Kategoriya</span>
                <select wire:model.live="categoryId" class="w-full rounded-xl border border-gray-200 bg-white/80 px-4 py-2 text-sm shadow-sm outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                    <option value="">Barcha kategoriyalar</option>
                    @foreach ($filters as $id => $label)
                        <option value="{{ $id }}">{{ $label }}</option>
                    @endforeach
                </select>
            </label>
        </div>

        {{-- GRID QISMI --}}
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
            @forelse ($products as $product)
                <div class="group relative flex flex-col rounded-2xl border border-gray-200 bg-white p-4 shadow-sm transition hover:-translate-y-1 hover:border-primary-500 hover:shadow-lg dark:border-gray-700 dark:bg-gray-900">

                    {{-- Rasm Mantiqi --}}
                    @php
                        // Barcha rasmlarni olish ('images' kolleksiyasidan)
                        $mediaItems = $product->getMedia('images');
                        $mediaCount = $mediaItems->count();
                    @endphp

                    <div class="mb-4 h-48 w-full overflow-hidden rounded-xl bg-gray-100 dark:bg-gray-800">
                        @if ($mediaCount > 1)
                            {{-- KARUSEL (Agar rasm ko'p bo'lsa) --}}
                            <div
                                x-data="{
                                    activeSlide: 0,
                                    slidesCount: {{ $mediaCount }},
                                    next() { this.activeSlide = (this.activeSlide === this.slidesCount - 1) ? 0 : this.activeSlide + 1 },
                                    prev() { this.activeSlide = (this.activeSlide === 0) ? this.slidesCount - 1 : this.activeSlide - 1 }
                                }"
                                class="relative h-full w-full"
                            >
                                {{-- Rasmlar --}}
                                @foreach ($mediaItems as $index => $media)
                                    <img
                                        x-show="activeSlide === {{ $index }}"
                                        x-transition:enter="transition ease-out duration-300"
                                        x-transition:enter-start="opacity-0 scale-95"
                                        x-transition:enter-end="opacity-100 scale-100"
                                        src="{{ $media->getUrl('optimized') }}"
                                        alt="{{ $product->name }}"
                                        class="absolute inset-0 h-full w-full object-cover"
                                        style="display: none;" {{-- JS yuklanguncha yashirib turish --}}
                                    >
                                @endforeach

                                {{-- Oldingi tugmasi --}}
                                <button
                                    @click.prevent.stop="prev()"
                                    class="absolute left-2 top-1/2 -translate-y-1/2 rounded-full bg-white/80 p-1 text-gray-800 opacity-0 shadow hover:bg-white group-hover:opacity-100 transition focus:outline-none"
                                >
                                    <x-heroicon-m-chevron-left class="h-4 w-4" />
                                </button>

                                {{-- Keyingi tugmasi --}}
                                <button
                                    @click.prevent.stop="next()"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 rounded-full bg-white/80 p-1 text-gray-800 opacity-0 shadow hover:bg-white group-hover:opacity-100 transition focus:outline-none"
                                >
                                    <x-heroicon-m-chevron-right class="h-4 w-4" />
                                </button>

                                {{-- Kichik nuqtalar (pastda) --}}
                                <div class="absolute bottom-2 left-0 right-0 flex justify-center space-x-1">
                                    @foreach ($mediaItems as $index => $media)
                                        <div
                                            class="h-1.5 w-1.5 rounded-full transition-colors duration-200"
                                            :class="activeSlide === {{ $index }} ? 'bg-white' : 'bg-white/50'"
                                        ></div>
                                    @endforeach
                                </div>
                            </div>

                        @elseif ($mediaCount === 1)
                            {{-- 1 TA RASM --}}
                            <a href="{{ route('filament.admin.resources.products.view', $product) }}">
                                <img
                                    src="{{ $mediaItems[0]->getUrl('optimized') }}"
                                    alt="{{ $product->name }}"
                                    class="h-full w-full object-cover transition duration-300 group-hover:scale-105"
                                >
                            </a>
                        @else
                            {{-- RASM YO'Q --}}
                            <a href="{{ route('filament.admin.resources.products.view', $product) }}" class="flex h-full w-full items-center justify-center text-sm text-gray-500">
                                Rasm mavjud emas
                            </a>
                        @endif
                    </div>

                    {{-- Matn qismi (havola) --}}
                    <a href="{{ route('filament.admin.resources.products.view', $product) }}" class="flex flex-1 flex-col justify-between">
                        <div>
                            <div class="text-base font-semibold text-gray-900 group-hover:text-primary-600 dark:text-white dark:group-hover:text-primary-400 line-clamp-2">
                                {{ $product->name }}
                            </div>
                        </div>
                    </a>

                </div>
            @empty
                <div class="col-span-full rounded-2xl border border-dashed border-gray-300 bg-white p-8 text-center text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400">
                    Mahsulot topilmadi.
                </div>
            @endforelse
        </div>

        <div>
            {{ $products->links() }}
        </div>
    </div>
</x-filament::page>
