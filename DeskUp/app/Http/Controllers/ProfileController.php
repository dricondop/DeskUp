<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager; 
use Intervention\Image\Drivers\Gd\Driver; 

class ProfileController extends Controller
{
    public function show()
    {
        $isAdmin = false;
        $user = null;
        $userProfile = null;

        if (Auth::check()) {
            Auth::user()->refresh();
            $user = Auth::user();
            $isAdmin = Auth::user()->is_admin; 
            
            // Get the user's profile from user_profiles
            $userProfile = DB::table('user_profiles')
                ->where('user_id', $user->id)
                ->first();
        }

        return view('profile', [
            'user' => $user,
            'userProfile' => $userProfile,
            'isAdmin' => $isAdmin
        ]);
    }

    public function edit()
    {
        $user = Auth::user();
        $userProfile = DB::table('user_profiles')
            ->where('user_id', $user->id)
            ->first();

        return view('edit-profile', [
            'user' => $user,
            'userProfile' => $userProfile
        ]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        
        // Validate input data
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date',
            'location' => 'nullable|string|max:255',
            'ideal_height' => 'nullable|numeric',
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048'
        ]);

        try {
            DB::transaction(function () use ($user, $validated, $request) {
                // Update users table
                DB::table('users')
                    ->where('id', $user->id)
                    ->update([
                        'name' => $validated['name'],
                        'email' => $validated['email'],
                        'updated_at' => now()
                    ]);

                // Handle profile picture upload
                $profilePicturePath = null;
                if ($request->hasFile('profile_picture')) {
                    $profilePicturePath = $this->handleProfilePictureUpload($request->file('profile_picture'), $user->id);
                }

                // Prepare profile data
                $profileData = [
                    'phone' => $validated['phone'],
                    'date_of_birth' => $validated['date_of_birth'],
                    'location' => $validated['location'],
                    'ideal_height' => $validated['ideal_height'],
                    'updated_at' => now()
                ];

                // Add new profile picture to the profile data
                if ($profilePicturePath) {
                    $profileData['profile_picture'] = $profilePicturePath;
                }

                $existingProfile = DB::table('user_profiles')
                    ->where('user_id', $user->id)
                    ->exists();

                if ($existingProfile) {
                    DB::table('user_profiles')
                        ->where('user_id', $user->id)
                        ->update($profileData);
                } else {
                    $profileData['user_id'] = $user->id;
                    $profileData['created_at'] = now();
                    DB::table('user_profiles')->insert($profileData);
                }
            });

            return redirect()->route('profile')->with('success', 'Profile updated successfully!');
            
        } catch (\Exception $e) {
            return back()->with('error', 'Error updating profile: ' . $e->getMessage());
        }
    }

    /**
     * Handles the upload and processing of the profile picture
     */
    private function handleProfilePictureUpload($file, $userId)
    {
        // Create directory if it does not exist
        $directory = 'profile-pictures';
        if (!Storage::disk('public')->exists($directory)) {
            Storage::disk('public')->makeDirectory($directory);
        }

        // Generate a unique filename
        $filename = 'profile_' . $userId . '_' . time() . '.webp';
        $filePath = $directory . '/' . $filename;

        // Process and optimize the image using Intervention Image v3
        $manager = new ImageManager(new Driver());
        $image = $manager->read($file->getRealPath());
        
        // Resize to a 200x200 square
        $image->cover(200, 200);

        // Save processed image as WebP
        $image->save(storage_path('app/public/' . $filePath), 80, 'webp');

        // Return relative path for database storage
        return 'storage/' . $filePath;
    }

    /**
     * Deletes the profile picture
     */
    public function deleteProfilePicture(Request $request)
    {
        $user = Auth::user();
        
        try {
            $userProfile = DB::table('user_profiles')
                ->where('user_id', $user->id)
                ->first();

            if ($userProfile && $userProfile->profile_picture) {
                // Delete physical file
                $filePath = str_replace('storage/', '', $userProfile->profile_picture);
                if (Storage::disk('public')->exists($filePath)) {
                    Storage::disk('public')->delete($filePath);
                }

                // Update database
                DB::table('user_profiles')
                    ->where('user_id', $user->id)
                    ->update([
                        'profile_picture' => null,
                        'updated_at' => now()
                    ]);
            }

            return response()->json(['success' => true, 'message' => 'Profile picture removed successfully']);
            
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error removing profile picture'], 500);
        }
    }
}
