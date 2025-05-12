@extends('layouts.admin')

@section('title', 'Редактирование данных')

@section('styles')
    <style>
        .table-header {
            background: linear-gradient(to right, var(--primary), var(--sidebar-dark));
            color: var(--background);
            font-weight: 600;
        }
        .alert-danger {
            background-color: #fef2f2;
            color: #b91c1c;
            border: 1px solid #b91c1c;
            border-radius: 0.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        .input-field {
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .input-field:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(54, 186, 107, 0.2);
        }
        .submit-button {
            transition: background-color 0.2s, transform 0.2s;
        }
        .submit-button:hover {
            background-color: var(--accent);
            transform: translateY(-1px);
        }
        table {
            border-radius: 0.5rem;
            overflow: hidden;
        }
        td, th {
            border-color: var(--gray-mid);
        }
    </style>
@endsection

@section('content')
    <div class="max-w-7xl mx-auto">
        <h2 class="text-3xl font-bold text-gray-800 mb-8 text-center">Редактирование данных учебного плана</h2>
        <div class="bg-white p-8 rounded-xl shadow-lg hover-scale">
            <form method="POST" action="{{ route('save_data') }}">
                @csrf
                <div class="overflow-x-auto">
                    <table class="w-full table-auto border-collapse bg-white">
                        <thead>
                            <tr class="table-header">
                                <th class="p-4 text-left">Включить</th>
                                @foreach($headers as $header)
                                    <th class="p-4 text-left">{{ $header }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data as $index => $row)
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="p-4 border">
                                        <input type="checkbox" name="enable_row[{{ $index }}]" checked class="h-5 w-5 text-primary rounded focus:ring-primary">
                                    </td>
                                    @foreach($headers as $header)
                                        <td class="p-4 border">
                                            <input type="text" name="data[{{ $index }}][{{ $header }}]" value="{{ $row[$header] ?? '' }}" class="input-field w-full p-2 border rounded bg-gray-50 text-gray-800 focus:outline-none">
                                        </td>
                                    @endforeach
                                    <input type="hidden" name="data[{{ $index }}][module_index]" value="{{ $row['module_index'] ?? 'UNKNOWN_' . $index }}">
                                    <input type="hidden" name="data[{{ $index }}][module_name]" value="{{ $row['module_name'] ?? 'Неизвестный модуль' }}">
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-8 text-center">
                    <button type="submit" class="submit-button bg-primary text-white px-8 py-3 rounded-lg shadow-md">Сохранить</button>
                </div>
            </form>
            @if ($errors->any())
                <div class="alert-danger p-6 mt-6">
                    <ul class="list-disc list-inside space-y-2">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
@endsection
