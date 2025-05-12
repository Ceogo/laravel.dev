<?php

namespace App\Http\Controllers;

use App\Models\Cabinet;
use App\Models\LearningOutcome;
use Illuminate\Http\Request;
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
        $learningOutcomes = LearningOutcome::all();
        return view('cabinets.create', compact('learningOutcomes'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'number' => 'required|string|unique:cabinets,number|max:50',
            'description' => 'nullable|string',
            'capacity' => 'nullable|integer|min:1',
            'learning_outcome_ids' => 'nullable|array',
            'learning_outcome_ids.*' => 'exists:learning_outcomes,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $cabinet = Cabinet::create([
            'number' => $request->number,
            'description' => $request->description,
            'capacity' => $request->capacity,
        ]);

        // Привязка РО
        if ($request->has('learning_outcome_ids')) {
            $cabinet->learningOutcomes()->attach($request->learning_outcome_ids);
        }

        return redirect()->route('cabinets.index')->with('success', 'Кабинет успешно добавлен.');
    }

    public function edit(Cabinet $cabinet)
    {
        $learningOutcomes = LearningOutcome::all();
        $selectedLOs = $cabinet->learningOutcomes->pluck('id')->toArray();
        return view('cabinets.edit', compact('cabinet', 'learningOutcomes', 'selectedLOs'));
    }

    public function update(Request $request, Cabinet $cabinet)
    {
        $validator = Validator::make($request->all(), [
            'number' => 'required|string|unique:cabinets,number,' . $cabinet->id,
            'description' => 'nullable|string',
            'capacity' => 'nullable|integer|min:1',
            'learning_outcome_ids' => 'nullable|array',
            'learning_outcome_ids.*' => 'exists:learning_outcomes,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $cabinet->update([
            'number' => $request->number,
            'description' => $request->description,
            'capacity' => $request->capacity,
        ]);

        // Обновление привязки РО
        $cabinet->learningOutcomes()->sync($request->learning_outcome_ids ?? []);

        return redirect()->route('cabinets.index')->with('success', 'Кабинет успешно обновлён.');
    }

    public function destroy(Cabinet $cabinet)
    {
        $cabinet->delete();
        return redirect()->route('cabinets.index')->with('success', 'Кабинет успешно удалён.');
    }
}
