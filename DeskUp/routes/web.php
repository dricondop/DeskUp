<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LayoutController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// Home page
Route::get('/', function () {
    return view('welcome');
});

// Layout page (requires authentication)
Route::get('/layout', [LayoutController::class, 'index'])->middleware('auth');

// Sign in page
Route::get('/signin', function () {
    return view('signin');
})->name('login')->middleware('guest');

// Sign in logic
Route::post('/signin', function (Request $request) {
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
});

// Logout
Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/signin');
})->name('logout')->middleware('auth');
