<?php

namespace App\Services;

use App\Helpers\APIMethods;
use App\Models\User;
use App\Models\Desk;
use App\Models\UserStatsHistory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeskSyncService
{
    // Sync all desks (Ids & names) from the API endpoint into the database (desks table)
    // This will create, update, and DELETE desks to match the API exactly
    public function syncDesksFromApi(): array
    {
        $syncResults = [
            'created' => 0,
            'updated' => 0,
            'deleted' => 0,
            'errors' => []
        ];

        try {
            $apiDesks = APIMethods::getAllDesks();
            
            if (empty($apiDesks)) {
                Log::warning('No desks returned from API');
                return $syncResults;
            }

            // Track which API desk IDs we've seen
            $apiDeskIds = [];
            
            // Pre-fetch all desk data in parallel to reduce API calls
            $deskDataCache = [];
            foreach ($apiDesks as $apiDeskId) {
                try {
                    $deskDataCache[$apiDeskId] = $this->fetchDeskInfo($apiDeskId);
                    $apiDeskIds[] = $apiDeskId;
                } catch (\Exception $e) {
                    Log::warning("Failed to fetch info for desk {$apiDeskId}: " . $e->getMessage());
                    // Generate fallback data
                    $deskDataCache[$apiDeskId] = [
                        'desk_number_fallback' => abs(crc32($apiDeskId)) % 9999 + 1000,
                        'desk_name_fallback' => "Desk " . substr($apiDeskId, 0, 8),
                    ];
                    $apiDeskIds[] = $apiDeskId;
                }
            }

            // Create or update desks from API using cached data
            foreach ($apiDesks as $apiDeskId) {
                try {
                    $result = $this->syncSingleDeskFromApi($apiDeskId, $deskDataCache[$apiDeskId]);
                    if ($result['created']) {
                        $syncResults['created']++;
                    } elseif ($result['updated']) {
                        $syncResults['updated']++;
                    }
                } catch (\Exception $e) {
                    $error = "Error syncing desk {$apiDeskId}: " . $e->getMessage();
                    Log::error($error);
                    $syncResults['errors'][] = $error;
                }
            }

            // Delete desks that no longer exist in the API
            // This includes desks with null api_desk_id OR desks whose api_desk_id is not in the current API list
            $desksToDelete = Desk::where(function($query) use ($apiDeskIds) {
                $query->whereNull('api_desk_id')
                      ->orWhereNotIn('api_desk_id', $apiDeskIds);
            })->get();
            
            Log::info('Deletion check', [
                'api_desk_ids_count' => count($apiDeskIds),
                'api_desk_ids_sample' => array_slice($apiDeskIds, 0, 3),
                'total_desks_in_db' => Desk::count(),
                'desks_to_delete_count' => $desksToDelete->count(),
                'desks_to_delete_sample' => $desksToDelete->take(3)->pluck('api_desk_id')->toArray(),
                'desks_with_null_api_id' => Desk::whereNull('api_desk_id')->count()
            ]);
            
            foreach ($desksToDelete as $desk) {
                try {
                    Log::info("Deleting desk {$desk->name} (API ID: {$desk->api_desk_id}) - no longer in API");
                    
                    // Remove assigned users first
                    User::where('assigned_desk_id', $desk->desk_number)->update(['assigned_desk_id' => null]);
                    
                    // Delete the desk
                    $desk->delete();
                    $syncResults['deleted']++;
                } catch (\Exception $e) {
                    $error = "Error deleting desk {$desk->api_desk_id}: " . $e->getMessage();
                    Log::error($error);
                    $syncResults['errors'][] = $error;
                }
            }

            Log::info('Desk sync completed', $syncResults);

        } catch (\Exception $e) {
            $error = 'Failed to fetch desks from API: ' . $e->getMessage();
            Log::error($error);
            $syncResults['errors'][] = $error;
        }

        return $syncResults;
    }

    // Sync current API data for ALL available desks to user_stats_history
    public function syncAllDesksData(): array
    {
        $syncResults = [
            'synced' => 0,
            'skipped' => 0,
            'errors' => [],
            'desk_details' => []
        ];

        try {
            $apiDesks = APIMethods::getAllDesks();
            
            if (empty($apiDesks)) {
                Log::warning('No desks returned from API for data sync');
                return $syncResults;
            }

            foreach ($apiDesks as $apiDeskId) {
                try {
                    $result = $this->syncSingleDeskData($apiDeskId);
                    
                    if ($result['success']) {
                        if ($result['synced'] > 0) {
                            $syncResults['synced']++;
                            $syncResults['desk_details'][] = [
                                'api_desk_id' => $apiDeskId,
                                'status' => 'synced',
                                'data' => $result['desk_data']
                            ];
                        } else {
                            $syncResults['skipped']++;
                            $syncResults['desk_details'][] = [
                                'api_desk_id' => $apiDeskId,
                                'status' => 'skipped',
                                'reason' => 'No user or desk found'
                            ];
                        }
                    } else {
                        $syncResults['errors'][] = "Desk {$apiDeskId}: " . $result['error'];
                        $syncResults['desk_details'][] = [
                            'api_desk_id' => $apiDeskId,
                            'status' => 'error',
                            'error' => $result['error']
                        ];
                    }
                } catch (\Exception $e) {
                    $error = "Error syncing desk {$apiDeskId}: " . $e->getMessage();
                    Log::error($error);
                    $syncResults['errors'][] = $error;
                    $syncResults['desk_details'][] = [
                        'api_desk_id' => $apiDeskId,
                        'status' => 'error',
                        'error' => $e->getMessage()
                    ];
                }
            }

            Log::info('All desks data sync completed', [
                'synced' => $syncResults['synced'],
                'skipped' => $syncResults['skipped'],
                'errors_count' => count($syncResults['errors'])
            ]);

        } catch (\Exception $e) {
            $error = 'Failed to sync all desks data: ' . $e->getMessage();
            Log::error($error);
            $syncResults['errors'][] = $error;
        }

        return $syncResults;
    }

    // Used in syncDesksFromApi for creating/updating one desk at a time
    // Accepts optional pre-fetched desk data to avoid redundant API calls
    private function syncSingleDeskFromApi(string $apiDeskId, ?array $deskDataCache = null): array
    {
        // Use cached data if provided, otherwise fetch from API
        $deskInfo = $deskDataCache ?? $this->fetchDeskInfo($apiDeskId);

        $deskNumber = $deskInfo['desk_number'] ?? $deskInfo['desk_number_fallback'];
        $deskName = $deskInfo['desk_name'] ?? $deskInfo['desk_name_fallback'];
        $apiDeskId = $apiDeskId;
        
        // Try to find existing desk by desk_number
        $desk = Desk::where('desk_number', $deskNumber)->first();
        
        // Generate random coordinates for new desks to avoid stacking in corner
        $randomX = rand(100, 700);
        $randomY = rand(100, 500);
        
        $deskData = [
            'name' => $deskName,
            'desk_number' => $deskNumber,
            'api_desk_id' => $apiDeskId,
            'position_x' => $desk ? $desk->position_x : $randomX,
            'position_y' => $desk ? $desk->position_y : $randomY,
            'is_active' => true,
            'user_id' => null,
        ];

        if ($desk) {
            // Update existing desk
            $desk->update($deskData);
            Log::info("Updated desk: {$deskName} (API ID: {$apiDeskId})");
            return ['created' => false, 'updated' => true, 'desk' => $desk];
        } else {
            // Create new desk
            $desk = Desk::create($deskData);
            Log::info("Created new desk: {$deskName} (API ID: {$apiDeskId})");
            return ['created' => true, 'updated' => false, 'desk' => $desk];
        }
    }

    // Sync current API data for a specific desk ID to user_stats_history
    public function syncSingleDeskData(string $apiDeskId): array
    {
        try {
            $deskData = APIMethods::getDeskData($apiDeskId);

            // Format the API response into our expected data structure
            $formattedData = [
                "api_desk_id" => $apiDeskId,
                "position_mm" => $deskData['state']['position_mm'] ?? null,
                "speed_mms" => $deskData['state']['speed_mms'] ?? null,
                "status" => $deskData['state']['status'] ?? 'Unknown',
                "isPositionLost" => $deskData['state']['isPositionLost'] ?? false,
                "isOverloadProtectionUp" => $deskData['state']['isOverloadProtectionUp'] ?? false,
                "isOverloadProtectionDown" => $deskData['state']['isOverloadProtectionDown'] ?? false,
                "isAntiCollision" => $deskData['state']['isAntiCollision'] ?? false,
                "activationsCounter" => $deskData['usage']['activationsCounter'] ?? null,
                "sitStandCounter" => $deskData['usage']['sitStandCounter'] ?? null,
                "lastErrors" => $deskData['lastErrors'] ?? [],
            ];

            $result = $this->insertDeskDataToHistory($formattedData);

            return [
                'success' => true,
                'synced' => $result ? 1 : 0,
                'desk_data' => $formattedData
            ];

        } catch (\Exception $e) {
            Log::error("Failed to sync specific desk {$apiDeskId}: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    // Insert a single desk's data into user_stats_history table
    private function insertDeskDataToHistory(array $deskData): bool
    {
        // Find desk and user based on existing data structure
        $user = $this->findUserForApiDesk($deskData['api_desk_id']);
        $desk = $this->findDeskForApiDesk($deskData['api_desk_id']);

        if (!$user || !$desk) {
            Log::warning('No user or desk found for API desk', [
                'api_desk_id' => $deskData['api_desk_id'],
                'user_found' => !!$user,
                'desk_found' => !!$desk
            ]);
            return false;
        }

        // Insert data using desk_number instead of desk->id
        UserStatsHistory::create([
            'user_id' => $user->id,
            'desk_id' => $desk->desk_number,
            'desk_height_mm' => $deskData['position_mm'],
            'desk_speed_mms' => $deskData['speed_mms'],
            'desk_status' => $deskData['status'],
            'is_position_lost' => $deskData['isPositionLost'],
            'is_overload_up' => $deskData['isOverloadProtectionUp'],
            'is_overload_down' => $deskData['isOverloadProtectionDown'],
            'is_anti_collision' => $deskData['isAntiCollision'],
            'activations_count' => $deskData['activationsCounter'],
            'sit_stand_count' => $deskData['sitStandCounter'],
            'recorded_at' => now(), // To implement
        ]);

        Log::info('Desk data synced to history', [
            'api_desk_id' => $deskData['api_desk_id'],
            'user_id' => $user->id,
            'desk_number' => $desk->desk_number,
            'height' => $deskData['position_mm']
        ]);

        return true;
    }

    // Find user for a given API desk ID (looks for assigned user or returns first admin)
    private function findUserForApiDesk(string $apiDeskId): ?User
    {
        // First, try to find the desk in our database
        $desk = $this->findDeskForApiDesk($apiDeskId);
        
        if ($desk && $desk->user_id) {
            return User::find($desk->user_id);
        }

        // Fallback: if no user is assigned to the desk, return the first admin user
        return User::where('is_admin', true)->first();
    }

    // Find desk for a given API desk ID by extracting desk_number and searching database
    private function findDeskForApiDesk(string $apiDeskId): ?Desk
    {
        try {
            $deskNumber = $this->extractDeskNumberFromApiId($apiDeskId);
            return Desk::where('desk_number', $deskNumber)->first();
        } catch (\Exception $e) {
            Log::warning("Could not find desk for API ID {$apiDeskId}: " . $e->getMessage());
            return null;
        }
    }

    // Get API desk mapping for debugging purposes (shows API ID to database desk relationship)
    public function getApiDeskMapping(): array
    {
        try {
            $apiDesks = APIMethods::getAllDesks();
            $mapping = [];

            foreach ($apiDesks as $apiDeskId) {
                try {
                    $deskData = APIMethods::getDeskData($apiDeskId);
                    $deskNumber = $this->extractDeskNumberFromApiId($apiDeskId);
                    $desk = Desk::where('desk_number', $deskNumber)->first();
                    
                    $mapping[] = [
                        'api_desk_id' => $apiDeskId,
                        'api_desk_name' => $deskData['config']['name'] ?? 'Unknown',
                        'extracted_desk_number' => $deskNumber,
                        'found_in_db' => !!$desk,
                        'db_desk_name' => $desk ? $desk->name : null,
                        'assigned_user' => $desk && $desk->user_id ? $desk->user->name : null
                    ];
                } catch (\Exception $e) {
                    $mapping[] = [
                        'api_desk_id' => $apiDeskId,
                        'error' => $e->getMessage()
                    ];
                }
            }

            return $mapping;
        } catch (\Exception $e) {
            Log::error('Error getting API desk mapping: ' . $e->getMessage());
            return [];
        }
    }

    // Extract desk number from API desk ID by parsing the desk name from config
    private function extractDeskNumberFromApiId(string $apiDeskId): int
    {
        try {
            $deskData = APIMethods::getDeskData($apiDeskId);
            $deskName = $deskData['config']['name'] ?? null;

            // Try to extract number from desk name (e.g. "DESK 3677" -> 3677)
            if ($deskName && preg_match('/(\d+)/', $deskName, $matches)) {
                return (int) $matches[1];
            }
        } catch (\Exception $e) {
            Log::warning("Could not extract desk number from name for {$apiDeskId}: " . $e->getMessage());
        }

        // Fallback: generate number from API ID hash
        return abs(crc32($apiDeskId)) % 9999 + 1000;
    }
    
    // Extract desk name from API desk ID and format it as "Desk [number]"
    private function extractDeskNameFromApiId(string $apiDeskId): string
    {
        try {
            $deskNumber = $this->extractDeskNumberFromApiId($apiDeskId);
            return "Desk " . $deskNumber;
        } catch (\Exception $e) {
            Log::warning("Could not fetch desk name for {$apiDeskId}: " . $e->getMessage());
        }
        
        // Fallback: generate name from ID
        return "Desk " . substr($apiDeskId, 0, 8);
    }


     // Extract desk number and desk name from API desk ID
    private function fetchDeskInfo(string $apiDeskId): array
    {
        try {
            $deskData = APIMethods::getDeskData($apiDeskId);
            $deskName = $deskData['config']['name'] ?? null;

            $deskNumber = null;
            // Try to extract number from desk name (e.g. "DESK 3677" -> 3677)
            if ($deskName && preg_match('/(\d+)/', $deskName, $matches)) {
                $deskNumber = (int) $matches[1];
            }

            return [
                'desk_number' => $deskNumber,
                'desk_name' => $deskNumber ? "Desk {$deskNumber}" : null,
            ];
        }
        catch (\Exception $e) {
            Log::warning("Could not fetch desk info for {$apiDeskId}: " . $e->getMessage());
            return [
                'desk_number_fallback' => abs(crc32($apiDeskId)) % 9999 + 1000,
                'desk_name_fallback' => "Desk " . substr($apiDeskId, 0, 8),
            ];
        }
    }

    /**
     * Check if the external desk API is online and accessible
     * 
     * @return bool True if API is healthy, false otherwise
     */
    public function checkApiHealth(): bool
    {
        try {
            $apiDesks = APIMethods::getAllDesks();
            
            // If we get a response (even if empty array), API is online
            return is_array($apiDesks);
        } catch (\Exception $e) {
            Log::warning('API health check failed: ' . $e->getMessage());
            return false;
        }
    }
}
