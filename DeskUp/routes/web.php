<?php

use App\Helpers\APIMethods;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LayoutController;
use App\Http\Controllers\DeskController;
use App\Http\Controllers\ProfileController; 
use App\Http\Controllers\HealthController;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use App\Http\Controllers\AdminStatisticsController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/desk-control/{id}', [DeskController::class, 'show'])->name('desk.control');
Route::post('/api/desks/{id}/height', [DeskController::class, 'updateHeight'])->middleware('auth');
Route::post('/api/desks/{id}/status', [DeskController::class, 'updateStatus'])->middleware('auth');

Route::get('/layout', [LayoutController::class, 'index'])->middleware('auth')->name('layout');
Route::post('/layout/save', [LayoutController::class, 'save'])->middleware('auth');
Route::get('/layout/load', [LayoutController::class, 'load'])->middleware('auth');

Route::middleware('auth')->group(function () {
    Route::get('/api/desks', [DeskController::class, 'index']);
    Route::post('/api/desks/{id}/height', [DeskController::class, 'updateHeight']);
    Route::post('/api/desks/{id}/status', [DeskController::class, 'updateStatus']);
    Route::post('/api/desks/{id}/activities', [DeskController::class, 'addActivity']);
    
    // Health page view
    Route::get('/health', [HealthController::class, 'index'])->name('health');
    // Health stats API endpoint
    Route::get('/api/health-stats', [HealthController::class, 'getStats'])->name('api.health.stats');
    Route::get('/api/health-chart-data', [HealthController::class, 'getChartData'])->name('api.health.chart');
    Route::get('/api/health-live-status', [HealthController::class, 'getLiveStatus'])->name('api.health.live');
});

Route::get('/signin', function () {
    return view('signin');
})->name('login');


Route::get('/admin-statistics', [AdminStatisticsController::class, 'index'])
    ->name('admin-statistics')
    ->middleware('auth');


Route::get('/admin-control', function () {
    $user = auth()->user();
    $isAdmin = $user && $user->is_admin; 
    return view('admin-user-control', compact('isAdmin'));
})->name('admin-user-control');

Route::get('/profile', [ProfileController::class, 'show'])->name('profile')->middleware('auth');

Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
Route::put('/profile/update', [ProfileController::class, 'update'])->name('profile.update');
Route::delete('/profile/picture/delete', [ProfileController::class, 'deleteProfilePicture'])->name('profile.picture.delete');

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

// Populates the desk table in the database with all the desks available from the simulator
Route::get('/sync-desks-from-api', function () {
    $deskSyncService = new \App\Services\DeskSyncService();
    $results = $deskSyncService->syncDesksFromApi();
    
    return response()->json([
        'success' => true,
        'message' => 'Desk sync completed',
        'results' => $results,
        'total_desks_in_db' => \App\Models\Desk::count()
    ]);
});

// Sync current API data for ALL available desks (should be periodically loaded)
Route::get('/sync-all-desks-data', function () {
    $deskSyncService = new \App\Services\DeskSyncService();
    $results = $deskSyncService->syncAllDesksData();
    
    return response()->json([
        'success' => true,
        'message' => 'All desks data synced',
        'results' => $results,
        'total_records' => \App\Models\UserStatsHistory::count()
    ]);
});

// Sync current API data for a specific desk
Route::get('/sync-desk-data/{apiDeskId}', function ($apiDeskId) {
    $deskSyncService = new \App\Services\DeskSyncService();
    $result = $deskSyncService->syncSingleDeskData($apiDeskId);
    
    return response()->json($result);
});

// Get all desks from the API with some info (for debugging)
Route::get('/api-desk-mapping', function () {
    $deskSyncService = new \App\Services\DeskSyncService();
    
    try {
        $mapping = $deskSyncService->getApiDeskMapping();
        
        return response()->json([
            'success' => true,
            'mapping' => $mapping
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to get mapping: ' . $e->getMessage(),
            'error' => $e->getMessage()
        ], 500);
    }
});