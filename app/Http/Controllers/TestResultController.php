<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\LessonAndHistory;
use Illuminate\Http\Request;

class TestResultController extends Controller
{
    public function index()
    {
        return view('testresult.main', [
            'data' => LessonAndHistory::query()->where('data', '=', 2 )->get(),
            'topStudents' => Assessment::query()->orderBy('get_mark', 'desc')
                ->take(5)->get()
        ]);
    }
}
