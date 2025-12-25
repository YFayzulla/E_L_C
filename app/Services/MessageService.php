<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class MessageService
{

    public function refreshToken()
    {
        $response = Http::patch("notify.eskiz.uz/api/auth/refresh")->json();
        return $response['data']['token'];
    }

    public function sendMessage($phone ,$message, $retry = false): void
    {
        if (!$phone) {
            return;
        }

        $token = Cache::get('token');
        $phone = '998'.$phone;
        if (!$token) {
            $token = $this->getToken();
            Cache::put('token', $token);
        }
        if ($phone != '998999999999') {
            $res = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->post("notify.eskiz.uz/api/message/sms/send", [
                'mobile_phone' => "$phone",
                'message' => "$message",
                'from' => '4546',
                //'callback_url' => route('receive_status')
            ]);
            if ($res->status() >= 400) {
                $token = $this->getToken();
                Cache::put('token', $token);

                if (!$retry) {
                    $this->sendMessage($phone, $message, true);
                }
            }
        }
    }


    public function getToken()
    {
        $response = Http::post("notify.eskiz.uz/api/auth/login", [
            'email' => config('app.sms_email'),
            'password' => config('app.sms_password'),
        ])->json();

        return $response['data']['token'];
    }

    public function receive(Request $request)
    {// EXPIRED
        if ($request->get('status') != "DELIVRD")
            Cache::put('token', $this->getToken());
    }
}