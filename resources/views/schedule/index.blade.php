@extends('layouts.admin')

@section('title', 'Расписание')

@section('styles')
    <style>
        .pm-lesson {
            background-color: #fef2f2;
        }
        .class-hour {
            background-color: var(--gray-light);
        }
        .table-header {
            background: linear-gradient(to right, var(--primary), var(--sidebar-dark));
            color: var(--background);
            font-weight: 600;
        }
        .alert-warning {
            background-color: #fefce8;
            color: #92400e;
            border: 1px solid #d97706;
            border-radius: 0.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        .alert-success {
            background-color: #f0fdf4;
            color: #15803d;
            border: 1px solid #15803d;
            border-radius: 0.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        .draggable {
            transition: background 0.2s, transform 0.2s;
        }
        .draggable:hover {
            cursor: move;
            background-color: #e0e7ff;
            transform: scale(1.01);
        }
        .drag-over {
            background-color: #dbeafe;
            border: 2px dashed var(--primary);
        }
        .dragging {
            opacity: 0.5;
            transform: scale(0.98);
        }
        .input-field, select {
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .input-field:focus, select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(54, 186, 107, 0.2);
        }
        .submit-button {
            background-color: var(--primary);
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
        @media (max-width: 768px) {
            .draggable {
                pointer-events: none;
            }
        }
    </style>
    <link href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css" rel="stylesheet">
@endsection

@section('content')
    <div class="max-w-7xl mx-auto">
        <h2 class="text-3xl font-bold text-gray-800 mb-4 text-center">Расписание для группы: {{ $group->name }}</h2>
        <p class="text-center text-gray-600 mb-6">Семестр: {{ $semester }}, Неделя: {{ $week }}</p>

        @if (session('success'))
            <div class="alert-success p-6 rounded-lg shadow-md text-center mb-6">
                {{ session('success') }}
            </div>
        @endif
        @if (session('warnings'))
            <div class="alert-warning p-6 rounded-lg shadow-md text-center mb-6">
                @foreach (session('warnings') as $warning)
                    <p class="mb-2">{{ $warning }}</p>
                @endforeach
            </div>
        @endif

        <div class="bg-white p-8 rounded-xl shadow-lg mb-6 hover-scale">
            <form method="GET" action="{{ route('schedule') }}" class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="group_id" class="block text-sm font-medium text-gray-700 mb-1">Группа:</label>
                    <select name="group_id" id="group_id" class="input-field block w-full p-3 border rounded bg-gray-50 text-gray-800 focus:outline-none">
                        @foreach($groups as $g)
                            <option value="{{ $g->id }}" {{ $g->id == $group->id ? 'selected' : '' }}>{{ $g->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="semester" class="block text-sm font-medium text-gray-700 mb-1">Семестр:</label>
                    <select name="semester" id="semester" class="input-field block w-full p-3 border rounded bg-gray-50 text-gray-800 focus:outline-none">
                        <option value="3" {{ $semester == 3 ? 'selected' : '' }}>3-й семестр</option>
                        <option value="4" {{ $semester == 4 ? 'selected' : '' }}>4-й семестр</option>
                    </select>
                </div>
                <div>
                    <label for="week" class="block text-sm font-medium text-gray-700 mb-1">Неделя:</label>
                    <input type="number" name="week" id="week" class="input-field block w-full p-3 border rounded bg-gray-50 text-gray-800 focus:outline-none" value="{{ $week }}" min="1" required>
                </div>
                <div class="md:col-span-3 text-center mt-4">
                    <button type="submit" class="submit-button bg-primary text-white px-8 py-3 rounded-lg shadow-md">Показать расписание</button>
                </div>
            </form>
        </div>

        @if(empty($schedule['monday']) && empty($schedule['tuesday']) && empty($schedule['wednesday']) && empty($schedule['thursday']) && empty($schedule['friday']))
            <div class="alert-warning p-6 rounded-lg shadow-md text-center">
                Расписание не сгенерировано. Проверьте наличие данных.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full table-auto border-collapse bg-white">
                    <thead>
                        <tr class="table-header">
                            <th class="p-4 text-left">Пара</th>
                            <th class="p-4 text-left">Время</th>
                            <th class="p-4 text-left">Понедельник</th>
                            <th class="p-4 text-left">Вторник</th>
                            <th class="p-4 text-left">Среда</th>
                            <th class="p-4 text-left">Четверг</th>
                            <th class="p-4 text-left">Пятница</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="class-hour">
                            <td class="p-4 border">-</td>
                            <td class="p-4 border">{{ $bellSchedule['monday']['class_hour']['start'] }}–{{ $bellSchedule['monday']['class_hour']['end'] }}</td>
                            <td class="p-4 border">Классный час</td>
                            <td colspan="4" class="p-4 border"></td>
                        </tr>
                        @for($pair = 1; $pair <= 7; $pair++)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="p-4 border">{{ $pair }}</td>
                                <td class="p-4 border">
                                    {{ $bellSchedule['other_days'][$pair]['start'] }}–
                                    {{ $bellSchedule['other_days'][$pair]['end'] }}
                                    @if($pair == 1)
                                        <br><small class="text-gray-500">(Пн: {{ $bellSchedule['monday'][$pair]['start'] }}–{{ $bellSchedule['monday'][$pair]['end'] }})</small>
                                    @endif
                                </td>
                                @foreach(['monday', 'tuesday', 'wednesday', 'thursday', 'friday'] as $day)
                                    <td class="p-4 border {{ isset($schedule[$day][$pair]['module_index']) && strpos($schedule[$day][$pair]['module_index'], 'ПМ') === 0 ? 'pm-lesson' : '' }} {{ isset($schedule[$day][$pair]) ? 'draggable' : '' }}"
                                        @if(isset($schedule[$day][$pair]))
                                            draggable="true"
                                            data-schedule-id="{{ $schedule[$day][$pair]['id'] }}"
                                            data-day="{{ $day }}"
                                            data-pair="{{ $pair }}"
                                            ondragstart="handleDragStart(event)"
                                            ondragover="handleDragOver(event)"
                                            ondrop="handleDrop(event)"
                                            ondragenter="handleDragEnter(event)"
                                            ondragleave="handleDragLeave(event)"
                                            ondragend="handleDragEnd(event)"
                                        @endif
                                    >
                                        @if(isset($schedule[$day][$pair]))
                                            <strong class="text-gray-800">{{ $schedule[$day][$pair]['discipline_name'] }}</strong><br>
                                            <span class="text-gray-600">{{ $schedule[$day][$pair]['teacher_name'] }}</span><br>
                                            <small class="text-gray-500">
                                                {{ $schedule[$day][$pair]['type'] == 'theoretical' ? 'Теория' : ($schedule[$day][$pair]['type'] == 'lab_practical' ? 'ЛПР' : 'КР/КП') }}
                                            </small><br>
                                            <a href="{{ route('schedule.edit', $schedule[$day][$pair]['id']) }}" class="text-primary hover:underline text-sm">Редактировать</a>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script>
        let draggedElement = null;

        function handleDragStart(event) {
            draggedElement = event.target;
            event.target.classList.add('dragging');
            event.dataTransfer.setData('source', `${event.target.dataset.day}_${event.target.dataset.pair}`);
            event.dataTransfer.setData('scheduleId', event.target.dataset.scheduleId);
        }

        function handleDragOver(event) {
            if (event.target.classList.contains('draggable')) {
                event.preventDefault();
            }
        }

        function handleDragEnter(event) {
            if (event.target.classList.contains('draggable')) {
                event.target.classList.add('drag-over');
            }
        }

        function handleDragLeave(event) {
            event.target.classList.remove('drag-over');
        }

        function handleDragEnd(event) {
            event.target.classList.remove('dragging');
            document.querySelectorAll('.drag-over').forEach(el => el.classList.remove('drag-over'));
        }

        function handleDrop(event) {
            event.preventDefault();
            const target = event.target.closest('.draggable');
            if (!target || !draggedElement) return;

            const source = event.dataTransfer.getData('source');
            const [sourceDay, sourcePair] = source.split('_');
            const targetDay = target.dataset.day;
            const targetPair = target.dataset.pair;

            fetch('{{ route("schedule.swap") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    source: { day: sourceDay, pair: sourcePair },
                    target: { day: targetDay, pair: targetPair },
                    group_id: '{{ $group->id }}',
                    semester: '{{ $semester }}',
                    week: '{{ $week }}'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Toastify({
                        text: 'Занятия успешно переставлены!',
                        duration: 3000,
                        gravity: 'top',
                        position: 'right',
                        backgroundColor: '#10b981',
                    }).showToast();
                    location.reload();
                } else {
                    Toastify({
                        text: 'Ошибка: ' + data.message,
                        duration: 3000,
                        gravity: 'top',
                        position: 'right',
                        backgroundColor: '#ef4444',
                    }).showToast();
                }
            })
            .catch(error => {
                Toastify({
                    text: 'Ошибка при перестановке: ' + error.message,
                    duration: 3000,
                    gravity: 'top',
                    position: 'right',
                    backgroundColor: '#ef4444',
                }).showToast();
            });

            target.classList.remove('drag-over');
            draggedElement = null;
        }

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && draggedElement) {
                draggedElement.classList.remove('dragging');
                document.querySelectorAll('.drag-over').forEach(el => el.classList.remove('drag-over'));
                draggedElement = null;
            }
        });
    </script>
@endsection
