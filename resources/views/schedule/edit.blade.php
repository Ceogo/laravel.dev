@extends('layouts.admin')

@section('title', 'Редактировать пару')

@section('content')
    <h2 class="text-3xl font-bold text-gray-800 mb-6 text-center">Редактировать пару</h2>
    <div class="bg-white p-6 rounded-lg shadow-lg max-w-md mx-auto hover-scale">
        <form method="POST" action="{{ route('schedule.edit', $schedule->id) }}">
            @csrf
            <div class="mb-4">
                <label for="learning_outcome_id" class="block text-sm font-medium text-gray-700">Дисциплина:</label>
                <select name="learning_outcome_id" id="learning_outcome_id" class="mt-1 block w-full p-2 border rounded focus:ring-2 focus:ring-blue-500">
                    @foreach ($learningOutcomes as $lo)
                        <option value="{{ $lo->id }}" {{ $lo->id == $schedule->learning_outcome_id ? 'selected' : '' }}>
                            {{ $lo->discipline_name }} ({{ $lo->teacher_name ?? 'вакансия' }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="text-center">
                <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition">Сохранить</button>
            </div>
        </form>
    </div>
@endsection
