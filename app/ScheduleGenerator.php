<?php

namespace App;
use App\Models\Group;
use App\Models\Schedule;
use App\Models\LearningOutcome;
use App\Models\LessonLine;
use App\Models\Cabinet;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ScheduleGenerator
{
    private array $dailyDistribution = [3, 3, 4, 4, 4]; // Распределение пар по дням
    private array $weekDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];

    public function generateForAllGroups(int $semester, int $week): array
    {
        $groups = Group::with(['modules.learningOutcomes'])->get();
        $allSchedules = [];

        foreach ($groups as $group) {
            $allSchedules[$group->id] = $this->generateForGroup($group, $semester, $week);
        }

        return $allSchedules;
    }
    private function generateForGroup(Group $group, int $semester, int $week): array
    {
        $schedule = array_fill_keys($this->weekDays, []);
        $lessons = $this->collectGroupLessons($group, $semester, $week);
        $dayLimits = $this->calculateDayLimits($group);

        // Сортировка по приоритету: БМ 1.1 → ПМ → остальные
        usort($lessons, function ($a, $b) {
            if ($a['module_index'] === 'БМ 1.1') return -1;
            if ($b['module_index'] === 'БМ 1.1') return 1;
            if (str_contains($a['module_index'], 'ПМ')) return -1;
            if (str_contains($b['module_index'], 'ПМ')) return 1;
            return 0;
        });

        // Распределение по дням
        foreach ($this->weekDays as $dayIndex => $day) {
            $dayLessons = [];
            $dayPmCount = 0;
            $dayPeCount = 0;
            $maxPairs = $dayLimits[$dayIndex];

            foreach ($lessons as $key => $lesson) {
                if (count($dayLessons) >= $maxPairs) break;

                // Проверка ПМ
                if (str_contains($lesson['module_index'], 'ПМ') && $dayPmCount >= floor($maxPairs * 0.5)) {
                    continue;
                }

                // Проверка БМ 1.1
                if ($lesson['module_index'] === 'БМ 1.1' && $dayPeCount >= 1) {
                    continue;
                }

                // Проверка доступности преподавателя
                if (!$this->isTeacherAvailable($lesson['teacher_name'], $day, count($dayLessons) + 1, $week, $semester)) {
                    continue;
                }

                // Поиск кабинета
                $suitableCabinets = $this->findSuitableCabinets($lesson, $group->id);
                if ($suitableCabinets->isEmpty()) continue;

                // Добавление пары
                $selectedCabinet = $this->selectOptimalCabinet($suitableCabinets, $lesson['teacher_name']);
                $pairNumber = count($dayLessons) + 1;

                Schedule::updateOrCreate([
                    'group_id' => $group->id,
                    'day' => $day,
                    'pair_number' => $pairNumber,
                    'week' => $week,
                    'semester' => $semester
                ], [
                    'learning_outcome_id' => $lesson['learning_outcome_id'],
                    'cabinet_id' => $selectedCabinet->id
                ]);

                // Обновление счетчиков
                if (str_contains($lesson['module_index'], 'ПМ')) $dayPmCount++;
                if ($lesson['module_index'] === 'БМ 1.1') $dayPeCount++;

                $dayLessons[$pairNumber] = $lesson;
                unset($lessons[$key]);
            }

            $schedule[$day] = $dayLessons;
        }

        return $schedule;
    }

    public function generateForWeek(Group $group, int $semester, int $week): array
    {
        $weeklyLessons = $this->collectWeeklyLessons($group, $semester, $week);
        $dailySchedules = $this->distributeToDays($weeklyLessons, $group, $semester, $week);
        $this->validateSchedule($dailySchedules, $group, $semester, $week);
        return $dailySchedules;
    }

    private function calculateDayLimits(Group $group): array
    {
        $totalHours = $group->modules->sum(fn($m) => $m->learningOutcomes->sum(fn($lo) => $lo->semesterDetails->first()?->hours_per_week ?? 0));

        // Базовое распределение: 3-5 пар в день
        $baseDistribution = [3, 3, 4, 4, 4];

        // Корректировка по вместимости
        $groupSize = $group->students_count ?? 0;
        $cabinetCapacity = Cabinet::whereHas('learningOutcomes', function ($q) use ($group) {
            $q->whereIn('module_id', $group->modules->pluck('id'));
        })->avg('capacity');

        // Если вместимость меньше группы, уменьшаем количество пар
        if ($cabinetCapacity < $groupSize * 0.8) {
            return array_map(fn($d) => max(2, $d - 1), $baseDistribution);
        }

        return $baseDistribution;
    }
    private function collectGroupLessons(Group $group, int $semester, int $week): array
    {
        $lessons = [];

        // Регулярные пары из semester_details
        foreach ($group->modules as $module) {
            foreach ($module->learningOutcomes as $outcome) {
                $semesterDetail = $outcome->semesterDetails()
                    ->where('semester_number', $semester)
                    ->first();

                if ($semesterDetail && $semesterDetail->total_hours > 0) {
                    $lessons[] = [
                        'learning_outcome_id' => $outcome->id,
                        'module_index' => $module->index,
                        'discipline_name' => $outcome->discipline_name,
                        'teacher_name' => $outcome->teacher_name ?? 'вакансия',
                        'type' => 'theoretical',
                        'hours_per_week' => $semesterDetail->hours_per_week,
                    ];
                }
            }
        }

        // Одноразовые пары из lesson_lines
        $oneTimeLessons = LessonLine::where('group_id', $group->id)
            ->whereNull('target_week')
            ->orWhere('target_week', $week)
            ->whereHas('learningOutcome.semesterDetails', function ($query) use ($semester) {
                $query->where('semester_number', $semester)
                    ->where('hours_per_week', 1);
            })
            ->with('learningOutcome.module')
            ->get()
            ->map(function ($line) {
                return [
                    'learning_outcome_id' => $line->learning_outcome_id,
                    'module_index' => optional($line->learningOutcome->module)->index ?? 'Не указан',
                    'discipline_name' => $line->learningOutcome->discipline_name,
                    'teacher_name' => $line->learningOutcome->teacher_name ?? 'вакансия',
                    'type' => 'one_time',
                    'hours_per_week' => 1,
                ];
            })
            ->toArray();

        return array_merge($lessons, $oneTimeLessons);
    }
    private function collectWeeklyLessons(Group $group, int $semester, int $week): array
    {
        $lessons = [];

        // Логирование начала сбора пар
        Log::info("Начинаем сбор пар для группы {$group->id}, семестр $semester, неделя $week");

        // Регулярные пары из semester_details
        foreach ($group->modules as $module) {
            foreach ($module->learningOutcomes as $outcome) {
                $semesterDetail = $outcome->semesterDetails()
                    ->where('semester_number', $semester)
                    ->first();

                if ($semesterDetail && $semesterDetail->total_hours > 0) {
                    Log::info("Добавлена регулярная пара: {$outcome->discipline_name}, часов: {$semesterDetail->hours_per_week}");
                    $lessons[] = [
                        'learning_outcome_id' => $outcome->id,
                        'module_index' => $module->index,
                        'discipline_name' => $outcome->discipline_name,
                        'teacher_name' => $outcome->teacher_name ?? 'вакансия',
                        'type' => 'theoretical',
                        'hours_per_week' => $semesterDetail->hours_per_week,
                    ];
                }
            }
        }

        // Одноразовые пары из lesson_lines
        $oneTimeLessons = LessonLine::where('group_id', $group->id)
            ->whereNull('target_week')
            ->orWhere('target_week', $week)
            ->whereHas('learningOutcome.semesterDetails', function ($query) use ($semester) {
                $query->where('semester_number', $semester)
                    ->where('hours_per_week', 1);
            })
            ->with('learningOutcome.module')
            ->get()
            ->map(function ($line) {
                Log::info("Добавлена одноразовая пара: {$line->learningOutcome->discipline_name}");
                return [
                    'learning_outcome_id' => $line->learning_outcome_id,
                    'module_index' => optional($line->learningOutcome->module)->index ?? 'Не указан',
                    'discipline_name' => $line->learningOutcome->discipline_name,
                    'teacher_name' => $line->learningOutcome->teacher_name ?? 'вакансия',
                    'type' => 'one_time',
                    'hours_per_week' => 1,
                ];
            })
            ->toArray();

        Log::info("Всего пар собрано: " . count(array_merge($lessons, $oneTimeLessons)));
        return array_merge($lessons, $oneTimeLessons);
    }

    private function distributeToDays(array $lessons, Group $group, int $semester, int $week): array
    {
        $schedule = array_fill_keys(['monday', 'tuesday', 'wednesday', 'thursday', 'friday'], []);
        $peCount = 0; // Счетчик физкультур за неделю

        // Сортировка по приоритету: БМ 1.1 → ПМ → остальные
        usort($lessons, function ($a, $b) {
            if ($a['module_index'] === 'БМ 1.1') return -1;
            if ($b['module_index'] === 'БМ 1.1') return 1;
            if (str_contains($a['module_index'], 'ПМ')) return -1;
            if (str_contains($b['module_index'], 'ПМ')) return 1;
            return 0;
        });

        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
        $dailyDistribution = [3, 3, 4, 4, 4]; // Распределение пар по дням

        foreach ($days as $dayIndex => $day) {
            $dayLessons = [];
            $dayPmCount = 0; // Счетчик ПМ-пар для текущего дня
            $dayPeCount = 0; // Счетчик физкультур для текущего дня
            $maxPairs = $dailyDistribution[$dayIndex];

            foreach ($lessons as $key => $lesson) {
                if (count($dayLessons) >= $maxPairs) break;

                // Проверка для ПМ
                if (str_contains($lesson['module_index'], 'ПМ') && $dayPmCount >= 2) {
                    Log::warning("Пропущена ПМ-пара: {$lesson['discipline_name']} (дневной лимит исчерпан)");
                    continue;
                }

                // Проверка для физкультуры
                if ($lesson['module_index'] === 'БМ 1.1' && $peCount >= 2) {
                    Log::warning("Пропущена физкультура: {$lesson['discipline_name']} (недельный лимит исчерпан)");
                    continue;
                }

                // Проверка доступности преподавателя
                if (!$this->isTeacherAvailable($lesson['teacher_name'], $day, count($dayLessons) + 1, $week, $semester)) {
                    Log::warning("Преподаватель недоступен: {$lesson['teacher_name']} на $day, пара " . (count($dayLessons) + 1));
                    continue;
                }

                // Поиск кабинетов
                $suitableCabinets = $this->findSuitableCabinets($lesson, $group->id);
                if ($suitableCabinets->isEmpty()) {
                    Log::warning("Нет подходящих кабинетов для {$lesson['discipline_name']}");
                    continue;
                }

                // Выбор кабинета
                $selectedCabinet = $this->selectOptimalCabinet($suitableCabinets, $lesson['teacher_name']);

                // Добавление пары
                $pairNumber = count($dayLessons) + 1;
                $scheduleItem = Schedule::create([
                    'group_id' => $group->id,
                    'learning_outcome_id' => $lesson['learning_outcome_id'],
                    'day' => $day,
                    'pair_number' => $pairNumber,
                    'type' => $lesson['type'],
                    'week' => $week,
                    'semester' => $semester,
                    'cabinet_id' => $selectedCabinet->id,
                ]);

                // Добавление в расписание
                $dayLessons[$pairNumber] = [
                    'module_index' => $lesson['module_index'],
                    'discipline_name' => $lesson['discipline_name'],
                    'teacher_name' => $lesson['teacher_name'],
                    'type' => $lesson['type'],
                    'cabinet_number' => $selectedCabinet->number,
                    'id' => $scheduleItem->id,
                ];

                // Обновление счетчиков
                if (str_contains($lesson['module_index'], 'ПМ')) $dayPmCount++;
                if ($lesson['module_index'] === 'БМ 1.1') {
                    $dayPeCount++;
                    $peCount++;
                }

                unset($lessons[$key]);
            }

            // Логирование дневного распределения
            Log::info("Распределено пар в $day: " . count($dayLessons));
            Log::info("ПМ-пар в $day: $dayPmCount");
            Log::info("Физкультура в $day: $dayPeCount");

            $schedule[$day] = $dayLessons;
        }

        $this->processOneTimeLessons($group, $semester, $week);
        return $schedule;
    }
    private function findSuitableCabinets(array $lesson, int $groupId): \Illuminate\Support\Collection
    {
        $group = Group::find($groupId);
        $groupSize = $group ? $group->students_count : 0;

        // Логирование вместимости
        Log::info("Вместимость группы $groupId: $groupSize");

        $baseQuery = Cabinet::query()
            ->whereHas('learningOutcomes', function ($q) use ($lesson) {
                $q->where('learning_outcome_id', $lesson['learning_outcome_id']);
            });

        $suitableCabinets = $baseQuery->where('capacity', '>=', $groupSize)->get();

        if ($suitableCabinets->isEmpty()) {
            Log::warning("Нет кабинетов, подходящих по вместимости для {$lesson['discipline_name']}");
            return $baseQuery->get();
        }

        return $suitableCabinets;
    }

    private function selectOptimalCabinet(\Illuminate\Support\Collection $cabinets, string $teacherName): Cabinet
    {
        if ($teacherName === 'вакансия') {
            return $cabinets->first();
        }

        $teacher = User::where('name', $teacherName)->first();
        if (!$teacher) return $cabinets->first();

        // Попытка найти предпочтительный кабинет
        $preferred = $teacher->preferredCabinets->first();
        if ($preferred && $cabinets->contains($preferred->id)) {
            return $cabinets->firstWhere('id', $preferred->id);
        }

        return $cabinets->first();
    }

    private function isTeacherAvailable(string $teacherName, string $day, int $pairNumber, int $week, int $semester): bool
    {
        if ($teacherName === null || $teacherName === 'вакансия') {
            return true;
        }

        $isAvailable = !Schedule::where('week', $week)
            ->where('semester', $semester)
            ->where('day', $day)
            ->where('pair_number', $pairNumber)
            ->whereHas('learningOutcome', function ($query) use ($teacherName) {
                $query->where('teacher_name', $teacherName);
            })
            ->exists();

        if (!$isAvailable) {
            Log::warning("Преподаватель занят: $teacherName на $day, пара $pairNumber");
        }

        return $isAvailable;
    }

    private function processOneTimeLessons(Group $group, int $semester, int $week): void
    {
        $learningOutcomes = LearningOutcome::whereHas('semesterDetails', function ($query) use ($semester) {
            $query->where('semester_number', $semester)
                ->where('hours_per_week', 1);
        })->get();

        foreach ($learningOutcomes as $outcome) {
            $line = LessonLine::updateOrCreate(
                [
                    'learning_outcome_id' => $outcome->id,
                    'group_id' => $group->id,
                    'target_week' => $week
                ],
                ['is_processed' => true]
            );

            if ($line->wasRecentlyCreated) {
                Log::info("Одноразовая пара добавлена: {$outcome->discipline_name}, неделя $week");
            } else {
                Log::info("Одноразовая пара обновлена: {$outcome->discipline_name}, неделя $week");
            }
        }
    }

    private function validateSchedule(array $schedule, Group $group, int $semester, int $week): void
    {
        $totalPairs = array_sum(array_map('count', $schedule));

        if ($totalPairs < 18 || $totalPairs > 20) {
            throw new \Exception("Неверное количество пар за неделю: $totalPairs");
        }

        // Проверка дневного лимита ПМ-пар
        foreach ($schedule as $day => $pairs) {
            $dayPmCount = count(array_filter($pairs, fn($p) => str_contains($p['module_index'], 'ПМ')));
            if ($dayPmCount > 2) {
                throw new \Exception("Превышено количество ПМ-пар в $day: $dayPmCount > 2");
            }
        }

        // Проверка недельного лимита физкультуры
        $peTotal = 0;
        foreach ($schedule as $day => $pairs) {
            $peTotal += count(array_filter($pairs, fn($p) => $p['module_index'] === 'БМ 1.1'));
        }

        if ($peTotal > 2) {
            throw new \Exception("Превышено количество физкультур за неделю: $peTotal > 2");
        }

        // Проверка конфликтов преподавателей
        $teacherConflicts = Schedule::where('semester', $semester)
            ->where('week', $week)
            ->with('learningOutcome')
            ->get()
            ->groupBy(function ($item) {
                return $item->learningOutcome->teacher_name . '_' . $item->day . '_' . $item->pair_number;
            })
            ->filter(fn($group) => $group->count() > 1);

        foreach ($teacherConflicts as $conflict) {
            Log::warning("Конфликт преподавателя: {$conflict[0]->learningOutcome->teacher_name} в {$conflict[0]->day} на паре {$conflict[0]->pair_number}");
        }
    }
}
