@extends('layouts.admin')

@section('title', 'Редактирование данных')

@section('styles')
    <style>
        .table-header {
            background: linear-gradient(to right, #4b6cb7, #182848);
            color: white;
        }
        .alert-danger {
            background-color: #fee2e2;
            color: #991b1b;
        }
    </style>
@endsection

@section('content')
    <h2 class="text-3xl font-bold text-gray-800 mb-6 text-center">Редактирование данных учебного плана</h2>
    <div class="bg-white p-6 rounded-lg shadow-lg hover-scale">
        <form method="POST" action="{{ route('save_data') }}">
            @csrf
            <div class="overflow-x-auto">
                <table class="w-full table-auto border-collapse">
                    <thead>
                        <tr class="table-header">
                            <th class="p-3">Включить</th>
                            @foreach($headers as $header)
                                <th class="p-3">{{ $header }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($data as $index => $row)
                            <tr class="hover:bg-gray-50">
                                <td class="p-3 border">
                                    <input type="checkbox" name="enable_row[{{ $index }}]" checked class="h-5 w-5 text-blue-600">
                                </td>
                                @foreach($headers as $header)
                                    <td class="p-3 border">
                                        <input type="text" name="data[{{ $index }}][{{ $header }}]" value="{{ $row[$header] ?? '' }}" class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-500">
                                    </td>
                                @endforeach
                                <input type="hidden" name="data[{{ $index }}][module_index]" value="{{ $row['module_index'] ?? 'UNKNOWN_' . $index }}">
                                <input type="hidden" name="data[{{ $index }}][module_name]" value="{{ $row['module_name'] ?? 'Неизвестный модуль' }}">
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-6 text-center">
                <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition">Сохранить</button>
            </div>
        </form>
        @if ($errors->any())
            <div class="alert-danger p-4 rounded-lg mt-6 shadow-md">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
@endsection
