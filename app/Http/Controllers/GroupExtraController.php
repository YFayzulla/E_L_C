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

        $group=Group::find($request->group_id);

        StudentInformation::create([
            'user_id' => $id,
            'group_id' => $request->group_id,
            'group'=> $group->name,
        ]);

        return redirect()->back()->with('success', 'Updated successfully!');
    }

    public function attendance($id)
    {

        $today = Carbon::today();
        $items = Attendance::whereDate('created_at', $today)->where('group_id', $id)->get();
        $group= Group::find($id);
        return view('user.group.attendance', compact('items','group'));

    }

    public function filter(Request $request)
    {

        if ($request->filter_date == null) {
            $selectedDate=$request->filter_date = Carbon::today();
        } else
            $selectedDate = $request->input('filter_date');

        if ($request->input('task') === 'show') {

            // Retrieve the selected date from the form input

            // Query the database for attendance records matching the selected date
            $items = Attendance::whereDate('created_at', $selectedDate)->get();

            // Pass the filtered attendance records to the view
            return view('user.group.attendance', compact('items'));

        } elseif ($request->input('task') === 'report') {


            $items = Attendance::whereDate('created_at', $selectedDate)->get();

            $pdf = PDF::loadView('user.pdf.attendance_in_group', ['items' => $items]);

            return $pdf->download('orders.pdf');

            GeneratePdfJob::dispatch($id);

            return "PDF generation job dispatched successfully!";

        } else {

        }
    }

    public function show($id){

        $students = User::where('group_id' , $id)->orderby('name') ->role('student')->get();

        return view('user.group.student',compact('students'));

    }


}
