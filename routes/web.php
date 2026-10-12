<?php

use Dkbs\LaravelDkbsign\Exceptions\DkbsignException;
use Dkbs\LaravelDkbsign\Facades\Dkbsign;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dkbsign/test-otp', function () {
    if (blank(config('dkbsign.token'))) {
        return response()->json([
            'ok' => false,
            'message' => 'DKBSIGN_TOKEN manquant dans .env',
        ], 500);
    }

    try {
        $response = Dkbsign::sendOtp();

        return response()->json([
            'ok' => true,
            'data' => $response,
        ]);
    } catch (DkbsignException $e) {
        return response()->json([
            'ok' => false,
            'message' => $e->getMessage(),
            'status' => $e->statusCode,
        ], $e->statusCode >= 400 ? $e->statusCode : 502);
    }
});
