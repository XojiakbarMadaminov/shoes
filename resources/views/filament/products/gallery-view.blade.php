<x-filament::page>
    <div class="space-y-6">
        {{-- Filtrlar --}}
        <div class="flex flex-col gap-4 md:flex-row md:items-end">
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

        {{-- RESPONSIVE GRID --}}
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:gap-4 lg:grid-cols-5 xl:grid-cols-6">
            @forelse ($products as $product)
                <div class="group relative flex flex-col rounded-2xl border border-gray-200 bg-white p-3 shadow-sm transition hover:-translate-y-1 hover:border-primary-500 hover:shadow-lg dark:border-gray-700 dark:bg-gray-900 md:p-4">

                    @php
                        $mediaItems = $product->getMedia('images');
                        $mediaCount = $mediaItems->count();
                        $imageUrls = $mediaItems->map(fn($m) => $m->getUrl())->toArray();
                    @endphp

                    <div class="mb-3 h-36 w-full overflow-hidden rounded-xl bg-gray-100 dark:bg-gray-800 sm:h-40 md:h-48">
                        @if ($mediaCount > 1)
                            {{-- KARUSEL --}}
                            <div
                                x-data="{
                                    activeSlide: 0,
                                    slidesCount: {{ $mediaCount }},
                                    next() { this.activeSlide = (this.activeSlide === this.slidesCount - 1) ? 0 : this.activeSlide + 1 },
                                    prev() { this.activeSlide = (this.activeSlide === 0) ? this.slidesCount - 1 : this.activeSlide - 1 },
                                    openZoom() {
                                        $dispatch('open-zoom', {
                                            urls: @js($imageUrls),
                                            index: this.activeSlide,
                                        });
                                    }
                                }"
                                class="relative h-full w-full"
                            >
                                @foreach ($mediaItems as $index => $media)
                                    <img
                                        x-show="activeSlide === {{ $index }}"
                                        @click="openZoom()"
                                        x-cloak
                                        x-transition:enter="transition ease-out duration-300"
                                        x-transition:enter-start="opacity-0"
                                        x-transition:enter-end="opacity-100"
                                        src="{{ $media->getUrl('optimized') }}"
                                        alt="{{ $product->name }}"
                                        class="absolute inset-0 h-full w-full object-cover cursor-zoom-in"
                                    >
                                @endforeach

                                {{-- Tugmalar --}}
                                <button
                                    @click.prevent.stop="prev()"
                                    class="absolute left-1 top-1/2 -translate-y-1/2 rounded-full bg-white/80 p-1 text-gray-800 opacity-0 shadow hover:bg-white group-hover:opacity-100 transition focus:outline-none md:left-2"
                                >
                                    <x-heroicon-m-chevron-left class="h-3 w-3 md:h-4 md:w-4" />
                                </button>

                                <button
                                    @click.prevent.stop="next()"
                                    class="absolute right-1 top-1/2 -translate-y-1/2 rounded-full bg-white/80 p-1 text-gray-800 opacity-0 shadow hover:bg-white group-hover:opacity-100 transition focus:outline-none md:right-2"
                                >
                                    <x-heroicon-m-chevron-right class="h-3 w-3 md:h-4 md:w-4" />
                                </button>

                                {{-- Kichik nuqtalar --}}
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
                            <div
                                x-data="{
                                    openZoom() {
                                        $dispatch('open-zoom', {
                                            urls: @js($imageUrls),
                                            index: 0,
                                        });
                                    }
                                }"
                                class="h-full w-full"
                            >
                                <img
                                    @click="openZoom()"
                                    src="{{ $mediaItems[0]->getUrl('optimized') }}"
                                    alt="{{ $product->name }}"
                                    class="h-full w-full object-cover transition duration-300 cursor-zoom-in group-hover:scale-105"
                                >
                            </div>
                        @else
                            {{-- RASM YO'Q --}}
                            <a href="{{ route('filament.admin.resources.products.view', $product) }}" class="flex h-full w-full items-center justify-center text-xs text-gray-500">
                                Rasm mavjud emas
                            </a>
                        @endif
                    </div>

                    {{-- Matn qismi --}}
                    <a href="{{ route('filament.admin.resources.products.view', $product) }}" class="flex flex-1 flex-col justify-between">
                        <div>
                            <div class="text-sm font-semibold text-gray-900 group-hover:text-primary-600 dark:text-white dark:group-hover:text-primary-400 line-clamp-2 md:text-base">
                                {{ $product->name }}
                            </div>
                        </div>
                    </a>

                </div>
            @empty
                <div class="col-span-2 rounded-2xl border border-dashed border-gray-300 bg-white p-8 text-center text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400 sm:col-span-3 lg:col-span-5 xl:col-span-6">
                    Mahsulot topilmadi.
                </div>
            @endforelse
        </div>

        <div>
            {{ $products->links() }}
        </div>
    </div>

    {{-- ZOOM MODAL - Sizning kodingiz asosida --}}
    <div
        x-data="{
            show: false,
            i: 0,
            urls: []
        }"
        @open-zoom.window="show = true; urls = $event.detail?.urls ?? []; i = $event.detail?.index ?? 0"
        @keydown.escape.window="show = false"
        x-show="show"
        x-cloak
        @click="show = false"
        class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/95 p-4"
        style="display: none;"
    >
        <div class="relative w-full h-full flex items-center justify-center" @click.stop>
            <template x-if="urls.length > 0">
                <div class="relative">
                    <img :src="urls[i]" alt="Zoom" class="max-w-full max-h-[90vh] h-auto mx-auto object-contain">

                    {{-- Navigation tugmalari --}}
                    <template x-if="urls.length > 1">
                        <div>
                            <button type="button"
                                    @click.stop="i = (i - 1 + urls.length) % urls.length"
                                    class="absolute left-2 top-1/2 -translate-y-1/2 bg-white/90 text-gray-800 rounded-full w-10 h-10 flex items-center justify-center hover:bg-white shadow-lg transition md:w-12 md:h-12">
                                <svg class="h-6 w-6 md:h-8 md:w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                                </svg>
                            </button>
                            <button type="button"
                                    @click.stop="i = (i + 1) % urls.length"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 bg-white/90 text-gray-800 rounded-full w-10 h-10 flex items-center justify-center hover:bg-white shadow-lg transition md:w-12 md:h-12">
                                <svg class="h-6 w-6 md:h-8 md:w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                </svg>
                            </button>
                        </div>
                    </template>

                    {{-- Counter --}}
                    <div class="absolute bottom-4 left-1/2 -translate-x-1/2 bg-black/60 text-white px-3 py-1 rounded-full text-sm backdrop-blur-sm" x-show="urls.length > 1" x-text="(i+1) + ' / ' + urls.length"></div>
                </div>
            </template>

            {{-- X tugmasi --}}
            <button
                @click.stop="show = false"
                type="button"
                class="absolute top-4 right-4 z-10 bg-white text-gray-800 rounded-full w-10 h-10 flex items-center justify-center shadow-lg hover:bg-gray-100 transition md:w-12 md:h-12"
                title="Yopish (ESC)"
            >
                <svg class="h-6 w-6 md:h-8 md:w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </div>
</x-filament::page>
