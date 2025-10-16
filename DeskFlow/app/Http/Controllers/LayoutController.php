<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LayoutController extends Controller
{
    /**
     * Save the office layout to a JSON file
     */
    public function save(Request $request)
    {
        try {
            $desks = $request->input('desks', []);
            
            // Validate the data
            $request->validate([
                'desks' => 'required|array',
                'desks.*.name' => 'required|string',
                'desks.*.x' => 'required|numeric',
                'desks.*.y' => 'required|numeric',
            ]);
            
            // Save to JSON file in storage
            $layoutData = [
                'desks' => $desks,
                'saved_at' => now()->toDateTimeString()
            ];
            
            Storage::put('layout.json', json_encode($layoutData, JSON_PRETTY_PRINT));
            
            return response()->json([
                'success' => true,
                'message' => 'Layout saved successfully'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Load the office layout from JSON file
     */
    public function load()
    {
        try {
            if (Storage::exists('layout.json')) {
                $layoutJson = Storage::get('layout.json');
                $layoutData = json_decode($layoutJson, true);
                
                return response()->json([
                    'success' => true,
                    'desks' => $layoutData['desks'] ?? [],
                    'saved_at' => $layoutData['saved_at'] ?? null
                ]);
            } else {
                return response()->json([
                    'success' => true,
                    'desks' => [],
                    'message' => 'No saved layout found'
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Display the layout view
     */
    public function index()
    {
        return view('layout');
    }
}
