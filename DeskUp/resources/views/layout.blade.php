<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Office Layout | DeskUp</title>
        <link rel="stylesheet" href="/css/layout.css">
    </head>
    <body>
        <!-- User Info -->
        <div style="position: fixed; bottom: 10px; right: 10px; background: #fff; padding: 10px 15px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); z-index: 1000; display: flex; align-items: center; gap: 15px;">
            <span style="color: #333; font-weight: 500;">
                {{ auth()->user()->name }}
                @if($isAdmin)
                    <span style="color: #4CAF50;">• Admin</span>
                @else
                    <span style="color: #666;">• User</span>
                @endif
            </span>
            <form action="{{ route('logout') }}" method="POST" style="margin: 0;">
                @csrf
                <button type="submit" style="background: #f44336; color: white; border: none; padding: 5px 15px; border-radius: 3px; cursor: pointer; font-size: 14px;">
                    Logout
                </button>
            </form>
        </div>

        <main id="canvas" data-is-admin="{{ $isAdmin ? 'true' : 'false' }}"></main>
        
        <section class="desk-count">Desks: <span id="deskCount">0</span>/50</section>
        
        @if($isAdmin)
        <nav class="toolbar">   
            <div class="toggle-container">
                <label class="toggle-label">
                    <span class="toggle-text">Edit Mode</span>
                    <input type="checkbox" id="editModeToggle" checked>
                    <span class="toggle-slider"></span>
                </label>
            </div>
            <button id="addDesk" class="toolbar-btn">Add Desk</button>
            <button id="deleteSelected" class="toolbar-btn delete-btn">Delete Selected</button>
            <button id="saveLayout" class="toolbar-btn">Save Layout</button>
        </nav>
        @endif

        <script>window.isAdmin = {{ $isAdmin ? 'true' : 'false' }};</script>
        <script src='/js/layout-drag-drop.js'></script>
        <script src='/js/layout-save-load.js'></script>
    </body>
</html>