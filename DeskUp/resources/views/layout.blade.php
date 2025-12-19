<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Office Layout | DeskUp</title>
    
    <link rel="stylesheet" href="{{ asset('css/sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/layout.css') }}">
    <link rel="stylesheet" href="{{ asset('css/sync-status.css') }}">
</head>
<body>
    @include('components.sidebar')
    
    <div class="main-content">
        <!-- API Status Indicator -->
        <div id="apiStatus" class="api-status-indicator checking">
            <div class="api-status-dot"></div>
            <span id="apiStatusText">Checking API...</span>
        </div>

        <!-- Hover Tooltip -->
        <div id="hoverTooltip" class="hover-tooltip"></div>

        <main id="canvas" data-is-admin="{{ $isAdmin ? 'true' : 'false' }}"></main>

        @if($isAdmin)
        <nav class="toolbar">   
            <div class="toggle-container">
                <label class="toggle-label">
                    <span class="toggle-text">Edit Mode</span>
                    <input type="checkbox" id="editModeToggle" checked>
                    <span class="toggle-slider"></span>
                </label>
            </div>
            <button id="saveLayout" class="toolbar-btn">Save Layout</button>
            <button id="downloadJSON" class="toolbar-btn">Download JSON</button>
            <button id="uploadJSON" class="toolbar-btn">Upload JSON</button>
            <input type="file" id="uploadJSONInput" accept=".json" style="display: none;">
        </nav>
        @endif

    <script>window.isAdmin = {{ $isAdmin ? 'true' : 'false' }};</script>
    <script src="{{ asset('js/api-status.js') }}"></script>
    <script src="{{ asset('js/layout-drag-drop.js') }}"></script>
    <script src="{{ asset('js/layout-save-load.js') }}"></script>
</body>
</html>