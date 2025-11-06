<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LayoutController;
use App\Http\Controllers\DeskController;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/desk-control/{id}', [DeskController::class, 'show'])->name('desk.control');

Route::get('/layout', [LayoutController::class, 'index'])->middleware('auth');
Route::post('/layout/save', [LayoutController::class, 'save'])->middleware('auth');
Route::get('/layout/load', [LayoutController::class, 'load'])->middleware('auth');

Route::middleware('auth')->group(function () {
    Route::get('/api/desks', [DeskController::class, 'index']);
    Route::post('/api/desks/{id}/height', [DeskController::class, 'updateHeight']);
    Route::post('/api/desks/{id}/status', [DeskController::class, 'updateStatus']);
    Route::post('/api/desks/{id}/activities', [DeskController::class, 'addActivity']);
});

Route::get('/signin', function () {
    return view('signin');
})->name('login');

Route::get('/admin-statistics', function () {
    return view('admin-statistics');
})->name('admin-statistics');


Route::get('/admin-control', function () {
    $user = auth()->user();
    $isAdmin = $user && $user->is_admin; 
    return view('admin-user-control', compact('isAdmin'));
})->name('admin-user-control');

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
