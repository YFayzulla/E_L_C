<?php

namespace App\Http\Controllers;

use App\Models\DeptStudent;
use App\Models\GroupTeacher;
use App\Models\HistoryPayments;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

//    public function index()
//    {
//    }

    public function auth()
    {
        $id = auth()->id();
        $groups = GroupTeacher::where('teacher_id', $id)->get();
        return view('dashboard', compact('groups'));
    }

    public function search(Request $request)
    {
        // Convert input dates to Carbon instances and format them as strings
        $startDate = $request->start_date ? Carbon::parse($request->start_date)->subDay()->startOfDay() : null;
        $endDate = $request->end_date ? Carbon::parse($request->end_date)->endOfDay() : null;

        // If both dates are provided, perform the whereBetween query
        if ($startDate && $endDate) {
            $users = HistoryPayments::whereBetween('date', [$startDate, $endDate])->get();
        } else {
            // If only one date is provided, check for that specific date
            $users = HistoryPayments::query()
                ->when($startDate, function ($query, $startDate) {
                    return $query->whereDate('date', $startDate);
                })
                ->when($endDate, function ($query, $endDate) {
                    return $query->orWhereDate('date', $endDate);
                })
                ->get();
        }

        $date = [$startDate, $endDate];

        // Pass the users and date range to the view
        return view('user.index', [
            'users' => $users,
            'start_date' => $startDate ? $startDate->toDateString() : null,
            'end_date' => $endDate ? $endDate->toDateString() : null,
//            'date' => $date,
        ]);
    }


}
