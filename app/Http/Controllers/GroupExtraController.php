<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\StudentInformation;
use App\Models\User;
use Illuminate\Http\Request;

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

    public function assessment($id){
        return view('user.group.attendance',compact('id'));
    }

}
