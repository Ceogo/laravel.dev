@extends('layouts.admin')

@section('title', 'Расписание')

@section('styles')
    <style>
        .pm-lesson {
            background-color: #ffe5e5;
        }
        .class-hour {
            background-color: #e9ecef;
        }
        .table-header {
            background: linear-gradient(to right, #4b6cb7, #182848);
            color: white;
        }
        .alert-warning {
            background-color: #fef3c7;
            color: #92400e;
        }
        .alert-success {
            background-color: #d1fae5;
            color: #065f46;
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
            border: 2px dashed #3b82f6;
        }
        .dragging {
            opacity: 0.5;
            transform: scale(0.98);
        }
        @media (max-width: 768px) {
            .draggable {
                pointer-events: none; /* Отключаем drag-and-drop на мобильных */
            }
        }
    </style>
    <link href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css" rel="stylesheet">
@endsection

@section('content')
    <h2 class="text-3xl font-bold text-gray-800 mb-4 text-center">Расписание для группы: {{ $group->name }}</h2>
    <p class="text-center text-gray-600 mb-6">Семестр: {{ $semester }}, Неделя: {{ $week }}</p>

    @if (session('success'))
        <div class="alert-success p-4 rounded-lg shadow-md text-center mb-6">
            {{ session('success') }}
        </div>
    @endif
    @if (session('warnings'))
        <div class="alert-warning p-4 rounded-lg shadow-md text-center mb-6">
            @foreach (session('warnings') as $warning)
                <p>{{ $warning }}</p>
            @endforeach
        </div>
    @endif

    <div class="bg-white p-6 rounded-lg shadow-lg mb-6 hover-scale">
        <form method="GET" action="{{ route('schedule') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label for="group_id" class="block text-sm font-medium text-gray-700">Группа:</label>
                <select name="group_id" id="group_id" class="mt-1 block w-full p-2 border rounded focus:ring-2 focus:ring-blue-500">
                    @foreach($groups as $g)
                        <option value="{{ $g->id }}" {{ $g->id == $group->id ? 'selected' : '' }}>{{ $g->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="semester" class="block text-sm font-medium text-gray-700">Семестр:</label>
                <select name="semester" id="semester" class="mt-1 block w-full p-2 border rounded focus:ring-2 focus:ring-blue-500">
                    <option value="3" {{ $semester == 3 ? 'selected' : '' }}>3-й семестр</option>
                    <option value="4" {{ $semester == 4 ? 'selected' : '' }}>4-й семестр</option>
                </select>
            </div>
            <div>
                <label for="week" class="block text-sm font-medium text-gray-700">Неделя:</label>
                <input type="number" name="week" id="week" class="mt-1 block w-full p-2 border rounded focus:ring-2 focus:ring-blue-500" value="{{ $week }}" min="1" required>
            </div>
            <div class="md:col-span-3 text-center mt-4">
                <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition">Показать расписание</button>
            </div>
        </form>
    </div>

    @if(empty($schedule['monday']) && empty($schedule['tuesday']) && empty($schedule['wednesday']) && empty($schedule['thursday']) && empty($schedule['friday']))
        <div class="alert-warning p-4 rounded-lg shadow-md text-center">
            Расписание не сгенерировано. Проверьте наличие данных.
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full table-auto border-collapse">
                <thead>
                    <tr class="table-header">
                        <th class="p-3">Пара</th>
                        <th class="p-3">Время</th>
                        <th class="p-3">Понедельник</th>
                        <th class="p-3">Вторник</th>
                        <th class="p-3">Среда</th>
                        <th class="p-3">Четверг</th>
                        <th class="p-3">Пятница</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="class-hour">
                        <td class="p-3 border">-</td>
                        <td class="p-3 border">{{ $bellSchedule['monday']['class_hour']['start'] }}–{{ $bellSchedule['monday']['class_hour']['end'] }}</td>
                        <td class="p-3 border">Классный час</td>
                        <td colspan="4" class="p-3 border"></td>
                    </tr>
                    @for($pair = 1; $pair <= 7; $pair++)
                        <tr class="hover:bg-gray-50">
                            <td class="p-3 border">{{ $pair }}</td>
                            <td class="p-3 border">
                                {{ $bellSchedule['other_days'][$pair]['start'] }}–
                                {{ $bellSchedule['other_days'][$pair]['end'] }}
                                @if($pair == 1)
                                    <br><small>(Пн: {{ $bellSchedule['monday'][$pair]['start'] }}–{{ $bellSchedule['monday'][$pair]['end'] }})</small>
                                @endif
                            </td>
                            @foreach(['monday', 'tuesday', 'wednesday', 'thursday', 'friday'] as $day)
                                <td class="p-3 border {{ isset($schedule[$day][$pair]['module_index']) && strpos($schedule[$day][$pair]['module_index'], 'ПМ') === 0 ? 'pm-lesson' : '' }} {{ isset($schedule[$day][$pair]) ? 'draggable' : '' }}"
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
                                        <strong>{{ $schedule[$day][$pair]['discipline_name'] }}</strong><br>
                                        {{ $schedule[$day][$pair]['teacher_name'] }}<br>
                                        <small>
                                            {{ $schedule[$day][$pair]['type'] == 'theoretical' ? 'Теория' : ($schedule[$day][$pair]['type'] == 'lab_practical' ? 'ЛПР' : 'КР/КП') }}
                                        </small>
                                        <br>
                                        <a href="{{ route('schedule.edit', $schedule[$day][$pair]['id']) }}" class="text-blue-600 hover:underline">Редактировать</a>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endfor
                </tbody>
            </table>
        </div>
    @endif
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
