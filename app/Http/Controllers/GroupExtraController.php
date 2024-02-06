<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\Attendance;
use App\Models\StudentInformation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
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

    public function change_group(Request $request , $id)
    {

        $user=User::find($id);

        $user->update([
            'group_id'=>$request->group_id,
        ]);

        StudentInformation::create([
            'user_id'=>$id,
            'group_id'=>$request->group_id,
        ]);

        return redirect()->back()->with('success');
    }

    public function attendance($id){
        $today = Carbon::today();
        $items = Attendance::whereDate('created_at', $today)->where('group_id', $id)->paginate();
        return view('user.group.attendance',compact('items'));
    }

    public function filter(Request $request)
    {
        // Retrieve the selected date from the form input
        $selectedDate = $request->input('filter_date');

        // Query the database for attendance records matching the selected date
        $items = Attendance::whereDate('created_at', $selectedDate)->get();

        // Pass the filtered attendance records to the view
        return view('user.group.attendance',compact('items'));
    }

}
