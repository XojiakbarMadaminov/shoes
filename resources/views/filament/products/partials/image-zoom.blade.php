@php $urls = $urls ?? []; @endphp
<div class="p-2" x-data="{ i: 0, urls: @js($urls) }">
    <template x-if="urls.length === 0">
        <div class="text-sm text-gray-500">Rasm mavjud emas.</div>
    </template>

    <template x-if="urls.length > 0">
        <div class="relative">
            <img :src="urls[i]" alt="Preview" class="max-w-full h-auto mx-auto" style="max-height: 75vh;">

            <button type="button"
                    @click="i = (i - 1 + urls.length) % urls.length"
                    class="absolute left-2 top-1/2 -translate-y-1/2 bg-black/50 text-white rounded-full w-9 h-9 flex items-center justify-center hover:bg-black/70">
                <<
            </button>
            <button type="button"
                    @click="i = (i + 1) % urls.length"
                    class="absolute right-2 top-1/2 -translate-y-1/2 bg-black/50 text-white rounded-full w-9 h-9 flex items-center justify-center hover:bg-black/70">
                >>
            </button>

            <div class="mt-2 text-center text-xs text-gray-500" x-text="(i+1) + ' / ' + urls.length"></div>
        </div>
    </template>
</div>
