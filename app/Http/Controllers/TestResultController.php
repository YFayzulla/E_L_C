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
        $assessment = Assessment::query()->where('history_id', '=', $id)->get();
        $name = $assessment->first()->group;
//        dd($name,$assessment);
        return view('assessment.index', [
            'assessments' => Assessment::query()->where('history_id', '=', $id)->get(),
            'groups'=>Group::query()->orderBy('name')->get(),
            'id'=>$name,
        ]);

    }
}
