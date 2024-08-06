<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\Attendance;
use App\Models\Group;
use App\Models\StudentInformation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PDF;
use function Symfony\Component\Translation\t;

class GroupExtraController extends Controller
{

    public function deleteMultiple(Request $request)
    {

        $selectedItems = $request->input('selectedItems', []);

        // Delete the selected items from the database
        Assessment::whereIn('id', $selectedItems)->delete();

        return redirect()->back()->with('success', 'Selected items deleted successfully');

    }

    public function change_group(Request $request, $id)
    {

        $user = User::find($id);

        $user->update([
            'group_id' => $request->group_id,
        ]);

        $group = Group::find($request->group_id);

        StudentInformation::create([
            'user_id' => $id,
            'group_id' => $request->group_id,
            'group' => $group->name,
        ]);

        return redirect()->back()->with('success', 'Updated successfully!');
    }


    public function filter(Request $request, $id)
    {


        if ($request->filter_date == null) {
            $selectedDate = $request->filter_date = Carbon::today();
        } else
            $selectedDate = $request->input('filter_date');

        if ($request->input('task') === 'show') {

            // Retrieve the selected date from the form input

            // Query the database for attendance records matching the selected date

            $group = Group::find($id);


            $items = Attendance::whereDate('created_at', $selectedDate)->where('group_id', $id)->get();


            // Pass the filtered attendance records to the view

            return view('user.group.attendance', compact('items', 'group'));

        } elseif ($request->input('task') === 'report') {

            $items = Attendance::whereDate('created_at', $selectedDate)->get();

            $pdf = PDF::loadView('user.pdf.attendance_in_group', ['items' => $items]);

            return $pdf->download('orders.pdf');

            GeneratePdfJob::dispatch($id);

            return "PDF generation job dispatched successfully!";

        } else {

        }
    }

    public function show($id)
    {

        $students = User::where('group_id', $id)->orderby('name')->role('student')->get();

        return view('user.group.student', compact('students'));

    }

    public function attendance($id)
    {

        $today = Carbon::today();
        $items = Attendance::whereDate('created_at', $today)->where('group_id', $id)->get();
        $group = Group::find($id);

        //        dd($group,$items);

        return view('user.group.attendance', compact('items', 'group'));

    }


    public function attendanceList()
    {
        $date = request('date', now()->format('Y-m')); // Default to current year-month if not provided
        list($year, $month) = explode('-', $date);

        // Fetch students (assuming a 'role' column in the users table to differentiate students)
        $students = User::role('student')->get();

        // Fetch attendances for the selected month and year based on created_at timestamp
        $attendances = Attendance::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->get();

        $data = [];
        foreach ($students as $student) {
            $data[$student->name] = [];
            for ($i = 1; $i <= 31; $i++) {
                $data[$student->name][str_pad($i, 2, '0', STR_PAD_LEFT)] = ''; // Initialize all days as empty
            }
        }

        foreach ($attendances as $attendance) {
            $day = $attendance->created_at->format('d');
            $data[$attendance->user->name][$day] = $attendance->status; // Adjust status if needed
        }

        return view('user.group.attendance_list', [
            'data' => $data,
            'year'=> $year,
            'month'=> $month,
        ]);
    }

}
