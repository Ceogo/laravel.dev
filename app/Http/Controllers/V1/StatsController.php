<?php

namespace App\Http\Controllers\V1;

use App\Models\User;
use App\Models\Schedule;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class StatsController extends Controller
{
    public function index(Request $request)
    {
        $totalUsers = User::count();
        $activeStudents = User::where('role', 'student')->where('status', 'active')->count();
        $activeTeachers = User::where('role', 'teacher')->where('status', 'active')->count();
        $today = now()->toDateString();
        $classesToday = Schedule::whereDate('created_at', $today)->count();

        return response()->json([
            'totalUsers' => $totalUsers,
            'activeStudents' => $activeStudents,
            'activeTeachers' => $activeTeachers,
            'classesToday' => $classesToday,
        ]);
    }
}
