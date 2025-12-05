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
                                        const images = {{ json_encode($mediaItems->map(fn($m) => $m->getUrl())->toArray()) }};
                                        $dispatch('open-zoom', { images: images, activeIndex: this.activeSlide });
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
                                        $dispatch('open-zoom', { images: ['{{ $mediaItems[0]->getUrl() }}'], activeIndex: 0 });
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

    {{-- GLOBAL ZOOM MODAL --}}
    <div
        x-data="{
            show: false,
            images: [],
            activeIndex: 0,
            next() { this.activeIndex = (this.activeIndex === this.images.length - 1) ? 0 : this.activeIndex + 1 },
            prev() { this.activeIndex = (this.activeIndex === 0) ? this.images.length - 1 : this.activeIndex - 1 }
        }"
        @open-zoom.window="show = true; images = $event.detail.images; activeIndex = $event.detail.activeIndex"
        @keydown.escape.window="show = false"
        x-show="show"
        x-cloak
        @click="show = false"
        class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/95"
        style="display: none;"
    >
        <div class="relative w-full h-full flex items-center justify-center p-4">
            <template x-for="(image, index) in images" :key="index">
                <img
                    x-show="activeIndex === index"
                    :src="image"
                    alt="Zoom"
                    class="max-h-[90vh] max-w-[95vw] w-auto h-auto object-contain"
                    @click.stop
                >
            </template>

            {{-- X tugmasi --}}
            <button
                @click.stop="show = false"
                class="absolute top-4 right-4 z-10 rounded-full bg-white p-2 text-gray-800 shadow-lg hover:bg-gray-100 transition"
                title="Yopish (ESC)"
            >
                <svg class="h-6 w-6 md:h-8 md:w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>

            {{-- Navigation tugmalari --}}
            <button
                x-show="images.length > 1"
                @click.stop="prev()"
                class="absolute left-4 top-1/2 -translate-y-1/2 rounded-full bg-white/90 p-3 text-gray-800 shadow-lg hover:bg-white transition"
            >
                <svg class="h-6 w-6 md:h-8 md:w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
            </button>

            <button
                x-show="images.length > 1"
                @click.stop="next()"
                class="absolute right-4 top-1/2 -translate-y-1/2 rounded-full bg-white/90 p-3 text-gray-800 shadow-lg hover:bg-white transition"
            >
                <svg class="h-6 w-6 md:h-8 md:w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
        </div>
    </div>
</x-filament::page>
