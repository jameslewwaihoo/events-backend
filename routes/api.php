<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Services\InvitationMusic;

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
        Route::get('/events/{event}/photos/{version}/{variant}', 'PublicEventController@photo')
            ->where(['version' => '[a-f0-9-]{36}', 'variant' => 'full|thumb'])
            ->withoutMiddleware('throttle:api')
            ->name('public.events.photos.image');
        Route::get('/events/{event}/music/{track}', 'PublicEventController@music')
            ->where('track', InvitationMusic::TRACK_ID_PATTERN)
            ->withoutMiddleware('throttle:api')
            ->name('public.events.music.track');
        Route::post('/events/{event}/rsvp', 'PublicEventController@submitRsvp')
            ->middleware('throttle:30,1');
        Route::get('/events/{event:slug}/rsvp', 'PublicEventController@getRsvp');

        Route::get('/events/{event}/household-rsvp', 'HouseholdRsvpController@show');
        Route::post('/events/{event}/household-rsvp', 'HouseholdRsvpController@submitShared')
            ->middleware('throttle:10,1,shared-rsvp');
        Route::put('/events/{event}/household-rsvp', 'HouseholdRsvpController@update')
            ->middleware('throttle:30,1');
    });
