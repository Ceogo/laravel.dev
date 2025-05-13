<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Cabinet;
use Illuminate\Http\Request;
use App\Models\LearningOutcome;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class TeacherController extends Controller
{
    public function index()
    {
        $teachers = User::where('role', 'teacher')
                    ->with(['preferredCabinets', 'learningOutcomes'])
                    ->get();
        $cabinets = Cabinet::all();
        $learningOutcomes = LearningOutcome::all();

        return view('teachers.index', compact('teachers', 'cabinets', 'learningOutcomes'));
    }

    public function create()
    {
        $cabinets = Cabinet::all();
        $learningOutcomes = LearningOutcome::all();
        return view('teachers.create', compact('cabinets', 'learningOutcomes'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:users,name',
            'surname' => 'required|string|max:255',
            'realname' => 'required|string|max:255',
            'firstname' => 'nullable|string|max:255',
            'display_name' => 'nullable|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'status' => 'required|in:active,inactive',
            'preferred_cabinet_id' => 'nullable|exists:cabinets,id',
            'learning_outcome_ids' => 'nullable|array',
            'learning_outcome_ids.*' => 'exists:learning_outcomes,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $teacher = User::create([
            'name' => $request->name,
            'surname' => $request->surname,
            'realname' => $request->realname,
            'firstname' => $request->firstname,
            'display_name' => $request->display_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'teacher',
            'status' => $request->status,
        ]);

        if ($request->has('preferred_cabinet_id')) {
            $teacher->preferredCabinets()->attach($request->preferred_cabinet_id);
        }
        if ($request->has('learning_outcome_ids')) {
            $teacher->learningOutcomes()->attach($request->learning_outcome_ids);
        }

        return redirect()->route('teachers.index')->with('success', 'Преподаватель успешно добавлен.');
    }

    public function edit(User $teacher)
    {
        $cabinets = Cabinet::all();
        $selectedCabinet = $teacher->preferredCabinets->first()?->id ?? null;
        $learningOutcomes = LearningOutcome::all();
        $selectedLearningOutcomes = $teacher->learningOutcomes->pluck('id')->toArray();

        return view('teachers.edit', compact('teacher', 'cabinets', 'selectedCabinet', 'learningOutcomes', 'selectedLearningOutcomes'));
    }

    public function update(Request $request, User $teacher)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:users,name,' . $teacher->id,
            'surname' => 'required|string|max:255',
            'realname' => 'required|string|max:255',
            'firstname' => 'nullable|string|max:255',
            'display_name' => 'nullable|string|max:255',
            'email' => 'required|email|unique:users,email,' . $teacher->id,
            'password' => 'nullable|string|min:8',
            'status' => 'required|in:active,inactive',
            'preferred_cabinet_id' => 'nullable|exists:cabinets,id',
            'learning_outcome_ids' => 'nullable|array',
            'learning_outcome_ids.*' => 'exists:learning_outcomes,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $teacher->update([
            'name' => $request->name,
            'surname' => $request->surname,
            'realname' => $request->realname,
            'firstname' => $request->firstname,
            'display_name' => $request->display_name,
            'email' => $request->email,
            'password' => $request->password ? Hash::make($request->password) : $teacher->password,
            'status' => $request->status,
        ]);

        $teacher->preferredCabinets()->sync($request->preferred_cabinet_id ?? []);
         $teacher->learningOutcomes()->sync($request->learning_outcome_ids ?? []);

        return redirect()->route('teachers.index')->with('success', 'Преподаватель успешно обновлён.');
    }

    public function destroy(User $teacher)
    {
        if ($teacher->role !== 'teacher') {
            return redirect()->route('teachers.index')->with('error', 'Этот пользователь не является преподавателем.');
        }

        $teacher->delete();
        return redirect()->route('teachers.index')->with('success', 'Преподаватель успешно удалён.');
    }

    public function exportTeachers()
    {
        $teachers = User::where('role', 'teacher')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Заголовки
        $sheet->setCellValue('A1', 'Имя');
        $sheet->setCellValue('B1', 'Email');
        $sheet->setCellValue('C1', 'Статус');
        $sheet->setCellValue('D1', 'Предпочтительный кабинет');

        // Данные
        $row = 2;
        foreach ($teachers as $teacher) {
            $sheet->setCellValue('A' . $row, $teacher->display_name);
            $sheet->setCellValue('B' . $row, $teacher->email);
            $sheet->setCellValue('C' . $row, $teacher->status);
            $sheet->setCellValue('D' . $row, $teacher->preferredCabinets->first()?->number ?? '—');
            $row++;
        }

        // Сохранение файла
        $writer = new Xlsx($spreadsheet);
        $fileName = 'teachers.xlsx';
        $tempPath = sys_get_temp_dir() . '/' . $fileName;
        $writer->save($tempPath);

        return response()->download($tempPath, $fileName)->deleteFileAfterSend(true);
    }
}
