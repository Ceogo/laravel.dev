@extends('layouts.admin')

@section('title', 'Учебный план')

@section('styles')
    <style>
        .table-header {
            background: linear-gradient(to right, #4b6cb7, #182848);
            color: white;
        }
        .table-row:nth-child(even) {
            background-color: #f9fafb;
        }
        .alert-success {
            background-color: #d1fae5;
            color: #065f46;
        }
    </style>
@endsection

@section('content')
    <h2 class="text-3xl font-bold text-gray-800 mb-6 text-center">Учебный план</h2>
    @if (session('success'))
        <div class="alert-success p-4 rounded-lg mb-6 shadow-md text-center">
            {{ session('success') }}
        </div>
    @endif
    @foreach($groups as $group)
        <div class="bg-white p-6 rounded-lg shadow-lg mb-8 hover-scale">
            <h3 class="text-2xl font-semibold text-gray-700">Группа: {{ $group->name }} ({{ $group->specialty_code ?? 'Не указан' }})</h3>
            <p class="text-gray-600">Специальность: {{ $group->specialty_name ?? 'Не указана' }}</p>
            <p class="text-gray-600">Количество обучающихся: {{ $group->students_count ?? 'Не указано' }}</p>
            <div class="overflow-x-auto mt-4">
                <table class="w-full table-auto border-collapse">
                    <thead>
                        <tr class="table-header">
                            @foreach($headers as $header)
                                @if(is_array($header))
                                    <th colspan="{{ $headerCounts[$header['title']] ?? 1 }}" class="p-3 text-sm">{{ $header['title'] }}</th>
                                @else
                                    <th rowspan="2" class="p-3 text-sm">{{ $header }}</th>
                                @endif
                            @endforeach
                        </tr>
                        <tr class="table-header">
                            @foreach($headers as $header)
                                @if(is_array($header))
                                    @foreach($header['children'] as $child)
                                        @if(is_array($child))
                                            @foreach($child['children'] as $subChild)
                                                <th class="p-3 text-sm">{{ $subChild }}</th>
                                            @endforeach
                                        @else
                                            <th class="p-3 text-sm">{{ $child }}</th>
                                        @endif
                                    @endforeach
                                @endif
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($group->modules as $module)
                            <tr class="table-row">
                                <td class="p-3 border">{{ $module->index }}</td>
                                <td class="p-3 border">{{ $module->name }}</td>
                                <td colspan="{{ count($flattenedHeaders) - 2 }}" class="p-3 border"></td>
                            </tr>
                            @foreach($module->learningOutcomes as $lo)
                                <tr class="table-row">
                                    <td class="p-3 border">{{ $lo->index }}</td>
                                    <td class="p-3 border">{{ $lo->discipline_name }}</td>
                                    <td class="p-3 border">{{ $lo->teacher_name ?? 'вакансия' }}</td>
                                    <td class="p-3 border">
                                        @if(is_array($lo->semesterDistribution->exams))
                                            {{ implode(', ', $lo->semesterDistribution->exams) }}
                                        @else
                                            {{ $lo->semesterDistribution->exams ?? '-' }}
                                        @endif
                                    </td>
                                    <td class="p-3 border">
                                        @if(is_array($lo->semesterDistribution->credits))
                                            {{ implode(', ', $lo->semesterDistribution->credits) }}
                                        @else
                                            {{ $lo->semesterDistribution->credits ?? '-' }}
                                        @endif
                                    </td>
                                    <td class="p-3 border">
                                        @if(is_array($lo->semesterDistribution->course_works))
                                            {{ implode(', ', $lo->semesterDistribution->course_works) }}
                                        @else
                                            {{ $lo->semesterDistribution->course_works ?? '-' }}
                                        @endif
                                    </td>
                                    <td class="p-3 border">
                                        @if(is_array($lo->semesterDistribution->control_works))
                                            {{ implode(', ', $lo->semesterDistribution->control_works) }}
                                        @else
                                            {{ $lo->semesterDistribution->control_works ?? '-' }}
                                        @endif
                                    </td>
                                    <td class="p-3 border">{{ $lo->rupDetail->credits ?? '-' }}</td>
                                    <td class="p-3 border">{{ $lo->rupDetail->total_hours ?? '-' }}</td>
                                    <td class="p-3 border">{{ $lo->rupDetail->theoretical_hours ?? '-' }}</td>
                                    <td class="p-3 border">{{ $lo->rupDetail->lab_practical_hours ?? '-' }}</td>
                                    <td class="p-3 border">{{ $lo->rupDetail->course_works ?? '-' }}</td>
                                    <td class="p-3 border">{{ $lo->rupDetail->professional_practice ?? '-' }}</td>
                                    <td class="p-3 border">{{ $lo->academicYearDetail->total_hours ?? '-' }}</td>
                                    <td class="p-3 border">{{ $lo->academicYearDetail->theoretical_hours ?? '-' }}</td>
                                    <td class="p-3 border">{{ $lo->academicYearDetail->lab_practical_hours ?? '-' }}</td>
                                    <td class="p-3 border">{{ $lo->academicYearDetail->course_works ?? '-' }}</td>
                                    <td class="p-3 border">{{ $lo->academicYearDetail->professional_training ?? '-' }}</td>
                                    @foreach($lo->semesterDetails as $semesterDetail)
                                        @if($semesterDetail->semester_number == 3)
                                            <td class="p-3 border">{{ $semesterDetail->weeks_count ?? '-' }}</td>
                                            <td class="p-3 border">{{ $semesterDetail->hours_per_week ?? '-' }}</td>
                                            <td class="p-3 border">{{ $semesterDetail->total_hours ?? '-' }}</td>
                                            <td class="p-3 border">{{ $semesterDetail->theoretical_hours ?? '-' }}</td>
                                            <td class="p-3 border">{{ $semesterDetail->lab_practical_hours ?? '-' }}</td>
                                            <td class="p-3 border">{{ $semesterDetail->course_projects ?? '-' }}</td>
                                            <td class="p-3 border">{{ $semesterDetail->project_verification ?? '-' }}</td>
                                            <td class="p-3 border">{{ $semesterDetail->professional_training ?? '-' }}</td>
                                            <td class="p-3 border">{{ $semesterDetail->lab_practical_duplication ?? '-' }}</td>
                                            <td class="p-3 border">{{ $semesterDetail->project_duplication ?? '-' }}</td>
                                            <td class="p-3 border">{{ $semesterDetail->verification_duplication ?? '-' }}</td>
                                            <td class="p-3 border">{{ $semesterDetail->consultations ?? '-' }}</td>
                                            <td class="p-3 border">{{ $semesterDetail->exams ?? '-' }}</td>
                                            <td class="p-3 border">{{ $semesterDetail->semester_total ?? '-' }}</td>
                                        @endif
                                    @endforeach
                                    @foreach($lo->semesterDetails as $semesterDetail)
                                        @if($semesterDetail->semester_number == 4)
                                            <td class="p-3 border">{{ $semesterDetail->weeks_count ?? '-' }}</td>
                                            <td class="p-3 border">{{ $semesterDetail->hours_per_week ?? '-' }}</td>
                                            <td class="p-3 border">{{ $semesterDetail->total_hours ?? '-' }}</td>
                                            <td class="p-3 border">{{ $semesterDetail->theoretical_hours ?? '-' }}</td>
                                            <td class="p-3 border">{{ $semesterDetail->lab_practical_hours ?? '-' }}</td>
                                            <td class="p-3 border">{{ $semesterDetail->course_projects ?? '-' }}</td>
                                            <td class="p-3 border">{{ $semesterDetail->project_verification ?? '-' }}</td>
                                            <td class="p-3 border">{{ $semesterDetail->professional_training ?? '-' }}</td>
                                            <td class="p-3 border">{{ $semesterDetail->lab_practical_duplication ?? '-' }}</td>
                                            <td class="p-3 border">{{ $semesterDetail->project_duplication ?? '-' }}</td>
                                            <td class="p-3 border">{{ $semesterDetail->verification_duplication ?? '-' }}</td>
                                            <td class="p-3 border">{{ $semesterDetail->consultations ?? '-' }}</td>
                                            <td class="p-3 border">{{ $semesterDetail->exams ?? '-' }}</td>
                                            <td class="p-3 border">{{ $semesterDetail->semester_total ?? '-' }}</td>
                                        @endif
                                    @endforeach
                                    <td class="p-3 border">{{ $lo->yearTotal->total_hours ?? '-' }}</td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach
@endsection
