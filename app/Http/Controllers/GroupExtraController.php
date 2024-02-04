<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
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

}
