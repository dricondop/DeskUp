<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\DeskSyncService;

class APIMethods {

    // Sync desk data after any API call
    private static function syncDeskData(string $deskID): void
    {
        try {
            // Wait a moment for the desk to process
            usleep(500000); // 500ms
            
            // Sync the updated state back to database
            $syncService = new DeskSyncService();
            $syncService->syncSingleDeskData($deskID);
            
            Log::info('Desk data synced after API call', [
                'api_desk_id' => $deskID
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to sync desk data after API call', [
                'api_desk_id' => $deskID,
                'error' => $e->getMessage()
            ]);
        }
    }

    // Raise or lower a specific desk and sync the change to the database
    public static function raiseDesk(float $newHeight, string $deskID) {
        $api_key = env('DESK_API_KEY');
        $api_url = env('DESK_API_URL');

        if (empty($api_key) || empty($api_url)) {
            throw new \RuntimeException('DESK_API_KEY or DESK_API_URL is not inside the .env file.');
        }

        // Send command to API
        $response = Http::withoutVerifying()->put("{$api_url}/api/v2/{$api_key}/desks/{$deskID}/state", [
            'position_mm' => $newHeight,
        ]);

        // If successful, sync the data back to database
        if ($response->successful()) {
            self::syncDeskData($deskID);
        }

        return $response;
    }


    // Get a list of all desks (returns array of strings)
    public static function getAllDesks() {
        $api_key = env('DESK_API_KEY');
        $api_url = env('DESK_API_URL');

        if (empty($api_key)) {
            throw new \RuntimeException('DESK_API_KEY is not inside the .env file.');
        }

        $response = Http::withoutVerifying()->get("{$api_url}/api/v2/{$api_key}/desks");

        // Note: This returns a list of desk IDs, so we can't sync individual desks here
        // If you want to sync all desks after this call, use DeskSyncService::syncAllDesksData() manually

        return $response->json();
    }

    // Get the data from a specific category (config, state, usage, lastErrors)
    // It returns an array of key-value pairs, like "key = position_mm" "value = 790"
    // Also syncs the desk data to database after retrieving
    public static function getCategoryData(string $category, string $deskID) {
        $api_key = env('DESK_API_KEY');
        $api_url = env('DESK_API_URL');

        if (empty($api_key) || empty($api_url)) {
            throw new \RuntimeException('DESK_API_KEY or DESK_API_URL is not inside the .env file.');
        }

        $response = Http::withoutVerifying()->get("{$api_url}/api/v2/{$api_key}/desks/{$deskID}/{$category}");

        // Sync desk data after successful API call
        if ($response->successful()) {
            self::syncDeskData($deskID);
        }

        return $response->json();
    }

    // This method gets all the data across all categories of a particular desk
    // It returns an array of key-value pairs, but it has the category name "in the middle", this means that if 
    // you want to retrieve the position you'd have to do: "$position = $response['state']['position_mm'];"
    // Also syncs the desk data to database after retrieving
    public static function getDeskData(string $deskID) {
        $api_key = env('DESK_API_KEY');
        $api_url = env('DESK_API_URL');

        if (empty($api_key) || empty($api_url)) {
            throw new \RuntimeException('DESK_API_KEY or DESK_API_URL is not inside the .env file.');
        }

        $response = Http::withoutVerifying()->get("{$api_url}/api/v2/{$api_key}/desks/{$deskID}");

        // Sync desk data after successful API call
        if ($response->successful()) {
            self::syncDeskData($deskID);
        }

        return $response->json();
    }
}