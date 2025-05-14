@extends('layouts.admin')
@section('title', 'Редактировать кабинет')
@section('styles')
<style>
    #ro-modal {
        border: 1px solid #e5e7eb;
        border-radius: 0.375rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        max-height: 24rem;
        overflow-y: auto;
        z-index: 50;
    }

    #ro-modal .select-all-index {
        transform: scale(0.9);
    }

    #ro-modal label {
        cursor: pointer;
    }

    #ro-modal .ml-4 {
        border-left: 1px solid #e5e7eb;
        padding-left: 1rem;
    }

    #ro-modal .hover\:bg-blue-50:hover {
        background-color: #bfdbfe;
    }
    .module-group {
        border-left: 2px solid #3b82f6;
        padding-left: 0.5rem;
    }

    .module-group label {
        font-weight: 600;
    }

    .outcome-item {
        padding-left: 1rem;
    }

    #ro-modal .hover\:bg-blue-50:hover {
        background-color: #bfdbfe;
    }

    #ro-modal .select-module:checked ~ .module-group label {
        color: #1d4ed8;
        font-weight: 600;
    }
</style>
@endsection
@section('content')
<div class="bg-white shadow rounded-lg p-6 max-w-lg mx-auto">
    <h2 class="text-xl font-semibold text-gray-900 mb-4">Редактировать кабинет</h2>
    <form action="{{ route('cabinets.update', $cabinet->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="mb-4">
            <label for="number" class="block text-sm font-medium text-gray-700">Номер кабинета</label>
            <input type="text" name="number" id="number" value="{{ $cabinet->number }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm" required>
        </div>
        <div class="mb-4">
            <label for="description" class="block text-sm font-medium text-gray-700">Описание (необязательно)</label>
            <textarea name="description" id="description" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">{{ $cabinet->description }}</textarea>
        </div>
        <div class="mb-4">
            <label for="capacity" class="block text-sm font-medium text-gray-700">Вместимость (необязательно)</label>
            <input type="number" name="capacity" id="capacity" value="{{ $cabinet->capacity }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
        </div>
        @include('cabinets.parts.selector')
        <div class="flex justify-end">
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Сохранить</button>
        </div>
    </form>
</div>
@endsection
@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('search-ro');
        const modal = document.getElementById('ro-modal');

        if (searchInput && modal) {
            searchInput.addEventListener('focus', () => {
                modal.classList.remove('hidden');
            });

            document.addEventListener('click', (e) => {
                if (!modal.contains(e.target) && e.target !== searchInput) {
                    modal.classList.add('hidden');
                }
            });
        }

        // Выбор всех РО по дисциплине
        document.querySelectorAll('.select-discipline').forEach(checkbox => {
            checkbox.addEventListener('change', function () {
                const discipline = this.id.replace('discipline-', '');
                const moduleIndex = this.dataset.moduleIndex;

                document.querySelectorAll(`.discipline-${discipline}.module-${moduleIndex}`).forEach(cb => {
                    cb.checked = this.checked;
                });
            });
        });

        // Обновление состояния чекбоксов дисциплин
        document.querySelectorAll('[data-module-index]').forEach(checkbox => {
            checkbox.addEventListener('change', function () {
                const discipline = this.name.replace('learning_outcome_ids[', '').replace(']', '');
                const moduleIndex = this.dataset.moduleIndex;
                const disciplineCheckboxes = document.querySelectorAll(`[data-module-index="${moduleIndex}"][id^="discipline-${discipline}"]`);

                const allChecked = Array.from(disciplineCheckboxes).every(cb => cb.checked);
                const anyChecked = Array.from(disciplineCheckboxes).some(cb => cb.checked);

                if (disciplineCheckboxes.length > 0) {
                    disciplineCheckboxes.forEach(cb => {
                        cb.checked = allChecked;
                        cb.indeterminate = anyChecked && !allChecked;
                    });
                }
            });
        });

        // Фильтрация по поиску
        if (searchInput) {
            searchInput.addEventListener('input', function () {
                const searchTerm = this.value.toLowerCase();
                document.querySelectorAll('#ro-modal > div').forEach(group => {
                    const index = group.querySelector('label.font-medium')?.textContent.toLowerCase() || '';
                    const isVisible = index.includes(searchTerm);
                    group.style.display = isVisible ? 'block' : 'none';
                });
            });
        }
    });
</script>
@endsection
