<?php

namespace App\Http\Controllers;

use App\Models\CommentStudent;
use App\Http\Requests\StoreCommentStudentRequest;
use App\Http\Requests\UpdateCommentStudentRequest;
use App\Models\Group;
use App\Models\GroupTeacher;

class CommentStudentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('teacher.comment.index', [
            'groups' => GroupTeacher::query()->where('teacher_id', auth()->id())->get()
        ]);
    }

    public function store(StoreCommentStudentRequest $request){

        CommentStudent::query()->create([
            'teacher' => auth()->user()->name,
            'student_id' => $request->student_id,
            'comment' => $request->comment
        ]);
        return back()->with('success' , 'Saved ');

    }
}
