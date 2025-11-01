<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LayoutController;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/layout', [LayoutController::class, 'index'])
    ->middleware(['auth', 'admin']);

Route::get('/signin', function () {
    return view('signin');
})->name('login');

Route::get('/profile', function () {
    return view('profile');
});

Route::get('/desk-control', function () {
    return view('desk-control');
});

Route::get('/health', function () {
    return view('health');
});

Route::get('/edit-profile', function () {
    return view('edit-profile');
});

Route::post('/signin', function (Request $request): Response|RedirectResponse {
    $key = 'login-attempts:' . $request->ip();
    
    // Verify if the 5 attemps per minute are reached
    if (RateLimiter::tooManyAttempts($key, 5)) {
        $seconds = RateLimiter::availableIn($key);
        return back()
            ->withErrors(['auth' => 'Too many requests, try again in 60 seconds'])
            ->withInput(['email' => $request->input('email')]);
    }

    $credentials = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required'],
    ]);

    if (Auth::attempt($credentials)) {
        $request->session()->regenerate();
        RateLimiter::clear($key); // Clear attemps when successful login
        return redirect()->intended('/desk-control');
    }

    // Increase failed attemps
    RateLimiter::hit($key, 60); 

    return back()
        ->withErrors(['auth' => 'Incorrect email or password'])
        ->withInput(['email' => $request->input('email')]);
}); 

Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/signin');
})->name('logout');
