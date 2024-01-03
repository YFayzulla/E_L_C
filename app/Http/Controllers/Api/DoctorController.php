<?php

namespace App\Http\Controllers\Api;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Napa\R19\Sms;

class DoctorController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $doctors = User::orderBy('id','DESC')->get();
        return response()->json($doctors, 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
//        $doctor = new User();
//        $doctor ->create($request->all());
//        return response()->json("Shifokor qo'shildi", 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $date = date('Y-m-d',strtotime(now()));
        $customers = Customer::orderby('time')->where('date',$date)->where('doctor_id',$id)->get();
        return response()->json($customers,200);
//        $doctor = Doctor::find($id);
//        return $doctor;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $user = User::find($id);
//        $doctor->update($request->all());

//        return response()->json($user, 200);
        if ($request->hasFile('image')) {
            $request->validate([
                'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg',

            ]);
            File::delete(public_path($user['image']));
//            /storage/doctors/ca5d7fd8-3a76-49c9-8cd4-36445501d92b-1667212087.jpg
            $uuid = Str::uuid()->toString();
            $fileName = $uuid . '-' . time() . '.' . $request->image->extension();
            $request->image->move(public_path('../storage/app/public/doctors'), $fileName);
            $user->update([
                'last_name'=> $request->last_name,
                'first_name' => $request->first_name,
                'father_name'=> $request->father_name,
                'username' => $request->username,
                'password' => Hash::make($request->password),
                'phone_number' => $request->phone_number,
                'speciality' => $request->speciality,
                'room_number' => $request->room_number,
                'image' => '/storage/doctors/' . $fileName,
            ]);
            $success['token'] = $user->createToken('LaravelSanctumAuth')->plainTextToken;
        } else {
            $user -> update($request->all());
            $success['token'] = $user->createToken('LaravelSanctumAuth')->plainTextToken;
            $success['name'] = $user->name;
        }
        return response()->json("Shifokor tahrirlandi", 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        User::find($id)->delete();
        return response()->json("Shifokor o'chirildi", 200);
    }
    public function sender(){
        Sms::send('998977913883',"salom");
        return response()->json(200,'success');
    }
}
