<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LayoutController;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/layout', [LayoutController::class, 'index'])->middleware('auth');

Route::get('/signin', function () {
    return view('signin');
})->name('login')->middleware('guest');

Route::get('/profile', function () {
    return view('profile');
});

Route::get('/edit-profile', function () {
    return view('edit-profile');
});

Route::post('/signin', function (Request $request): Response|RedirectResponse {
    $credentials = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required'],
    ]);

    if (Auth::attempt($credentials)) {
        $request->session()->regenerate();
        return redirect()->intended('/layout');
    }

    return back()
        ->withErrors(['auth' => 'Incorrect email or password'])
        ->withInput(['email' => $request->input('email')]);
})->middleware('throttle:5,1'); 

Route::view('/health', 'health')->name('health');

Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/signin');
})->name('logout')->middleware('auth');
