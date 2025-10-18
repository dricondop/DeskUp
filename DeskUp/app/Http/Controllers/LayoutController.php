<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LayoutController extends Controller
{
    public function index()
    {
        return view('layout');
    }

    public function save(Request $request)
    {
        $validated = $request->validate([
            'desks' => 'required|array',
            'desks.*.name' => 'required|string',
            'desks.*.x' => 'required|numeric',
            'desks.*.y' => 'required|numeric',
        ]);

        $data = [
            'desks' => $validated['desks'],
            'saved_at' => now()->toDateTimeString()
        ];

        Storage::put('layout.json', json_encode($data, JSON_PRETTY_PRINT));

        return response()->json([
            'success' => true,
            'message' => 'Layout saved successfully'
        ]);
    }

    public function load()
    {
        if (!Storage::exists('layout.json')) {
            return response()->json([
                'success' => true,
                'desks' => [],
                'message' => 'No saved layout found'
            ]);
        }

        $data = json_decode(Storage::get('layout.json'), true);

        return response()->json([
            'success' => true,
            'desks' => $data['desks'] ?? [],
            'saved_at' => $data['saved_at'] ?? null
        ]);
    }
}
