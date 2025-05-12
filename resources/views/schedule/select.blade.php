@extends('layouts.admin')

@section('title', 'Выбор группы')

@section('content')
    <h2 class="text-3xl font-bold text-gray-800 mb-6 text-center">Выберите группу</h2>
    <div class="bg-white p-6 rounded-lg shadow-lg max-w-md mx-auto hover-scale">
        <form method="GET" action="{{ route('schedule') }}">
            <div class="mb-4">
                <label for="group_id" class="block text-sm font-medium text-gray-700">Группа:</label>
                <select name="group_id" id="group_id" class="mt-1 block w-full p-2 border rounded focus:ring-2 focus:ring-blue-500" required>
                    @foreach($groups as $group)
                        <option value="{{ $group->id }}">{{ $group->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-4">
                <label for="semester" class="block text-sm font-medium text-gray-700">Семестр:</label>
                <select name="semester" id="semester" class="mt-1 block w-full p-2 border rounded focus:ring-2 focus:ring-blue-500">
                    <option value="3" {{ $semester == 3 ? 'selected' : '' }}>3-й семестр</option>
                    <option value="4" {{ $semester == 4 ? 'selected' : '' }}>4-й семестр</option>
                </select>
            </div>
            <div class="mb-4">
                <label for="week" class="block text-sm font-medium text-gray-700">Неделя:</label>
                <input type="number" name="week" id="week" class="mt-1 block w-full p-2 border rounded focus:ring-2 focus:ring-blue-500" value="{{ $week }}" min="1" required>
            </div>
            <div class="text-center">
                <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition">Показать расписание</button>
            </div>
        </form>
    </div>
@endsection
