<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function search(Request $request){
        $date = $request['date'];
        $doctor_id = $request['doctor_id'];
        $customer = Customer::orderby('time')->where('doctor_id',$doctor_id)->where('date', $date)->get();
        return response()->json($customer,200);
    }
}
