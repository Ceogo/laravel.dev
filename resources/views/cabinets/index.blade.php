@extends('layouts.admin')
@section('title', 'Кабинеты')
@section('content')
<div class="bg-white shadow rounded-lg p-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-semibold text-gray-900">Список кабинетов</h2>
        <a href="{{ route('cabinets.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Добавить кабинет</a>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Номер</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Описание</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Вместимость</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Разрешённые РО</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Действия</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach ($cabinets as $cabinet)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $cabinet->number }}</td>
                        <td class="px-6 py-4 text-sm text-gray-900">{{ $cabinet->description ?? '—' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $cabinet->capacity ?? '—' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-900">
                            {{ $cabinet->learningOutcomes->pluck('discipline_name')->join(', ') ?: '—' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <a href="{{ route('cabinets.edit', $cabinet->id) }}" class="text-blue-600 hover:text-blue-800 mr-2">Редактировать</a>
                            <form action="{{ route('cabinets.destroy', $cabinet->id) }}" method="POST" onsubmit="return confirm('Вы уверены?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800">Удалить</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
