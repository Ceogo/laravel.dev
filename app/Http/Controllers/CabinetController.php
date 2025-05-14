<?php

namespace App\Http\Controllers;

use App\Models\Module;
use App\Models\Cabinet;
use Illuminate\Http\Request;
use App\Models\LearningOutcome;
use Illuminate\Support\Facades\Validator;

class CabinetController extends Controller
{
    public function index()
    {
        $cabinets = Cabinet::with(['learningOutcomes', 'preferredTeachers'])->get();
        $learningOutcomes = LearningOutcome::all();
        return view('cabinets.index', compact('cabinets', 'learningOutcomes'));
    }

    public function create()
    {
        // Получаем все модули и группируем их по индексу
        $modules = Module::with('learningOutcomes')->get();
        $groupedModules = $modules->groupBy('index'); // Группировка по индексу
        $learningOutcomes = LearningOutcome::all();

        return view('cabinets.create', compact(
            'learningOutcomes',
            'groupedModules'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'number' => 'required|unique:cabinets',
            'learning_outcome_ids' => 'nullable|array',
            'learning_outcome_ids.*' => 'exists:learning_outcomes,id',
            'module_ids' => 'nullable|array',
            'module_ids.*' => 'exists:modules,id'
        ]);

        $cabinet = Cabinet::create($request->only(['number', 'description', 'capacity']));

        // Инициализация переменной
        $outcomeIds = [];

        // Обработка модулей и их дубликатов
        if ($request->module_ids) {
            foreach ($request->module_ids as $moduleId) {
                $module = Module::findOrFail($moduleId);
                $moduleIds = array_merge([$module->id], $module->getDuplicates()->pluck('id')->toArray());
                $cabinet->modules()->attach(array_unique($moduleIds));

                // Привязка всех РО из модуля и его дубликатов
                foreach ($module->learningOutcomes as $outcome) {
                    $outcomeIds[] = $outcome->id;
                    $outcomeIds = array_merge($outcomeIds, $outcome->getDuplicates()->pluck('id')->toArray());
                }
            }
            $cabinet->learningOutcomes()->attach(array_unique($outcomeIds));
        }

        return redirect()->route('cabinets.index');
    }

    public function edit(Cabinet $cabinet)
    {
        // Группируем модули по индексу
        $groupedModules = Module::with('learningOutcomes')->get()
            ->groupBy('index')
            ->map(function ($modules) {
                return $modules->sortByDesc(fn($m) => $m->id);
            });

        // Получаем связанные РО и группируем по дисциплинам
        $relatedROs = $cabinet->learningOutcomes->groupBy('discipline_name');
        $selectedDisciplines = $relatedROs->flatMap(fn($group) => $group->firstWhere('discipline_name'));

        return view('cabinets.edit', compact('cabinet', 'groupedModules', 'selectedDisciplines'));
    }
    public function update(Request $request, Cabinet $cabinet)
    {
        $validated = $request->validate([
            'number' => 'required|unique:cabinets,number,' . $cabinet->id,
            'learning_outcome_ids' => 'nullable|array',
            'learning_outcome_ids.*' => 'exists:learning_outcomes,id',
            'module_ids' => 'nullable|array',
            'module_ids.*' => 'exists:modules,id'
        ]);

        $cabinet->update($request->only(['number', 'description', 'capacity']));

        // Инициализация переменной
        $outcomeIds = [];

        // Обработка РО
        if ($request->learning_outcome_ids) {
            foreach ($request->learning_outcome_ids as $id) {
                $outcome = LearningOutcome::findOrFail($id);
                $outcomeIds[] = $id;
                $outcomeIds = array_merge($outcomeIds, $outcome->getDuplicates()->pluck('id')->toArray());
            }
        }

        // Обработка модулей
        if ($request->module_ids) {
            foreach ($request->module_ids as $moduleId) {
                $module = Module::findOrFail($moduleId);
                $moduleIds = array_merge([$module->id], $module->getDuplicates()->pluck('id')->toArray());
                $outcomeIdsFromModule = LearningOutcome::whereIn('module_id', $moduleIds)->pluck('id')->toArray();
                $outcomeIds = array_merge($outcomeIds, $outcomeIdsFromModule);
            }
        }

        $cabinet->learningOutcomes()->sync(array_unique($outcomeIds));

        return redirect()->route('cabinets.index');
    }

    public function destroy(Cabinet $cabinet)
    {
        $cabinet->delete();
        return redirect()->route('cabinets.index')->with('success', 'Кабинет успешно удалён.');
    }
}
