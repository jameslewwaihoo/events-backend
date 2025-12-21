<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::namespace('Api')
    ->prefix('public')
    ->group(function () {

        Route::get('/events/{event}', 'PublicEventController@show');
        Route::post('/events/{event}/rsvp', 'PublicEventController@submitRsvp')
            ->middleware('throttle:30,1');
        Route::get('/events/{event:slug}/rsvp', 'PublicEventController@getRsvp');
    });