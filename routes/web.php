<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Admin\EventRsvpController;
use App\Http\Controllers\Admin\EventController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Redirect root URL to /home (dashboard)
Route::get('/', function () {
    return redirect()->route('home');
});

// Register default Laravel authentication routes
Auth::routes();

// Dashboard route
Route::get('/home', [HomeController::class, 'index'])->name('home');

// Route::get('/login', 'App\Http\Controllers\LoginController@show')->name('login');

Route::group(['middleware' => 'auth'], function () {
	Route::resource('user', 'App\Http\Controllers\UserController', ['except' => ['show']]);
	Route::get('profile', ['as' => 'profile.edit', 'uses' => 'App\Http\Controllers\ProfileController@edit']);
	Route::put('profile', ['as' => 'profile.update', 'uses' => 'App\Http\Controllers\ProfileController@update']);
	Route::get('upgrade', function () {return view('pages.upgrade');})->name('upgrade'); 
	 Route::get('map', function () {return view('pages.maps');})->name('map');
	 Route::get('icons', function () {return view('pages.icons');})->name('icons'); 
	 Route::get('table-list', function () {return view('pages.tables');})->name('table');
	Route::put('profile/password', ['as' => 'profile.password', 'uses' => 'App\Http\Controllers\ProfileController@password']);

	Route::prefix('work')->controller(WorkController::class)->group(function () {
		Route::get('/', 'index')->name('work');
		Route::post('/upload-excel', 'uploadExcel')->name('upload.excel');
	});

	Route::prefix('admin')->group(function () {
		// RSVP listing page (Blade)
		Route::get('/events/{event:id}/rsvps', [EventRsvpController::class, 'index'])
        ->name('admin.events.rsvps.index');

		Route::get('/events/{event:id}/rsvps/export', [EventRsvpController::class, 'exportCsv'])
			->name('admin.events.rsvps.export');

		Route::get('/events', [EventController::class, 'index'])
			->name('admin.events.index');

	});
});