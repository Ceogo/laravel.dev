<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Cabinet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class TeacherController extends Controller
{
    public function index()
    {
        $teachers = User::where('role', 'teacher')->with('preferredCabinets')->get();
        $cabinets = Cabinet::all();
        return view('teachers.index', compact('teachers', 'cabinets'));
    }

    public function create()
    {
        $cabinets = Cabinet::all();
        return view('teachers.create', compact('cabinets'));
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

        return redirect()->route('teachers.index')->with('success', 'Преподаватель успешно добавлен.');
    }

    public function edit(User $teacher)
    {
        $cabinets = Cabinet::all();
        $selectedCabinet = $teacher->preferredCabinets->first()?->id ?? null;
        return view('teachers.edit', compact('teacher', 'cabinets', 'selectedCabinet'));
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
}
