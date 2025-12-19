<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\DeskSyncService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SyncDesksBeforeView
{
    /**
     * Handle an incoming request.
     * Syncs desks from API before loading views that display desk data.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only sync once every 2 minutes to avoid excessive API calls
        // Uses cache to prevent concurrent requests from triggering multiple syncs
        $cacheKey = 'desks_sync_in_progress';
        $lastSyncKey = 'desks_last_sync_time';
        
        $lastSync = Cache::get($lastSyncKey);
        $syncInProgress = Cache::get($cacheKey);
        $now = now();
        
        // Check if sync is needed (more than 2 minutes since last sync and no sync in progress)
        if (!$syncInProgress && (!$lastSync || $now->diffInMinutes($lastSync) >= 2)) {
            // Set flag to prevent concurrent syncs
            Cache::put($cacheKey, true, now()->addSeconds(30));
            
            try {
                $deskSyncService = new DeskSyncService();
                $results = $deskSyncService->syncDesksFromApi();
                
                // Update last sync time
                Cache::put($lastSyncKey, $now, now()->addMinutes(30));
                
                Log::info('Desks synced via middleware', [
                    'created' => $results['created'],
                    'updated' => $results['updated'],
                    'deleted' => $results['deleted'],
                    'route' => $request->path()
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to sync desks in middleware: ' . $e->getMessage());
                // Continue even if sync fails - don't block the request
            } finally {
                // Clear sync in progress flag
                Cache::forget($cacheKey);
            }
        }
        
        return $next($request);
    }
}
