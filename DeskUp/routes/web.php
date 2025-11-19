<?php

use App\Helpers\APIMethods;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LayoutController;
use App\Http\Controllers\DeskController;
use App\Services\DeskSyncService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/desk-control/{id}', [DeskController::class, 'show'])->name('desk.control');
Route::post('/api/desks/{id}/height', [DeskController::class, 'updateHeight'])->middleware('auth');
Route::post('/api/desks/{id}/status', [DeskController::class, 'updateStatus'])->middleware('auth');
Route::get('/api/desks/{id}/realtime', function ($id, DeskSyncService $syncService) {
    $desk = \App\Models\Desk::findOrFail($id);
    $realtimeData = $syncService->getRealTimeDeskData($desk);
    
    return response()->json([
        'success' => true,
        'data' => $realtimeData,
        'connected' => $desk->isConnectedToAPI()
    ]);
})->middleware('auth');

Route::get('/layout', [LayoutController::class, 'index'])->middleware('auth')->name('layout');
Route::post('/layout/save', [LayoutController::class, 'save'])->middleware('auth');
Route::get('/layout/load', [LayoutController::class, 'load'])->middleware('auth');

// Desk synchronization routes
Route::post('/api/desks/sync', function (DeskSyncService $syncService) {
    try {
        $results = $syncService->syncFromAPI();
        return response()->json([
            'success' => true,
            'results' => $results
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
})->middleware('auth');

Route::get('/api/desks/status', function (DeskSyncService $syncService) {
    return response()->json([
        'api_available' => $syncService->isAPIAvailable(),
        'timestamp' => now()->toDateTimeString()
    ]);
})->middleware('auth');

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
})->name('profile');

Route::get('desk-control', [DeskController::class, 'showAssignedDesk']);
Route::get('/desk-control', function () {
    if (Auth::check()) {
        $user = Auth::user();
        if ($user->assigned_desk_id) {
            return redirect()->route('desk.control', ['id' => $user->assigned_desk_id]);
        }

        return redirect('/layout')->with('error', 'You do not have an assigned desk.');
    }

    return redirect()->route('login');
})->name('desk.control.redirect');

Route::get('/health', function () {
    return view('health');
})->name('health');

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
        RateLimiter::clear($key); // Clear attempts when successful login
        
        // Redirect user to their assigned desk or layout if admin/no desk
        $user = Auth::user();
        if ($user->assigned_desk_id) {
            return redirect()->route('desk.control', ['id' => $user->assigned_desk_id]);
        }
        
        return redirect()->intended('/layout');
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


//API TESTING ROUTES
Route::get('/apitest', function () {
    $height = 790.0;
    $deskId = 'cd:fb:1a:53:fb:e6';

    $response = APIMethods::raiseDesk($height, $deskId);

    return $response;
});

Route::get('/apitest2', function () {

    $response = APIMethods::getAllDesks();

    return $response;
});

Route::get('/apitest3', function () {
    $category = "state";
    $deskId = 'cd:fb:1a:53:fb:e6';

    $response = APIMethods::getCategoryData($category, $deskId);

    return $response;
});

Route::get('/apitest4', function () {
    $deskId = '70:9e:d5:e7:8c:98';

    $response = APIMethods::getDeskData($deskId);

    return $response;
});
