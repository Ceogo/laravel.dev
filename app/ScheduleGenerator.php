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
    /**
     * Create a new class instance.
     */
    private array $dailyDistribution = [3, 3, 4, 4, 4]; // Распределение пар по дням
    private array $weekDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];

    public function generateForWeek(Group $group, int $semester, int $week): array
    {
        // Сбор всех пар на неделю
        $weeklyLessons = $this->collectWeeklyLessons($group, $semester ,$week);

        // Распределение пар по дням
        $dailySchedules = $this->distributeToDays($weeklyLessons, $group, $semester, $week);

        // Проверка и валидация расписания
        $this->validateSchedule($dailySchedules, $group, $semester, $week);

        return $dailySchedules;
    }

    private function collectWeeklyLessons(Group $group, int $semester, int $week): array
    {
        $lessons = [];

        // Сбор регулярных пар
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

    private function distributeToDays(array $lessons, Group $group, int $semester, int $week): array
    {
        $schedule = array_fill_keys($this->weekDays, []);
        $pmCount = 0; // Счетчик ПМ пар
        $peCount = 0; // Счетчик физкультур

        // Сортировка пар по приоритету
        usort($lessons, function ($a, $b) {
            // Физкультура имеет наивысший приоритет
            if ($a['module_index'] === 'БМ 1.1' && $b['module_index'] !== 'БМ 1.1') return -1;
            if ($b['module_index'] === 'БМ 1.1' && $a['module_index'] !== 'БМ 1.1') return 1;

            // Затем профессиональные модули
            if (str_contains($a['module_index'], 'ПМ') && !str_contains($b['module_index'], 'ПМ')) return -1;
            if (!str_contains($a['module_index'], 'ПМ') && str_contains($b['module_index'], 'ПМ')) return 1;

            return 0;
        });

        // Распределение пар по дням
        foreach ($this->weekDays as $dayIndex => $day) {
            $dayLessons = [];
            $maxPairs = $this->dailyDistribution[$dayIndex];
            $maxPmPairs = floor($maxPairs * 0.5);

            foreach ($lessons as $key => $lesson) {
                // Проверка ограничений
                if (count($dayLessons) >= $maxPairs) break;

                // Проверка для ПМ
                if (str_contains($lesson['module_index'], 'ПМ') && $pmCount >= $maxPmPairs) continue;

                // Проверка для физкультуры
                if ($lesson['module_index'] === 'БМ 1.1' && $peCount >= 2) continue;

                // Проверка доступности преподавателя
                if (!$this->isTeacherAvailable($lesson['teacher_name'], $day, count($dayLessons) + 1, $week, $semester)) {
                    continue;
                }

                // Поиск подходящих кабинетов
                $suitableCabinets = $this->findSuitableCabinets($lesson, $group->id);
                if ($suitableCabinets->isEmpty()) {
                    Log::warning("Нет подходящих кабинетов для {$lesson['discipline_name']} в группе {$group->name}");
                    continue;
                }

                // Выбор кабинета
                $selectedCabinet = $this->selectOptimalCabinet($suitableCabinets, $lesson['teacher_name']);

                // Создание записи
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
                if (str_contains($lesson['module_index'], 'ПМ')) $pmCount++;
                if ($lesson['module_index'] === 'БМ 1.1') $peCount++;

                // Удаление использованной пары
                unset($lessons[$key]);
            }

            $schedule[$day] = $dayLessons;
        }

        // Обработка одноразовых пар
        $this->processOneTimeLessons($group, $semester, $week);

        return $schedule;
    }

    private function findSuitableCabinets(array $lesson, int $groupId): \Illuminate\Support\Collection
    {
        $query = Cabinet::query()
            ->whereHas('learningOutcomes', function ($q) use ($lesson) {
                $q->where('learning_outcome_id', $lesson['learning_outcome_id']);
            });

        // Проверка вместимости
        $groupSize = Group::find($groupId)->students_count ?? 0;
        $suitableCabinets = $query->where('capacity', '>=', $groupSize)->get();

        return $suitableCabinets->isEmpty() ? $query->get() : $suitableCabinets;
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
        if ($teacherName === 'вакансия') return true;

        return !Schedule::where('week', $week)
            ->where('semester', $semester)
            ->where('day', $day)
            ->where('pair_number', $pairNumber)
            ->whereHas('learningOutcome', function ($query) use ($teacherName) {
                $query->where('teacher_name', $teacherName);
            })
            ->exists();
    }

    private function processOneTimeLessons(Group $group, int $semester, int $week): void
    {
        $learningOutcomes = LearningOutcome::whereHas('semesterDetails', function ($query) use ($semester) {
            $query->where('semester_number', $semester)
                ->where('hours_per_week', 1);
        })->get();

        foreach ($learningOutcomes as $outcome) {
            $line = LessonLine::firstOrCreate([
                'learning_outcome_id' => $outcome->id,
                'group_id' => $group->id,
                'target_week' => $week
            ]);

            $line->update(['is_processed' => true]);
        }
    }

    private function validateSchedule(array $schedule, Group $group, int $semester, int $week): void
    {
        $totalPairs = array_sum(array_map('count', $schedule));

        // Проверка общего количества пар
        if ($totalPairs < 18 || $totalPairs > 20) {
            throw new \Exception("Неверное количество пар за неделю: $totalPairs");
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

        // Проверка интервалов между парами
        foreach ($schedule as $day => $pairs) {
            $pairNumbers = array_keys($pairs);
            sort($pairNumbers);

            $lastPair = 0;
            foreach ($pairNumbers as $pairNumber) {
                if ($lastPair !== 0 && $pairNumber - $lastPair > 1) {
                    Log::warning("Пробел между парами $lastPair и $pairNumber в $day для группы {$group->name}");
                }
                $lastPair = $pairNumber;
            }
        }
    }
}
