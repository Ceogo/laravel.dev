@extends('layouts.admin')
@section('title', 'Редактировать преподавателя')
@section('content')
<div class="bg-white shadow rounded-lg p-6 max-w-lg mx-auto">
    <h2 class="text-xl font-semibold text-gray-900 mb-4">Редактировать преподавателя</h2>
    <form action="{{ route('teachers.update', $teacher->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="mb-4">
            <label for="display_name" class="block text-sm font-medium text-gray-700">Имя из расписания (например, Ташимов Д.К.)</label>
            <input type="text" name="display_name" id="display_name" value="{{ $teacher->display_name }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
        </div>

        <div class="mb-4">
            <label for="surname" class="block text-sm font-medium text-gray-700">Фамилия</label>
            <input type="text" name="surname" id="surname" value="{{ $teacher->surname }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm" required>
        </div>

        <div class="mb-4">
            <label for="realname" class="block text-sm font-medium text-gray-700">Имя</label>
            <input type="text" name="realname" id="realname" value="{{ $teacher->realname }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm" required>
        </div>

        <div class="mb-4">
            <label for="firstname" class="block text-sm font-medium text-gray-700">Отчество (необязательно)</label>
            <input type="text" name="firstname" id="firstname" value="{{ $teacher->firstname }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
        </div>
        <div class="mb-4">
            <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
            <input type="email" name="email" id="email" value="{{ $teacher->email }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm" required>
        </div>
        <div class="mb-4">
            <label for="password" class="block text-sm font-medium text-gray-700">Пароль (оставьте пустым, если не хотите менять)</label>
            <input type="password" name="password" id="password" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
        </div>
        <div class="mb-4">
            <label for="status" class="block text-sm font-medium text-gray-700">Статус</label>
            <select name="status" id="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                <option value="active" {{ $teacher->status == 'active' ? 'selected' : '' }}>Активен</option>
                <option value="inactive" {{ $teacher->status == 'inactive' ? 'selected' : '' }}>Неактивен</option>
            </select>
        </div>
        <div class="mb-4">
            <label for="preferred_cabinet_id" class="block text-sm font-medium text-gray-700">Предпочтительный кабинет</label>
            <select name="preferred_cabinet_id" id="preferred_cabinet_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                <option value="">— Не выбрано —</option>
                @foreach ($cabinets as $cabinet)
                    <option value="{{ $cabinet->id }}" {{ $selectedCabinet == $cabinet->id ? 'selected' : '' }}>{{ $cabinet->number }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex justify-end">
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Сохранить</button>
        </div>
    </form>
</div>
@endsection
