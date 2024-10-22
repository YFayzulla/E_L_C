<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\Group;
use App\Models\LessonAndHistory;
use Illuminate\Http\Request;

class TestResultController extends Controller
{
    public function index()
    {
        return view('assessment.main', [
            'data' => LessonAndHistory::query()->where('data', '=', 2)->paginate(7),
            'topStudents' => Assessment::query()->orderBy('new_get_mark', 'desc')
                ->take(5)->get()
        ]);
    }


    public function showResults($id)
    {
        $assessment = Assessment::query()->where('history_id', '=', $id)->first();
        if ($assessment == null) {
            return redirect()->back()->with('error',  'no information in this action');
        }else{
        return view('assessment.index', [
            'assessments' => Assessment::query()->where('history_id', '=', $id)->get(),
            'groups' => Group::query()->orderBy('name')->get(),
        ]);
        }

    }
}
