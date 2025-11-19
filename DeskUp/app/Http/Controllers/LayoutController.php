<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Desk;
use App\Services\DeskSyncService;

class LayoutController extends Controller
{
    protected $syncService;

    public function __construct(DeskSyncService $syncService)
    {
        $this->syncService = $syncService;
    }
    public function index()
    {
        $isAdmin = false;

        if (Auth::check()) {
            Auth::user()->refresh();
            $isAdmin = Auth::user()->isAdmin();
            
            if (!$isAdmin && Auth::user()->assigned_desk_id) {
                return redirect()->route('desk.control', ['id' => Auth::user()->assigned_desk_id]);
            }
        }

        return view('layout', [
            'isAdmin' => $isAdmin,
            'isLoggedIn' => Auth::check()
        ]);
    }

    public function save(Request $request)
    {
        $validated = $request->validate([
            'desks' => 'required|array',
            'desks.*.name' => 'required|string',
            'desks.*.x' => 'required|numeric',
            'desks.*.y' => 'required|numeric',
        ]);

        foreach ($validated['desks'] as $deskData) {
            preg_match('/\d+/', $deskData['name'], $matches);
            $deskNumber = isset($matches[0]) ? (int)$matches[0] : null;

            if ($deskNumber) {
                Desk::updateOrCreate(
                    ['desk_number' => $deskNumber],
                    [
                        'name' => $deskData['name'],
                        'position_x' => $deskData['x'],
                        'position_y' => $deskData['y']
                    ]
                );
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Layout saved successfully to database'
        ]);
    }

    public function load()
    {
        $desks = Desk::where('is_active', true)->get()->map(function ($desk) {
            return [
                'id' => $desk->id,
                'name' => $desk->name,
                'x' => $desk->position_x,
                'y' => $desk->position_y,
                'status' => $desk->status,
                'api_desk_id' => $desk->api_desk_id,
                'is_connected' => $desk->isConnectedToAPI()
            ];
        });

        return response()->json([
            'success' => true,
            'desks' => $desks,
            'saved_at' => now()->toDateTimeString()
        ]);
    }
}
