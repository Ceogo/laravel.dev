<div class="mt-4">
    <label class="block text-sm font-medium text-gray-700 mb-2">Разрешённые модули и РО</label>

    <div class="relative">
        <input type="text" id="search-ro" placeholder="Поиск по модулю или дисциплине..."
            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">

        <div id="ro-modal" class="hidden absolute z-10 mt-1 w-full bg-white rounded-md shadow-lg max-h-60 overflow-y-auto">
            @if (!empty($groupedModules))
                @foreach ($groupedModules as $index => $moduleGroup)
                    <div class="p-2 border-b hover:bg-blue-50 transition-colors">
                        <!-- Чекбокс модуля -->
                        <div class="flex items-center mb-2">
                            <input type="checkbox" name="module_ids[]"
                                value="{{ $moduleGroup->first()->id }}"
                                id="module-{{ Str::slug($index) }}"
                                class="mr-2 select-module"
                                {{ in_array($moduleGroup->first()->id, $selectedModules ?? []) ? 'checked' : '' }}>
                            <label for="module-{{ Str::slug($index) }}" class="font-medium">
                                {{ $index }} ({{ $moduleGroup->count() }} версий)
                            </label>
                        </div>

                        <!-- Группировка РО по дисциплинам -->
                        @php
                            // Группируем РО по дисциплинам
                            $groupedROs = $moduleGroup->flatMap(fn($module) => $module->learningOutcomes)
                                                    ->groupBy('discipline_name');
                        @endphp

                        <div class="ml-4 mt-1">
                            @foreach ($groupedROs as $discipline => $roGroup)
                                <div class="flex items-center mb-2">
                                    <input type="checkbox"
                                        id="discipline-{{ Str::slug($discipline) }}"
                                        class="mr-2 select-discipline"
                                        data-module-index="{{ Str::slug($index) }}"
                                        {{ in_array($roGroup->first()->id, $selectedLOs ?? []) ? 'checked' : '' }}>
                                    <label for="discipline-{{ Str::slug($discipline) }}" class="text-sm">
                                        {{ $discipline }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            @else
                <p class="text-center text-gray-500 p-4">Нет доступных модулей</p>
            @endif
        </div>
    </div>
</div>
