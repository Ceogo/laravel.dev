<?php

namespace App\Http\Controllers\V1;

use Illuminate\Http\Request;
use App\Models\LearningOutcome;
use App\Http\Controllers\Controller;

class LearningOutcomeController extends Controller
{
    public function index(Request $request)
    {
        return LearningOutcome::with('module')->get();
    }
}
