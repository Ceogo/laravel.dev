<?php

namespace App\Http\Controllers\V1;

use App\Models\Group;
use App\Models\Cabinet;
use App\Models\Schedule;
use App\Models\LessonLine;
use Illuminate\Http\Request;
use App\Models\LearningOutcome;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

class ApiSchedule extends Controller
{
    /**
     * Генерирует расписание для группы на указанную неделю и семестр.
     *
     * Этот метод выполняет следующие шаги:
     * 1. Автоматически заносит пары с нагрузкой 1 час в неделю в таблицу LessonLine.
     * 2. Получает обычные еженедельные пары (не в LessonLine).
     * 3. Получает пары из LessonLine с is_processed = false.
     * 4. Распределяет пары по дням недели.
     * 5. Назначает пары на каждый день, сначала обычные, затем из LessonLine.
     * 6. Обновляет статусы LessonLine для следующей недели.
     *
     * @param Group $group Группа, для которой генерируется расписание.
     * @param int $semester Семестр, для которого генерируется расписание.
     * @param int $week Номер недели.
     * @param array $bellSchedule Расписание звонков (не используется).
     * @return array Сгенерированное расписание.
     */
    private function generateSchedule($group, $semester, $week, $bellSchedule)
    {
        $this->ensureLessonLinesForGroup($group, $semester);

        $schedule = [
            'monday' => [],
            'tuesday' => [],
            'wednesday' => [],
            'thursday' => [],
            'friday' => [],
        ];

        $regularLessons = $this->getRegularLessons($group, $semester);
        $lessonLines = LessonLine::where('group_id', $group->id)
                                ->where('is_processed', false)
                                ->get();

        $days = array_keys($schedule);
        $totalPairs = count($regularLessons) + count($lessonLines);
        $pairsPerDay = $this->distributePairs($totalPairs, count($days));

        foreach ($days as $index => $day) {
            $pairsCount = $pairsPerDay[$index];
            $pairNumber = 1;

            foreach ($regularLessons as $key => $lesson) {
                if ($pairNumber > $pairsCount) break;

                $cabinet = $this->getAvailableCabinet($day, $pairNumber, $week, $semester);
                if ($cabinet) {
                    $scheduleItem = Schedule::create([
                        'group_id' => $group->id,
                        'learning_outcome_id' => $lesson->id,
                        'day' => $day,
                        'pair_number' => $pairNumber,
                        'type' => $lesson->type ?? 'lecture', // Предполагаем тип, если отсутствует
                        'week' => $week,
                        'semester' => $semester,
                        'cabinet_id' => $cabinet->id,
                        'teacher_id' => null, // Учитель не указан, так как это для фронтенда
                    ]);

                    $schedule[$day][$pairNumber] = [
                        'module_index' => $lesson->index,
                        'discipline_name' => $lesson->discipline_name,
                        'type' => $lesson->type ?? 'lecture',
                        'cabinet_number' => $cabinet->number,
                        'id' => $scheduleItem->id,
                    ];

                    unset($regularLessons[$key]);
                    $pairNumber++;
                }
            }

            foreach ($lessonLines as $key => $lessonLine) {
                if ($pairNumber > $pairsCount) break;

                $lesson = $this->getLessonDetails($lessonLine->learning_outcome_id);
                $cabinet = $this->getAvailableCabinet($day, $pairNumber, $week, $semester);
                if ($cabinet) {
                    $scheduleItem = Schedule::create([
                        'group_id' => $group->id,
                        'learning_outcome_id' => $lesson->id,
                        'day' => $day,
                        'pair_number' => $pairNumber,
                        'type' => $lesson->type ?? 'lecture',
                        'week' => $week,
                        'semester' => $semester,
                        'cabinet_id' => $cabinet->id,
                        'teacher_id' => null,
                    ]);

                    $schedule[$day][$pairNumber] = [
                        'module_index' => $lesson->index,
                        'discipline_name' => $lesson->discipline_name,
                        'type' => $lesson->type ?? 'lecture',
                        'cabinet_number' => $cabinet->number,
                        'id' => $scheduleItem->id,
                    ];

                    $lessonLine->is_processed = true;
                    $lessonLine->save();

                    unset($lessonLines[$key]);
                    $pairNumber++;
                }
            }
        }

        $this->updateLessonLineStatuses($group);

        return $schedule;
    }

    /**
     * Автоматически заносит пары с нагрузкой 1 час в неделю в LessonLine.
     *
     * @param Group $group Группа.
     * @param int $semester Семестр.
     */
    private function ensureLessonLinesForGroup($group, $semester)
    {
        $learningOutcomes = LearningOutcome::whereHas('semesterDetails', function ($query) use ($semester) {
                                              $query->where('semester_number', $semester)
                                                    ->where('hours_per_week', 1);
                                          })
                                          ->whereHas('module', function ($query) use ($group) {
                                              $query->where('group_id', $group->id);
                                          })
                                          ->get();

        foreach ($learningOutcomes as $outcome) {
            $exists = LessonLine::where('learning_outcome_id', $outcome->id)
                               ->where('group_id', $group->id)
                               ->exists();
            if (!$exists) {
                LessonLine::create([
                    'learning_outcome_id' => $outcome->id,
                    'group_id' => $group->id,
                    'is_processed' => false,
                ]);
            }
        }
    }

    /**
     * Получает обычные еженедельные пары (не в LessonLine).
     *
     * @param Group $group Группа.
     * @param int $semester Семестр.
     * @return \Illuminate\Support\Collection
     */
    private function getRegularLessons($group, $semester)
    {
        $lessonLineIds = LessonLine::where('group_id', $group->id)
                                  ->pluck('learning_outcome_id');
        return LearningOutcome::whereNotIn('id', $lessonLineIds)
                              ->whereHas('semesterDetails', function ($query) use ($semester) {
                                  $query->where('semester_number', $semester)
                                        ->where('hours_per_week', '>', 1); // Исключаем 1 час/неделю
                              })
                              ->whereHas('module', function ($query) use ($group) {
                                  $query->where('group_id', $group->id);
                              })
                              ->get();
    }

    /**
     * Получает детали учебного результата.
     *
     * @param int $learningOutcomeId ID учебного результата.
     * @return LearningOutcome
     */
    private function getLessonDetails($learningOutcomeId)
    {
        return LearningOutcome::findOrFail($learningOutcomeId);
    }

    /**
     * Распределяет пары по дням недели.
     *
     * @param int $totalPairs Общее количество пар.
     * @param int $daysCount Количество дней.
     * @return array Распределение пар по дням.
     */
    private function distributePairs($totalPairs, $daysCount)
    {
        $base = floor($totalPairs / $daysCount);
        $remainder = $totalPairs % $daysCount;
        $distribution = array_fill(0, $daysCount, $base);
        for ($i = 0; $i < $remainder; $i++) {
            $distribution[$i]++;
        }
        dd($distribution);
        return $distribution;
    }

    /**
     * Получает свободный кабинет на указанный день, пару, неделю и семестр.
     *
     * @param string $day День недели.
     * @param int $pairNumber Номер пары.
     * @param int $week Номер недели.
     * @param int $semester Семестр.
     * @return Cabinet|null
     */
    private function getAvailableCabinet($day, $pairNumber, $week, $semester)
    {
        $occupied = Schedule::where('day', $day)
                            ->where('pair_number', $pairNumber)
                            ->where('week', $week)
                            ->where('semester', $semester)
                            ->pluck('cabinet_id');
        return Cabinet::whereNotIn('id', $occupied)->first();
    }

    /**
     * Обновляет статусы LessonLine для следующей недели.
     *
     * @param Group $group Группа.
     */
    private function updateLessonLineStatuses($group)
    {
        $lessonLines = LessonLine::where('group_id', $group->id)->get();
        foreach ($lessonLines as $lessonLine) {
            if ($lessonLine->is_processed) {
                $lessonLine->is_processed = false;
                $lessonLine->save();
            }
        }
    }

    /**
     * Публичный метод для вызова генерации расписания.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function generate(Request $request)
    {
        $request->validate([
            'group_id' => 'required|integer|exists:groups,id',
            'semester' => 'required|integer|min:1',
            'week' => 'required|integer|min:1',
        ]);

        $group = Group::findOrFail($request->group_id);

        if ($group instanceof \Illuminate\Database\Eloquent\Collection) {
            Log::error('Expected Group object, got Collection', ['group_id' => $request->group_id]);
            return response()->json(['error' => 'Invalid group data'], 400);
        }

        $semester = $request->semester;
        $week = $request->week;
        $bellSchedule = [];

        try {
            $schedule = $this->generateSchedule($group, $semester, $week, $bellSchedule);
            return response()->json($schedule);
        } catch (\Exception $e) {
            Log::error('Error generating schedule', ['error' => $e->getMessage(), 'group_id' => $request->group_id]);
            return response()->json(['error' => 'Failed to generate schedule'], 500);
        }
    }
}
