<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class RefreshController extends Controller
{
    public function update($id)
    {
        User::query()->findOrFail($id)->update(['status' => 0]);
        return redirect()->back()->with('success','data updated successfully');
    }
}
