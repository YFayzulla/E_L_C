<?php

namespace App\Http\Controllers\Api;

use App\Models\Customer;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $today = date('Y-m-d', strtotime(now()));
        $tomorrow = date('Y-m-d',strtotime('+1 day', strtotime(now())));
        $after_tomorrow = date('Y-m-d',strtotime('+2 day',strtotime(now())));
        $customer_today = Customer::orderby('time')->where('date',$today)->get();
        $customer_tomorrow = Customer::orderby('time')->where('date',$tomorrow)->get();
        $customer_after_tomorrow = Customer::orderby('time')->where('date',$after_tomorrow)->get();
        $date = [];
        foreach ($customer_today as $value)
        $date['today'][] = $value;
        foreach ($customer_tomorrow as $value)
        $date['tomorrow'][] = $value;
        foreach ($customer_after_tomorrow as $value)
        $date['after_tomorrow'][] = $value;
        return response()->json($date, 200)->setCharset('utf-8');
    }
    public function get_data(Request $request){
        $data = Customer::orderby('time')->where('date',$request->date)->get();
        return response()->json($data, 200)->setCharset('utf-8');
    }
    public function get_customers(Request $request){
        $data = Customer::orderby('time')->where('date',$request->date)->where('doctor_id',$request->doctor_id)->get();
        return response()->json($data, 200)->setCharset('utf-8');
    }
    public function get_customers_v2(Request $request){
        $time = array();
      // $arr= array();
        $data = Customer::orderby('time')->where('date',$request->date)->where('doctor_id',$request->doctor_id)->get();

        //dd($request->date??"salom");
        $cnt = 0;
        foreach ($data as $value){
            $start_time = date("H",strtotime($value->time));
            $end_time = date("H",strtotime($value->end_time));
            for ($i=$start_time; $i<$end_time; $i++){
                $s="";
                $s = $i.':00:00';

                $time[$cnt]['id'] = $value['id'];
                $time[$cnt]['name'] = $value['name'];
                $time[$cnt]['phone'] = $value['phone'];
                $time[$cnt]['doctor_id'] = $value['doctor_id'];
                $time[$cnt]['language'] = $value['language'];
                $time[$cnt]['date'] = $value['date'];
                $time[$cnt]['time'] = $s;
                $time[$cnt]['procedura'] = $value['procedura'];
                $time[$cnt]['status'] = $value['status'];
                $cnt++;
            }
        }
        return response()->json($time, 200)->setCharset('utf-8');
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $date = date($request['date']);
        $time = date($request['time']);
        $end_time = date($request['end_time']);
        $doctor = $request['doctor_id'];
        $customer = Customer::where('doctor_id', $doctor)->where('date', $date)->get();
        $count = 0;
        foreach ($customer as $value) {
            if (strtotime($value->time) <= strtotime($time) &&  strtotime($time) < strtotime($value->end_time)) {
                $count++;
            }
            if (strtotime($value->time) < strtotime($end_time) && strtotime($end_time) <= strtotime($value->end_time)) {
                $count++;
            }
        }

        if ($end_time <= $time){
            return response()->json(['message' => "Vaqt oralig'i noto'g'ri kiritilgan"], 404);
        }
        else if (strtotime($date.$time) < strtotime(now())) {
            return response()->json(['message' => 'Bu vaqt o\'tib ketgan !'], 404);
        } else
            if ($count > 0)
                return response()->json(['message' => 'Shifokorning bu vaqtda mijozi bor !'], 404);
            else {
                $customer = new Customer();
                $customer->create($request->all());
                return response()->json(['message' => "Ro'yxatga yozildingiz !"], 200);
            }
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
//        dd($id);
//        $customer = Customer::find($id);
//        return $customer;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $date = date($request['date']);
        $time = date($request['time']);
        $end_time = date($request['end_time']);
        $doctor = $request['doctor_id'];
        $customer = Customer::where('doctor_id', $doctor)->where('date', $date)->get();
        $count = 0;
        foreach ($customer as $value) {
            if ($value->id == $id) continue;
            if ($value->time <= $time &&  $time < $value->end_time) {
                $count++;
            }
            if ($value->time < $end_time && $end_time <= $value->end_time) {
                $count++;
            }
        }
        if ($end_time <= $time){
            return response()->json(['message' => "Vaqt oralig'i noto'g'ri kiritilgan"], 404);
        }
        else if (strtotime($date.$time) < strtotime(now())) {
            return response()->json(['message' => 'Bu vaqt o\'tib ketgan !'], 404);
        } else
            if ($count > 0)
                return response()->json(['message' => 'Shifokorning bu vaqtda mijozi bor !'], 404);
            else {
                $customer = Customer::find($id);
                $customer->update($request->all());
                return response()->json(['message' => "Ro'yxatga yozildingiz !"], 200);
            }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        Customer::find($id)->delete();
        return response()->json(['message' => "o'chirildi"], 200);
    }


}
