@extends('layouts.admin')
@section('title', 'Добавить кабинет')
@section('content')
<div class="bg-white shadow rounded-lg p-6 max-w-lg mx-auto">
    <h2 class="text-xl font-semibold text-gray-900 mb-4">Добавить кабинет</h2>
    <form action="{{ route('cabinets.store') }}" method="POST">
        @csrf
        <div class="mb-4">
            <label for="number" class="block text-sm font-medium text-gray-700">Номер кабинета</label>
            <input type="text" name="number" id="number" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm" required>
        </div>
        <div class="mb-4">
            <label for="description" class="block text-sm font-medium text-gray-700">Описание (необязательно)</label>
            <textarea name="description" id="description" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"></textarea>
        </div>
        <div class="mb-4">
            <label for="capacity" class="block text-sm font-medium text-gray-700">Вместимость (необязательно)</label>
            <input type="number" name="capacity" id="capacity" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
        </div>
        <div class="mb-4">
            <label for="learning_outcome_ids" class="block text-sm font-medium text-gray-700">Разрешённые РО</label>
            <select name="learning_outcome_ids[]" id="learning_outcome_ids" multiple class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                @foreach ($learningOutcomes as $lo)
                    <option value="{{ $lo->id }}">{{ $lo->index }} {{ $lo->discipline_name }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex justify-end">
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Сохранить</button>
        </div>
    </form>
</div>
@endsection
