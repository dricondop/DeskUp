<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Office Layout | DeskUp</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            overflow: hidden;
        }

        #canvas {
            width: 100vw;
            height: 100vh;
            background: #f0f0f0;
            background-image: 
                linear-gradient(rgba(0,0,0,.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0,0,0,.05) 1px, transparent 1px);
            background-size: 20px 20px;
            position: relative;
            cursor: grab;
        }

        #canvas.grabbing {
            cursor: grabbing;
        }

        #canvas.selecting {
            cursor: crosshair;
        }

        .desk {
            position: absolute;
            width: 80px;
            height: 100px;
            cursor: move;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
        }

        .desk:hover {
            transform: scale(1.05);
        }

        .desk.dragging {
            opacity: 0.7;
            z-index: 1000;
        }

        .desk.selected img {
            outline: 3px solid #e74c3c;
            outline-offset: 2px;
        }

        .desk-label {
            color: black;
            font-weight: bold;
            font-size: 14px;
            margin-top: 4px;
            text-align: center;
        }

        .selection-box {
            position: absolute;
            border: 2px dashed #3498db;
            background: rgba(52, 152, 219, 0.1);
            pointer-events: none;
            z-index: 999;
        }

        .toolbar {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 100;
        }

        .toolbar .toolbar-btn {
            padding: 10px 15px;
            margin: 5px;
            border: none;
            background: #4a90e2;
            color: white;
            border-radius: 5px;
            cursor: pointer;
            display: inline-block;
            font-family: inherit;
            font-size: inherit;
            font-weight: normal;
        }

        .toolbar .toolbar-btn:hover {
            background: #357abd;
        }

        .toolbar .toolbar-btn.delete-btn {
            background: #e74c3c;
        }

        .toolbar .toolbar-btn.delete-btn:hover {
            background: #c0392b;
        }

        .toolbar .toolbar-btn:disabled {
            background: #95a5a6;
            cursor: not-allowed;
        }

        .toolbar .toolbar-btn.file-btn {
            padding: 0;
            background: transparent;
        }

        .toolbar .toolbar-btn.file-btn:hover {
            background: transparent;
        }

        .toolbar .toolbar-btn.file-btn span {
            padding: 10px 15px;
            background: #4a90e2;
            color: white;
            border-radius: 5px;
            display: inline-block;
        }

        .toolbar .toolbar-btn.file-btn:hover span {
            background: #357abd;
        }

        .toolbar .toolbar-btn.file-btn input[type="file"] {
            display: none;
        }

        .desk-count {
            margin: 5px;
            font-size: 14px;
            color: #333;
        }
        </style>
    </head>
    <body>
        <div id="canvas"></div>
        
        <div class="toolbar">
        <div class="desk-count">Desks: <span id="deskCount">0</span>/50</div>
        <button id="addDesk" class="toolbar-btn">Add Desk</button>
        <button id="deleteSelected" class="toolbar-btn delete-btn">Delete Selected</button>
        <button id="saveLayout" class="toolbar-btn">Save Layout</button>
        <label for="loadLayout" class="toolbar-btn file-btn">
            <input type="file" id="loadLayout" accept="application/json">
            <span>Load Layout</span>
        </label>
    </div>

    <script>
        const canvas = document.getElementById('canvas');
        const MAX_DESKS = 50;
        let deskCounter = 1;
    let isDragging = false;
    let currentDesk = null;
    let offsetX, offsetY;
    let dragStartPositions = [];
    let dragStartMouse = {x: 0, y: 0};
        
        // Selection box variables
        let isSelecting = false;
        let selectionBox = null;
        let startX, startY;
        let selectedDesks = new Set();

        // Load default layout from JSON file
        loadDefaultLayout();

        async function loadDefaultLayout() {
            try {
                const response = await fetch('/default-layout.json');
                const data = await response.json();
                
                if (data.desks && Array.isArray(data.desks)) {
                    data.desks.forEach(deskData => {
                        addDesk(deskData.x, deskData.y, deskData.name);
                    });
                    // Update counter to be one more than the highest desk number
                    const maxNum = Math.max(...data.desks.map(d => {
                        const match = d.name.match(/\d+/);
                        return match ? parseInt(match[0]) : 0;
                    }));
                    deskCounter = maxNum + 1;
                }
            } catch (error) {
                console.error('Error loading default layout:', error);
            }
        }

        function updateDeskCount() {
            const count = document.querySelectorAll('.desk').length;
            document.getElementById('deskCount').textContent = count;
            document.getElementById('addDesk').disabled = count >= MAX_DESKS;
        }

        function addDesk(x, y, deskName = null) {
            const currentCount = document.querySelectorAll('.desk').length;
            if (currentCount >= MAX_DESKS) {
                alert(`Maximum of ${MAX_DESKS} desks reached!`);
                return;
            }

            const desk = document.createElement('div');
            desk.className = 'desk';
            desk.style.left = x + 'px';
            desk.style.top = y + 'px';

            // Add desk icon and number
            const img = document.createElement('img');
            img.src = 'desk_icon.png';
            img.alt = 'Desk Icon';
            img.style.width = '48px';
            img.style.height = '48px';
            img.style.display = 'block';
            img.style.margin = '0 auto';

            const label = document.createElement('div');
            label.className = 'desk-label';
            label.textContent = deskName || `Desk ${deskCounter++}`;
            label.style.fontSize = '16px';
            label.style.marginTop = '4px';

            desk.appendChild(img);
            desk.appendChild(label);
            desk.addEventListener('mousedown', startDragging);
            canvas.appendChild(desk);
            updateDeskCount();
        }

        function startDragging(e) {
            if (e.shiftKey) return; // Don't drag when selecting
            
            // Get the desk element (in case we clicked on img or label inside)
            const deskElement = e.target.closest('.desk');
            if (!deskElement) return;
            
            // If desk is selected, drag all selected desks
            if (deskElement.classList.contains('selected') && selectedDesks.size > 1) {
                isDragging = true;
                currentDesk = deskElement;
                dragStartMouse.x = e.clientX;
                dragStartMouse.y = e.clientY;
                dragStartPositions = Array.from(selectedDesks).map(desk => ({
                    desk,
                    left: desk.offsetLeft,
                    top: desk.offsetTop
                }));
                selectedDesks.forEach(desk => desk.classList.add('dragging'));
            } else {
                isDragging = true;
                currentDesk = deskElement;
                currentDesk.classList.add('dragging');
                offsetX = e.clientX - currentDesk.offsetLeft;
                offsetY = e.clientY - currentDesk.offsetTop;
            }
            e.preventDefault();
        }

        // Selection box logic
        canvas.addEventListener('mousedown', (e) => {
            // Clear selection if clicking on empty canvas (not shift-selecting)
            if (e.target === canvas && !e.shiftKey) {
                selectedDesks.forEach(desk => desk.classList.remove('selected'));
                selectedDesks.clear();
                return;
            }
            
            // Only start selection if shift is pressed and we're not clicking on a desk
            if (!e.shiftKey) return;
            if (e.target.closest('.desk')) return; // Don't select if clicking on a desk
            
            isSelecting = true;
            canvas.classList.add('selecting');
            startX = e.clientX;
            startY = e.clientY;
            
            selectionBox = document.createElement('div');
            selectionBox.className = 'selection-box';
            selectionBox.style.left = startX + 'px';
            selectionBox.style.top = startY + 'px';
            canvas.appendChild(selectionBox);
        });

        // Check if two desks overlap
        function checkCollision(desk1, x1, y1, desk2) {
            const w1 = desk1.offsetWidth;
            const h1 = desk1.offsetHeight;
            const x2 = desk2.offsetLeft;
            const y2 = desk2.offsetTop;
            const w2 = desk2.offsetWidth;
            const h2 = desk2.offsetHeight;
            
            return !(x1 + w1 <= x2 || x2 + w2 <= x1 || y1 + h1 <= y2 || y2 + h2 <= y1);
        }

        // Check if position would cause collision with any other desk
        function wouldCollide(movingDesk, newX, newY, excludeDesks = []) {
            const allDesks = document.querySelectorAll('.desk');
            for (let desk of allDesks) {
                if (desk === movingDesk || excludeDesks.includes(desk)) continue;
                if (checkCollision(movingDesk, newX, newY, desk)) {
                    return true;
                }
            }
            return false;
        }

        document.addEventListener('mousemove', (e) => {
            if (isDragging && currentDesk) {
                const canvasRect = canvas.getBoundingClientRect();
                if (currentDesk.classList.contains('selected') && selectedDesks.size > 1) {
                    // Move all selected desks with boundary check
                    const dx = e.clientX - dragStartMouse.x;
                    const dy = e.clientY - dragStartMouse.y;
                    dragStartPositions.forEach(({desk, left, top}) => {
                        let newLeft = left + dx;
                        let newTop = top + dy;
                        // Clamp to canvas boundaries
                        newLeft = Math.max(0, Math.min(newLeft, canvasRect.width - desk.offsetWidth));
                        newTop = Math.max(0, Math.min(newTop, canvasRect.height - desk.offsetHeight));
                        desk.style.left = newLeft + 'px';
                        desk.style.top = newTop + 'px';
                    });
                } else {
                    let newLeft = e.clientX - offsetX;
                    let newTop = e.clientY - offsetY;
                    // Clamp to canvas boundaries
                    newLeft = Math.max(0, Math.min(newLeft, canvasRect.width - currentDesk.offsetWidth));
                    newTop = Math.max(0, Math.min(newTop, canvasRect.height - currentDesk.offsetHeight));
                    currentDesk.style.left = newLeft + 'px';
                    currentDesk.style.top = newTop + 'px';
                }
            }
            if (isSelecting && selectionBox) {
                const currentX = e.clientX;
                const currentY = e.clientY;
                const left = Math.min(startX, currentX);
                const top = Math.min(startY, currentY);
                const width = Math.abs(currentX - startX);
                const height = Math.abs(currentY - startY);
                selectionBox.style.left = left + 'px';
                selectionBox.style.top = top + 'px';
                selectionBox.style.width = width + 'px';
                selectionBox.style.height = height + 'px';
                updateSelectedDesks(left, top, width, height);
            }
        });

        document.addEventListener('mouseup', () => {
            if (isDragging && currentDesk) {
                if (currentDesk.classList.contains('selected') && selectedDesks.size > 1) {
                    selectedDesks.forEach(desk => desk.classList.remove('dragging'));
                } else {
                    currentDesk.classList.remove('dragging');
                }
            }
            isDragging = false;
            currentDesk = null;
            dragStartPositions = [];
            if (isSelecting && selectionBox) {
                selectionBox.remove();
                selectionBox = null;
                isSelecting = false;
                canvas.classList.remove('selecting');
            }
        });

        function updateSelectedDesks(boxLeft, boxTop, boxWidth, boxHeight) {
            selectedDesks.clear();
            
            document.querySelectorAll('.desk').forEach(desk => {
                desk.classList.remove('selected');
                
                const deskRect = desk.getBoundingClientRect();
                const deskLeft = desk.offsetLeft;
                const deskTop = desk.offsetTop;
                const deskWidth = desk.offsetWidth;
                const deskHeight = desk.offsetHeight;
                
                if (deskLeft < boxLeft + boxWidth &&
                    deskLeft + deskWidth > boxLeft &&
                    deskTop < boxTop + boxHeight &&
                    deskTop + deskHeight > boxTop) {
                    desk.classList.add('selected');
                    selectedDesks.add(desk);
                }
            });
        }

        document.getElementById('addDesk').addEventListener('click', () => {
            addDesk(Math.random() * (window.innerWidth - 150), Math.random() * (window.innerHeight - 150));
        });

        document.getElementById('deleteSelected').addEventListener('click', () => {
            if (selectedDesks.size === 0) {
                alert('No desks selected. Hold Shift and drag to select desks.');
                return;
            }
            if (confirm(`Delete ${selectedDesks.size} selected desk(s)?`)) {
                selectedDesks.forEach(desk => desk.remove());
                selectedDesks.clear();
                // Rename remaining desks sequentially
                let desks = Array.from(document.querySelectorAll('.desk'));
                desks.forEach((desk, i) => {
                    const label = desk.querySelector('.desk-label');
                    if (label) label.textContent = `Desk ${i + 1}`;
                });
                deskCounter = desks.length + 1;
                updateDeskCount();
            }
        });

        updateDeskCount();
    </script>
    <script src='/js/layout-save-load.js'></script>
</body>
</html>

