<x-filament-panels::page>
    <form wire:submit.prevent="updateStats"
          class="bg-white dark:bg-gray-800 p-6 rounded-xl border border-gray-200 dark:border-gray-700 shadow-lg">

        <div class="flex flex-col md:flex-row items-end gap-4">
            {{-- Boshlanish sanasi --}}
            <div class="flex-1">
                <label class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2 block">
                    {{ __('Boshlanish sanasi') }}
                </label>
                <x-filament::input
                    type="date"
                    wire:model.defer="start_date"
                    class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                           border border-gray-300 dark:border-gray-600 rounded-lg
                           focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
                />
            </div>

            {{-- Tugash sanasi --}}
            <div class="flex-1">
                <label class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2 block">
                    {{ __('Tugash sanasi') }}
                </label>
                <x-filament::input
                    type="date"
                    wire:model.defer="end_date"
                    class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                           border border-gray-300 dark:border-gray-600 rounded-lg
                           focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
                />
            </div>

            {{-- Filter tugmasi --}}
            <div class="md:w-auto">
                <div class="invisible block mb-2"></div>
                <x-filament::button
                    type="submit"
                    class="h-[42px] px-6 text-sm font-medium bg-blue-600 hover:bg-blue-700
                           focus:ring-4 focus:ring-blue-300 dark:focus:ring-blue-800 text-white
                           rounded-lg transition-all duration-200"
                >
                    {{ __('Filter') }}
                </x-filament::button>
            </div>
        </div>
    </form>
</x-filament-panels::page>
