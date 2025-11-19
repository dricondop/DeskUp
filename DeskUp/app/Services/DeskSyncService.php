<?php

namespace App\Services;

use App\Helpers\APIMethods;
use App\Models\Desk;
use Illuminate\Support\Facades\Log;

class DeskSyncService
{
    /**
     * Sync all desks from API to database
     * This method fetches all desks from the API and creates/updates them in the database
     */
    public function syncFromAPI(): array
    {
        try {
            // Get all desk IDs from the API
            $apiDeskIds = APIMethods::getAllDesks();
            
            if (!is_array($apiDeskIds)) {
                throw new \Exception('Failed to retrieve desk list from API');
            }

            $syncResults = [
                'total_api_desks' => count($apiDeskIds),
                'created' => 0,
                'updated' => 0,
                'unchanged' => 0,
                'errors' => []
            ];

            foreach ($apiDeskIds as $apiDeskId) {
                try {
                    $this->syncSingleDesk($apiDeskId, $syncResults);
                } catch (\Exception $e) {
                    $syncResults['errors'][] = "Desk {$apiDeskId}: {$e->getMessage()}";
                    Log::error("Failed to sync desk {$apiDeskId}", ['error' => $e->getMessage()]);
                }
            }

            // Mark desks not in API as inactive
            $this->markMissingDesksInactive($apiDeskIds);

            // Log sync summary (only if there were changes)
            if ($syncResults['created'] > 0 || $syncResults['updated'] > 0) {
                Log::info('Background sync completed', [
                    'created' => $syncResults['created'],
                    'updated' => $syncResults['updated'],
                    'total' => $syncResults['total_api_desks']
                ]);
            }

            return $syncResults;

        } catch (\Exception $e) {
            Log::error('Desk sync failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Sync a single desk from API to database
     */
    private function syncSingleDesk(string $apiDeskId, array &$syncResults): void
    {
        // Get full desk data from API
        $apiDeskData = APIMethods::getDeskData($apiDeskId);

        if (!$apiDeskData) {
            throw new \Exception('Failed to retrieve desk data');
        }

        // Find existing desk or create new one
        $desk = Desk::where('api_desk_id', $apiDeskId)->first();

        $deskData = [
            'api_desk_id' => $apiDeskId,
            'name' => $apiDeskData['config']['name'] ?? "Desk {$apiDeskId}",
            'status' => $apiDeskData['state']['status'] ?? 'Unknown',
            'height' => isset($apiDeskData['state']['position_mm']) 
                ? round($apiDeskData['state']['position_mm'] / 10) // Convert mm to cm
                : 110,
            'speed' => $apiDeskData['state']['speed_mms'] ?? 36,
            'is_active' => true
        ];

        if ($desk) {
            $hasChanges = $desk->status !== $deskData['status'] ||
                         $desk->height !== $deskData['height'] ||
                         $desk->speed !== $deskData['speed'] ||
                         $desk->is_active !== $deskData['is_active'];
            
            if ($hasChanges) {
                // Update existing desk, explicitly preserving position_x, position_y, user_id, and name
                $desk->status = $deskData['status'];
                $desk->height = $deskData['height'];
                $desk->speed = $deskData['speed'];
                $desk->is_active = $deskData['is_active'];
                $desk->save();
                $syncResults['updated']++;
            } else {
                $syncResults['unchanged']++;
            }
        } else {
            // Create new desk
            $deskNumber = Desk::max('desk_number') + 1 ?? 1;
            $deskData['desk_number'] = $deskNumber;
            $deskData['name'] = "Desk {$deskNumber}";
            
            Desk::create($deskData);
            $syncResults['created']++;
        }
    }

    /**
     * Mark desks that are no longer in the API as inactive
     */
    private function markMissingDesksInactive(array $apiDeskIds): void
    {
        Desk::whereNotNull('api_desk_id')
            ->whereNotIn('api_desk_id', $apiDeskIds)
            ->update(['is_active' => false]);
    }

    /**
     * Update desk position in API
     */
    public function updateDeskPosition(Desk $desk, float $newHeight): array
    {
        if (!$desk->api_desk_id) {
            throw new \Exception('Desk is not connected to API');
        }

        // Convert cm to mm
        $heightInMm = $newHeight * 10;

        $response = APIMethods::raiseDesk($heightInMm, $desk->api_desk_id);

        if ($response->successful()) {
            $desk->update(['height' => $newHeight]);
            return [
                'success' => true,
                'height' => $newHeight,
                'message' => 'Desk position updated successfully'
            ];
        }

        throw new \Exception('Failed to update desk position in API');
    }

    /**
     * Sync desk state from API to database
     */
    public function syncDeskState(Desk $desk): void
    {
        if (!$desk->api_desk_id) {
            return;
        }

        $stateData = APIMethods::getCategoryData('state', $desk->api_desk_id);

        if ($stateData) {
            $desk->update([
                'height' => isset($stateData['position_mm']) 
                    ? round($stateData['position_mm'] / 10) 
                    : $desk->height,
                'speed' => $stateData['speed_mms'] ?? $desk->speed,
                'status' => $stateData['status'] ?? $desk->status,
            ]);
        }
    }

    /**
     * Get real-time desk data from API
     */
    public function getRealTimeDeskData(Desk $desk): ?array
    {
        if (!$desk->api_desk_id) {
            return null;
        }

        try {
            $apiData = APIMethods::getDeskData($desk->api_desk_id);
            
            if ($apiData) {
                return [
                    'position_mm' => $apiData['state']['position_mm'] ?? null,
                    'position_cm' => isset($apiData['state']['position_mm']) 
                        ? round($apiData['state']['position_mm'] / 10, 1) 
                        : null,
                    'speed_mms' => $apiData['state']['speed_mms'] ?? null,
                    'status' => $apiData['state']['status'] ?? 'Unknown',
                    'isPositionLost' => $apiData['state']['isPositionLost'] ?? false,
                    'isOverloadProtectionUp' => $apiData['state']['isOverloadProtectionUp'] ?? false,
                    'isOverloadProtectionDown' => $apiData['state']['isOverloadProtectionDown'] ?? false,
                    'isAntiCollision' => $apiData['state']['isAntiCollision'] ?? false,
                    'usage' => $apiData['usage'] ?? null,
                    'lastErrors' => $apiData['lastErrors'] ?? []
                ];
            }
        } catch (\Exception $e) {
            Log::error("Failed to get real-time data for desk {$desk->id}", [
                'error' => $e->getMessage()
            ]);
        }

        return null;
    }

    /**
     * Check if API is available
     */
    public function isAPIAvailable(): bool
    {
        try {
            $desks = APIMethods::getAllDesks();
            return is_array($desks);
        } catch (\Exception $e) {
            Log::warning('API availability check failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
