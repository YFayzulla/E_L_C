<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Api\BaseController as BaseController;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;



class AuthController extends BaseController
{

    public function login(Request $request)
    {
        if (Auth::attempt(['username' => $request->username, 'password' => $request->password])) {
            $auth = Auth::user();
            $success['token'] = $auth->createToken('LaravelSanctumAuth')->plainTextToken;
//            $success['name'] = $auth->name;
            $success['username'] = $auth->username;
            $success['id'] = $auth->id;

            return $this->handleResponse($success, 'User logged-in!');
        } else {
            return $this->handleError('Unauthorised.', ['error' => 'Unauthorised']);
        }
    }

    public function register(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'username' => 'required|unique:users',
            'password' => 'required|confirmed|min:6',
            'phone_number' => 'required',
            'speciality' => 'required',
            'room_number' => 'required'
        ]);
        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json(['error' => $errors], 400);
        }
        if ($validator->passes()) {
            if ($request->hasFile('image')) {
                $request->validate([
                    'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:16000'
                ]);
                $uuid = Str::uuid()->toString();
                $fileName = $uuid . '-' . time() . '.' . $request->image->extension();
                $request->image->move(public_path('../storage/app/public/doctors'), $fileName);
                $user = User::create([
                    'last_name' => $request->last_name,
                    'first_name' => $request->first_name,
                    'father_name' => $request->father_name,
                    'username' => $request->username,
                    'password' => Hash::make($request->password),
                    'phone_number' => $request->phone_number,
                    'speciality' => $request->speciality,
                    'room_number' => $request->room_number,
                    'image' => '/storage/doctors/' . $fileName,
                ]);
                $user->save();
            } else {

                $user = User::create([
                    'last_name' => $request->last_name,
                    'first_name' => $request->first_name,
                    'father_name' => $request->father_name,
                    'username' => $request->username,
                    'password' => Hash::make($request->password),
                    'phone_number' => $request->phone_number,
                    'speciality' => $request->speciality,
                    'room_number' => $request->room_number,
                ]);
                $user->save();
                $success['name'] = $user->username;
            }
            return $this->handleResponse($success, 'Registered!');
        }
    }

    public function logout(Request $request)
    {
        auth()->user()->currentAccessToken()->delete();
        return $this->handleResponse([], 'User successfully logged out!');
    }
}
