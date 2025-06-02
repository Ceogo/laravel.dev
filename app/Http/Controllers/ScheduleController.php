<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Group;
use App\Models\Cabinet;
use App\Models\Schedule;
use App\Models\LessonLine;
use App\ScheduleGenerator;
use Illuminate\Http\Request;
use App\Models\SemesterDetail;
use App\Models\LearningOutcome;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ScheduleController extends Controller
{
    public function show(Request $request)
    {
        $groupId = $request->input('group_id');
        $semester = $request->input('semester', 3);
        $week = $request->input('week', 1);

        if (!$groupId) {
            $groups = Group::all();
            return view('schedule.select', compact('groups', 'semester', 'week'));
        }

        $group = Group::findOrFail($groupId);
        $bellSchedule = $this->getBellSchedule();

        $schedule = Schedule::with(['learningOutcome.module', 'cabinet']) // Загружаем связанные модели
            ->where('group_id', $groupId)
            ->where('semester', $semester)
            ->where('week', $week)
            ->get()
            ->groupBy('day')
            ->map(function ($daySchedules) {
                return $daySchedules->mapWithKeys(function ($item) {
                    return [$item->pair_number => [
                        'module_index' => optional(optional($item->learningOutcome)->module)->index ?? 'Не указан модуль',
                        'discipline_name' => optional($item->learningOutcome)->discipline_name ?? 'Не указана дисциплина',
                        'teacher_name' => $item->learningOutcome->teacher_name ?? 'вакансия',
                        'type' => $item->type,
                        'cabinet_number' => optional($item->cabinet)->number,
                        'id' => $item->id,
                    ]];
                })->all();
            })
            ->toArray();

        if (empty($schedule)) {
            $generator = new ScheduleGenerator();
            $schedule = $generator->generateForWeek($group, $semester, $week);
        }

        $groups = Group::all();
        return view('schedule.index', compact('schedule', 'group', 'groups', 'semester', 'week', 'bellSchedule'));
    }

    // private function prepareDayLessons($lessons)
    // {
    //     shuffle($lessons);
    //     $result = [];
    //     $pairNumber = 1;
    //     foreach ($lessons as $lesson) {
    //         $result[$pairNumber++] = $lesson;
    //         if ($pairNumber > 4) break;
    //     }
    //     return $result;
    // }

    private function getBellSchedule()
    {
        return [
            'monday' => [
                'class_hour' => ['start' => '08:15', 'end' => '08:35'],
                1 => ['start' => '08:40', 'end' => '10:00'],
                2 => ['start' => '10:10', 'end' => '11:30'],
                3 => ['start' => '12:15', 'end' => '13:35'],
                4 => ['start' => '13:40', 'end' => '15:00'],
                5 => ['start' => '15:10', 'end' => '16:30'],
                6 => ['start' => '16:40', 'end' => '18:00'],
                7 => ['start' => '18:05', 'end' => '19:25'],
            ],
            'other_days' => [
                1 => ['start' => '08:15', 'end' => '09:35'],
                2 => ['start' => '09:45', 'end' => '11:05'],
                3 => ['start' => '11:50', 'end' => '13:10'],
                4 => ['start' => '13:15', 'end' => '14:35'],
                5 => ['start' => '14:45', 'end' => '16:05'],
                6 => ['start' => '16:15', 'end' => '17:35'],
                7 => ['start' => '17:40', 'end' => '19:00'],
            ],
        ];
    }

    // private function generateSchedule($group, $semester, $week, $bellSchedule)
    // {
    //     $schedule = [
    //         'monday' => [], 'tuesday' => [], 'wednesday' => [],
    //         'thursday' => [], 'friday' => []
    //     ];

    //     $lessons = $this->getLessons($group, $semester);
    //     $days = array_keys($schedule);

    //     foreach ($days as $day) {
    //         $dayLessons = $this->prepareDayLessons($lessons);

    //         foreach ($dayLessons as $pairNumber => $lesson) {
    //             // Проверка доступности преподавателя
    //             if (!$this->isTeacherAvailable($lesson['teacher_name'], $day, $pairNumber, $week, $semester)) {
    //                 continue;
    //             }

    //             // Получение подходящих кабинетов
    //             $suitableCabinets = $this->findSuitableCabinets($lesson, $group->id);

    //             if ($suitableCabinets->isEmpty()) {
    //                 $this->logManualOverrideNeeded($lesson, $group);
    //                 continue;
    //             }

    //             // Выбор оптимального кабинета
    //             $selectedCabinet = $this->selectOptimalCabinet(
    //                 $suitableCabinets,
    //                 $lesson['teacher_name']
    //             );

    //             // Создание записи расписания
    //             $scheduleItem = Schedule::create([
    //                 'group_id' => $group->id,
    //                 'learning_outcome_id' => $lesson['learning_outcome_id'],
    //                 'day' => $day,
    //                 'pair_number' => $pairNumber,
    //                 'type' => $lesson['type'],
    //                 'week' => $week,
    //                 'semester' => $semester,
    //                 'cabinet_id' => $selectedCabinet->id
    //             ]);

    //             // Обновление данных расписания
    //             $schedule[$day][$pairNumber] = [
    //                 'module_index' => optional(optional($scheduleItem->learningOutcome)->module)->index ?? 'Не указан модуль',
    //                 'discipline_name' => optional($scheduleItem->learningOutcome)->discipline_name ?? 'Не указана дисциплина',
    //                 'teacher_name' => optional(optional($scheduleItem->learningOutcome)->teacher_name) ?? 'вакансия',
    //                 'type' => $scheduleItem->type,
    //                 'cabinet_number' => optional($scheduleItem->cabinet)->number,
    //                 'id' => $scheduleItem->id,
    //             ];
    //         }
    //     }

    //     $this->processLessonLines($group->id, $semester, $week);
    //     return $schedule;
    // }

    // private function getLessons($group, $semester)
    // {
    //     $lessons = [];

    //     foreach ($group->modules as $module) {
    //         foreach ($module->learningOutcomes as $lo) {
    //             $semesterDetail = $lo->semesterDetails()
    //                 ->where('semester_number', $semester)
    //                 ->first();

    //             if ($semesterDetail && $semesterDetail->total_hours > 0) {
    //                 // Исправленная обработка JSON-поля
    //                 $exams = is_string($semesterDetail->exams)
    //                 ? json_decode($semesterDetail->exams, true)
    //                 : [];

    //                 $lessons[] = [
    //                     'learning_outcome_id' => $lo->id,
    //                     'module_index' => $module->index,
    //                     'discipline_name' => $lo->discipline_name,
    //                     'teacher_name' => $lo->teacher_name ?? 'вакансия',
    //                     'type' => 'theoretical',
    //                     'hours_per_week' => $semesterDetail->hours_per_week,
    //                     'exams' => $exams,
    //                 ];
    //             }
    //         }
    //     }

    //     return $lessons;
    // }

    // private function isTeacherAvailable($teacherName, $day, $pairNumber, $week, $semester)
    // {
    //     // Обработка случая null или "вакансия"
    //     if (!$teacherName || $teacherName === 'вакансия') {
    //         return true;
    //     }

    //     return !Schedule::where('week', $week)
    //         ->where('semester', $semester)
    //         ->where('day', $day)
    //         ->where('pair_number', $pairNumber)
    //         ->whereHas('learningOutcome', function ($query) use ($teacherName) {
    //             if (is_array($teacherName)) {
    //                 $query->whereIn('teacher_name', $teacherName);
    //             } else {
    //                 $query->where('teacher_name', $teacherName);
    //             }
    //         })
    //         ->exists();
    // }

    // private function findSuitableCabinets($lesson, $groupId)
    // {
    //     $baseQuery = Cabinet::query()
    //         ->whereHas('learningOutcomes', function ($q) use ($lesson) {
    //             $q->where('learning_outcome_id', $lesson['learning_outcome_id']);
    //         });

    //     // Используем существующее поле `students_count`
    //     $group = Group::find($groupId);
    //     $groupSize = $group ? $group->students_count : 0;

    //     $suitableCabinets = $baseQuery->where('capacity', '>=', $groupSize)->get();

    //     if ($suitableCabinets->isEmpty()) {
    //         return Cabinet::whereHas('learningOutcomes', function ($q) use ($lesson) {
    //             $q->where('learning_outcome_id', $lesson['learning_outcome_id']);
    //         })->get();
    //     }
    //     return $suitableCabinets;
    // }

    // private function selectOptimalCabinet($cabinets, $teacherName)
    // {
    //     $teacher = User::where('name', $teacherName)->first();

    //     if ($teacher && $preferred = $teacher->preferredCabinets->first()) {
    //         return $cabinets->firstWhere('id', $preferred->id) ?? $cabinets->first();
    //     }

    //     return $cabinets->first();
    // }

    // private function processLessonLines($groupId, $semester, $week)
    // {
    //     $learningOutcomes = LearningOutcome::whereHas('semesterDetails', function ($q) use ($semester) {
    //         $q->where('semester_number', $semester)
    //         ->where('hours_per_week', 1);
    //     })->get();

    //     foreach ($learningOutcomes as $lo) {
    //         // Обновляем существующую запись или создаем новую
    //         LessonLine::updateOrCreate(
    //             [
    //                 'learning_outcome_id' => $lo->id,
    //                 'group_id' => $groupId,
    //                 'target_week' => $week
    //             ],
    //             [
    //                 'is_processed' => true,
    //                 'processed_at' => now()
    //             ]
    //         );
    //     }
    // }

    private function logManualOverrideNeeded($lesson, $group)
    {
        Log::warning("Группа {$group->name} не может быть назначена на РО {$lesson['discipline_name']} из-за несоответствия кабинета");
    }

    private function checkScheduleConflicts($schedule, $semester, $week)
    {
        $conflicts = [];

        $teacherSchedules = Schedule::where('semester', $semester)
            ->where('week', $week)
            ->with('learningOutcome')
            ->get()
            ->groupBy(function ($item) {
                return $item->learningOutcome->teacher_name . '_' . $item->day . '_' . $item->pair_number;
            });

        foreach ($teacherSchedules as $key => $group) {
            if ($group->count() > 1) {
                $conflicts[] = "Преподаватель {$group->first()->learningOutcome->teacher_name} ведет пары одновременно у нескольких групп в {$group->first()->day} на {$group->first()->pair_number}-й паре.";
            }
        }

        foreach ($schedule as $day => $pairs) {
            $pairNumbers = array_keys($pairs);
            sort($pairNumbers);
            $lastPair = 0;

            foreach ($pairNumbers as $pairNumber) {
                if ($lastPair !== 0 && $pairNumber - $lastPair > 1) {
                    $conflicts[] = "Обнаружено окно между {$lastPair} и {$pairNumber} на {$day}";
                }
                $lastPair = $pairNumber;
            }
        }

        return $conflicts;
    }

    private function validateScheduleSequence($schedule)
    {
        $conflicts = [];

        foreach ($schedule as $day => $pairs) {
            $pairNumbers = array_keys($pairs);
            sort($pairNumbers);
            $lastPair = 0;

            foreach ($pairNumbers as $pairNumber) {
                if ($lastPair !== 0 && $pairNumber - $lastPair > 1) {
                    $conflicts[] = "Обнаружено окно между {$lastPair} и {$pairNumber} на {$day}";
                }
                $lastPair = $pairNumber;
            }
        }

        return $conflicts;
    }

    public function forceAssignCabinet(Request $request, $scheduleId)
    {
        $validator = Validator::make($request->all(), [
            'cabinet_id' => 'required|exists:cabinets,id',
            'force' => 'boolean'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        $schedule = Schedule::findOrFail($scheduleId);
        $cabinet = Cabinet::find($request->cabinet_id);

        if (!$cabinet->getAvailableCapacity($schedule->group_id) && !$request->force) {
            return back()->withErrors(['capacity' => 'Вместимость кабинета недостаточна для группы']);
        }

        $schedule->update(['cabinet_id' => $cabinet->id]);
        return back()->with('success', 'Кабинет успешно назначен');
    }
}
