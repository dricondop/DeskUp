<?php

use App\Helpers\APIMethods;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LayoutController;
use App\Http\Controllers\DeskController;
use App\Http\Controllers\ProfileController; 
use App\Http\Controllers\HeightDetectionController; 
use App\Http\Controllers\HealthController;
use App\Http\Controllers\PDFExportController;
use App\Http\Controllers\NotificationController;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use App\Http\Controllers\AdminStatisticsController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\EventController;
use App\Models\UserStatsHistory;
use App\Models\Desk;
use App\Models\User;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/desk-control/{id}', [DeskController::class, 'show'])->middleware('sync.desks')->name('desk.control');
Route::post('/api/desks/{id}/height', [DeskController::class, 'updateHeight'])->middleware('auth');
Route::post('/api/desks/{id}/status', [DeskController::class, 'updateStatus'])->middleware('auth');

Route::get('/layout', [LayoutController::class, 'index'])->middleware(['auth', 'sync.desks'])->name('layout');
Route::post('/layout/save', [LayoutController::class, 'save'])->middleware('auth');
Route::get('/layout/load', [LayoutController::class, 'load'])->middleware('auth');

Route::middleware('auth')->group(function () {
    Route::get('/api/desks', [DeskController::class, 'index']);
    Route::post('/api/desks/{id}/height', [DeskController::class, 'updateHeight']);
    Route::post('/api/desks/{id}/status', [DeskController::class, 'updateStatus']);
    Route::post('/api/addEvent', [EventController::class, 'addEvent']);
    Route::post('/api/addCleaningSchedule', [EventController::class, 'addCleaningSchedule']);
    
    // API Status Check
    Route::get('/api/check-status', function () {
        $deskSyncService = new \App\Services\DeskSyncService();
        try {
            $isOnline = $deskSyncService->checkApiHealth();
            return response()->json([
                'status' => $isOnline ? 'online' : 'offline',
                'message' => $isOnline ? 'API is online and ready' : 'API is offline or unreachable'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'offline',
                'message' => 'API connection failed: ' . $e->getMessage()
            ], 503);
        }
    });
    
    // Health page view
    Route::get('/health', [HealthController::class, 'index'])->name('health');
    // Health stats API endpoint
    Route::get('/api/health-stats', [HealthController::class, 'getStats'])->name('api.health.stats');
    Route::get('/api/health-chart-data', [HealthController::class, 'getChartData'])->name('api.health.chart');
    Route::get('/api/health-live-status', [HealthController::class, 'getLiveStatus'])->name('api.health.live');

    // PDF Export routes
    Route::get('/health/export/pdf', [PDFExportController::class, 'exportHealthPDF'])->name('health.export.pdf');
    Route::get('/health/export/preview', [PDFExportController::class, 'previewHealthPDF'])->name('health.export.preview');
    // Combined endpoint for instant page load
    Route::get('/api/health-data', [HealthController::class, 'getAllData'])->name('api.health.all');
});

Route::get('/signin', function () {
    return view('signin');
})->name('login');


Route::get('/admin-statistics', [AdminStatisticsController::class, 'index'])
    ->name('admin-statistics')
    ->middleware('auth');
    $totalDesks = Desk::count();
    $occupiedDesks = UserStatsHistory::distinct('desk_id')->count('desk_id');
    $avgSession = UserStatsHistory::selectRaw(
    'COUNT(*) * 60.0 / NULLIF(COUNT(DISTINCT user_id), 0) as avg_minutes'
    )->value('avg_minutes') ?? 0;
    $topUsers = UserStatsHistory::select('user_id')
    ->selectRaw('COUNT(*) as count')
    ->groupBy('user_id')
    ->orderByDesc('count')
    ->with('user:id,name')
    ->limit(5)
    ->get()
    ->map(fn ($row) => [
        'name' => $row->user->name ?? 'Unknown',
        'count' => (int) $row->count,
    ]);
    $heatmapRaw = UserStatsHistory::selectRaw('
        EXTRACT(DOW FROM recorded_at) as day,
        EXTRACT(HOUR FROM recorded_at) as hour,
        COUNT(*) as count
    ')
    ->groupBy('day', 'hour')
    ->get();

    $heatmapGrid = [];
    foreach ($heatmapRaw as $row) {
    $heatmapGrid[(int)$row->day][(int)$row->hour] = (int)$row->count;
    }
    $users = User::all();
    $desks = Desk::all();

    // Admin Statistics PDF Export routes
    Route::get('/admin/statistics/export/pdf', [PDFExportController::class, 'exportAdminStatsPDF'])->name('admin.statistics.export.pdf');
    Route::get('/admin/statistics/export/preview', [PDFExportController::class, 'previewAdminStatsPDF'])->name('admin.statistics.export.preview');

Route::get('/events', [EventController::class, 'index'])
    ->name('events.index');
Route::get('/event/{event}/availableUsers', [EventController::class, 'availableUsers'])->name('event.available.users');
Route::post('/event/{event}/addUser', [EventController::class, 'addUserToEvent'])->name('event.add.users');



// Users Mannagement view
Route::middleware('auth')->group(function () {
    Route::get('/users-management', [AdminController::class, 'index'])->middleware('sync.desks');
    Route::post('/user/create', [AdminController::class, 'createUser']);
    Route::post('/user/{id}/assign-desk-id', [AdminController::class, 'assignDesk']);
    Route::post('/user/{id}/unassign-desk-id', [AdminController::class, 'unassignDesk']);
    Route::post('/user/{id}/remove-user', [AdminController::class, 'removeUser']);
    Route::post('/event/{id}/approve', [AdminController::class, 'approveEvent']);
    Route::post('/event/{id}/reject', [AdminController::class, 'rejectEvent']);
    
    // Notification Management (Admin)
    Route::get('/admin/notifications', [NotificationController::class, 'adminIndex'])->name('admin.notifications');
    Route::post('/api/notifications/send-manual', [NotificationController::class, 'sendManual']);
    Route::post('/api/notifications/settings', [NotificationController::class, 'updateSettings']);
});

// Notification routes for all authenticated users
Route::middleware('auth')->group(function () {
    Route::get('/api/notifications/history', [NotificationController::class, 'getUserNotifications']);
    Route::get('/api/notifications/pending', [NotificationController::class, 'getPending']);
    Route::post('/api/notifications/mark-read', [NotificationController::class, 'markAsRead']);
});

Route::get('/profile', [ProfileController::class, 'show'])->name('profile')->middleware('auth');

Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
Route::put('/profile/update', [ProfileController::class, 'update'])->name('profile.update');
Route::delete('/profile/picture/delete', [ProfileController::class, 'deleteProfilePicture'])->name('profile.picture.delete');


Route::get('/ideal-height', function () {
    return view('ideal-height');
})->name('ideal.height')->middleware('auth');

Route::get('/posture-analysis', [HeightDetectionController::class, 'showAnalysis'])->name('posture.analysis')->middleware('auth');

Route::post('/height-detection/analyze', [HeightDetectionController::class, 'analyze'])->name('height.detection.analyze')->middleware('auth');
Route::get('/height-detection/result/{id}', [HeightDetectionController::class, 'result'])->name('height.detection.result')->middleware('auth'); // ✅ Añadir {id}

Route::get('/height-detection/history', [HeightDetectionController::class, 'history'])->name('height.detection.history')->middleware('auth');
Route::get('/height-detection/health', [HeightDetectionController::class, 'healthCheck'])->name('height.detection.health')->middleware('auth');


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

// Landing page routes
Route::get('/privacy-policy', function () {
    return view('privacy-policy');
})->name('privacy.policy');

Route::get('/terms-conditions', function () {
    return view('terms-conditions');
})->name('terms.conditions');

Route::get('/contact-us', function () {
    return view('contact-us');
})->name('contact.us');

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

    return response()->json($response,200,[],JSON_PRETTY_PRINT);
});

// Populates the desk table in the database with all the desks available from the simulator
// Also REMOVES desks that no longer exist in the API
Route::get('/sync-desks-from-api', function () {
    // Increase execution time for this operation
    set_time_limit(120); // 2 minutes
    
    $deskSyncService = new \App\Services\DeskSyncService();
    $results = $deskSyncService->syncDesksFromApi();
    
    return response()->json([
        'success' => true,
        'message' => 'Desk sync completed - database now matches API',
        'results' => $results,
        'summary' => [
            'created' => $results['created'],
            'updated' => $results['updated'],
            'deleted' => $results['deleted'],
            'errors' => count($results['errors'])
        ],
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

// Debug route: Compare API desks vs Database desks
Route::get('/debug-desks-comparison', function () {
    try {
        $apiDesks = \App\Helpers\APIMethods::getAllDesks();
        $dbDesks = \App\Models\Desk::all();
        
        return response()->json([
            'success' => true,
            'api_desks_count' => count($apiDesks),
            'api_desks' => $apiDesks,
            'db_desks_count' => $dbDesks->count(),
            'db_desks' => $dbDesks->map(function($desk) {
                return [
                    'id' => $desk->id,
                    'name' => $desk->name,
                    'desk_number' => $desk->desk_number,
                    'api_desk_id' => $desk->api_desk_id,
                ];
            }),
            'api_desk_ids_not_in_db' => array_diff($apiDesks, $dbDesks->pluck('api_desk_id')->toArray()),
            'db_desk_ids_not_in_api' => $dbDesks->pluck('api_desk_id')->diff($apiDesks)->values()
        ], 200, [], JSON_PRETTY_PRINT);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
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

// run cleaning schedule (E.g. use UptimeRobot to run this endpoint every minute to check for cleaning time)
Route::get('/run-cleaning-schedule', [EventController::class, 'runCleaningSchedule']);
