<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Admin\EventRsvpController;
use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Purchaser\EventController as PurchaserEventController;
use App\Http\Controllers\Purchaser\DashboardController as PurchaserDashboardController;
use App\Http\Controllers\WorkController;

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

	Route::prefix('admin')->middleware('admin')->group(function () {
		// RSVP listing page (Blade)
		Route::get('/events/{event:id}/rsvps', [EventRsvpController::class, 'index'])
        ->name('admin.events.rsvps.index');

		Route::get('/events/{event:id}/rsvps/export', [EventRsvpController::class, 'exportCsv'])
			->name('admin.events.rsvps.export');

		Route::get('/events', [EventController::class, 'index'])
			->name('admin.events.index');
		Route::get('/events/create', [EventController::class, 'create'])
			->name('admin.events.create');
		Route::post('/events', [EventController::class, 'store'])
			->name('admin.events.store');
		Route::get('/events/{event:id}/edit', [EventController::class, 'edit'])
			->name('admin.events.edit');
		Route::put('/events/{event:id}', [EventController::class, 'update'])
			->name('admin.events.update');
		Route::delete('/events/{event:id}', [EventController::class, 'destroy'])
			->name('admin.events.destroy');

	});

	// Purchaser self-service routes
	Route::get('/dashboard', [PurchaserDashboardController::class, 'index'])->name('purchaser.dashboard');

	Route::prefix('my-events')->group(function () {
		Route::get('/', [PurchaserEventController::class, 'index'])->name('purchaser.events.index');
		Route::get('{event:id}/edit', [PurchaserEventController::class, 'edit'])->name('purchaser.events.edit');
		Route::put('{event:id}', [PurchaserEventController::class, 'update'])->name('purchaser.events.update');

		Route::get('{event:id}/guests', [\App\Http\Controllers\Purchaser\GuestController::class, 'index'])->name('purchaser.guests.index');
		Route::get('{event:id}/guests/{guest:id}/edit', [\App\Http\Controllers\Purchaser\GuestController::class, 'edit'])->name('purchaser.guests.edit');
		Route::put('{event:id}/guests/{guest:id}', [\App\Http\Controllers\Purchaser\GuestController::class, 'update'])->name('purchaser.guests.update');

		Route::get('{event:id}/rsvps', [\App\Http\Controllers\Purchaser\RsvpController::class, 'index'])->name('purchaser.rsvps.index');
		Route::get('{event:id}/rsvps/{rsvp:id}/edit', [\App\Http\Controllers\Purchaser\RsvpController::class, 'edit'])->name('purchaser.rsvps.edit');
		Route::put('{event:id}/rsvps/{rsvp:id}', [\App\Http\Controllers\Purchaser\RsvpController::class, 'update'])->name('purchaser.rsvps.update');
	});
});
