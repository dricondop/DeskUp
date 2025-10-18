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
        <main id="canvas"></main>
        
        <nav class="toolbar">
            <section class="desk-count">Desks: <span id="deskCount">0</span>/50</section>
            <button id="addDesk" class="toolbar-btn">Add Desk</button>
            <button id="deleteSelected" class="toolbar-btn delete-btn">Delete Selected</button>
            <button id="saveLayout" class="toolbar-btn">Save Layout</button>
            <label for="loadLayout" class="toolbar-btn file-btn">
            <input type="file" id="loadLayout" accept="application/json">
            <span>Load Layout</span>
            </label>
        </nav>

        <script src='/js/layout-drag-drop.js'></script>
        <script src='/js/layout-save-load.js'></script>
    </body>
</html>