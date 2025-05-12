@extends('layouts.admin')

@section('title', 'Загрузка документа')

@section('styles')
    <style>
        .alert-danger {
            background-color: #fee2e2;
            color: #991b1b;
        }
    </style>
@endsection

@section('content')
    <h2 class="text-3xl font-bold text-gray-800 mb-6 text-center">Загрузка документа</h2>
    <div class="bg-white p-6 rounded-lg shadow-lg max-w-md mx-auto hover-scale">
        <form action="{{ route('upload') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="mb-4">
                <input type="file" name="file" class="block w-full p-2 border rounded focus:ring-2 focus:ring-blue-500" required>
            </div>
            <div class="text-center">
                <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition">Отправить</button>
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
