<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class FCMServices
{ 
    //Cabecera de la peticion
    private static $Headers = [
        'Content-Type' => 'application/json',
        'Authorization' => 'key=AAAAwCtmc4g:APA91bHjuk-vzQ-8wi-V6mSidCbY3v4KKrmz4RNUtiq-DlwfM9vtszwUV6bvxrSZiMrJp3PvO0T5u0UqP6tQR0wq-7H9yU3nPFFoFnka-YUnhp8BjkTrt2dSVwdsk8PbkSRldLBK7TCs'
    ];

    //api de firebase apñi cloud message
    private static $cloudMessageAPI = 'https://fcm.googleapis.com/fcm/send';

    //envio de notificaciones por token
    public function sendNotificationByToken($token, $notification)
    {
        $response = Http::withHeaders(
            FCMServices::$Headers
        )->post(FCMServices::$cloudMessageAPI, [
            'to' => $token,
            'notification' => $notification,
        ])->json();

        return $response;
    }

    //envio de notificaciones por temas
    public function sendNotificationByTopic($topic, $notification) {
        $response = Http::withHeaders(
            FCMServices::$Headers
        )->post(FCMServices::$cloudMessageAPI, [
            'to' => "/topics/$topic",
            'notification' => $notification,
        ])->json();

        return $response;
    }
}