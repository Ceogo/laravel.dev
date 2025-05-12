<?php

namespace App\Http\Controllers\V1;

use App\Models\Schedule;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class TeacherStatsController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $semester = $request->input('semester', 3);
        $week = $request->input('week', 1);

        $totalClasses = Schedule::whereHas('learningOutcome', function ($query) use ($user) {
            $query->where('teacher_name', $user->name);
        })
            ->where('semester', $semester)
            ->where('week', $week)
            ->count();

        return response()->json([
            'totalClasses' => $totalClasses,
        ]);
    }
}
