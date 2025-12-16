const canvas = document.getElementById('canvas');
const MAX_DESKS = 50;
const isAdmin = window.isAdmin || false;
const tooltip = document.getElementById('hoverTooltip');

let deskCounter = 1;
let editModeEnabled = isAdmin;
let selectedDesks = new Set();

// Drag state
let isDragging = false;
let dragStartX = 0;
let dragStartY = 0;
let draggedDesks = [];

// Selection state
let isSelecting = false;
let selectionBox = null;
let selectStartX = 0;
let selectStartY = 0;

loadDefaultLayout();

async function loadDefaultLayout() {
    try {
        const response = await fetch('/layout/load');
        const data = await response.json();
        if (data.desks && data.desks.length > 0) {
            data.desks.forEach(d => addDesk(d.x, d.y, d.name, d.id));
            deskCounter = Math.max(...data.desks.map(d => parseInt(d.name.match(/\d+/)?.[0] || 0))) + 1;
        }
    } catch (error) {
        console.error('Error loading layout:', error);
    }
}

function updateDeskCount() {
    const count = document.querySelectorAll('.desk').length;
    const deskCountElement = document.getElementById('deskCount');
    if (deskCountElement) deskCountElement.textContent = count;

    const saveBtn = document.getElementById('saveLayout');
    const downloadBtn = document.getElementById('downloadJSON');
    const uploadBtn = document.getElementById('uploadJSON');
    
    if (saveBtn) saveBtn.disabled = !editModeEnabled;
    if (downloadBtn) downloadBtn.disabled = !editModeEnabled;
    if (uploadBtn) uploadBtn.disabled = !editModeEnabled;
}

function addDesk(x, y, name = null, deskId = null) {
    const currentCount = document.querySelectorAll('.desk').length;
    if (currentCount >= MAX_DESKS) {
        if (isAdmin) alert(`Maximum of ${MAX_DESKS} desks reached!`);
        return;
    }

    const desk = document.createElement('div');
    desk.className = 'desk';
    desk.style.left = `${x}px`;
    desk.style.top = `${y}px`;
    
    if (deskId) desk.setAttribute('data-desk-id', deskId);
    
    desk.innerHTML = `
        <img src="desk_icon.png" alt="Desk" style="width:48px;height:48px;display:block;margin:0 auto">
        <div class="desk-label" style="font-size:16px;margin-top:4px">${name || `Desk ${deskCounter++}`}</div>
    `;

    desk.addEventListener('dblclick', (e) => {
        e.stopPropagation();
        const id = desk.getAttribute('data-desk-id');
        if (id) {
            window.location.href = `/desk-control/${id}`;
        } else {
            alert(isAdmin 
                ? 'Please save the layout first to enable desk control access.'
                : 'This desk has not been saved yet. Please ask an admin to save the layout first.');
        }
    });

    if (isAdmin) desk.addEventListener('mousedown', startDrag);
    
    desk.addEventListener('mouseenter', () => showTooltip(desk));
    desk.addEventListener('mouseleave', hideTooltip);

    updateDeskCursor(desk);
    canvas.appendChild(desk);
    updateDeskCount();
}

function updateDeskCursor(desk) {
    desk.style.cursor = (isAdmin && editModeEnabled) ? 'move' : 'pointer';
}

function showTooltip(desk) {
    if (!tooltip) return;
    
    tooltip.textContent = (isAdmin && editModeEnabled) 
        ? 'Drag to move • Double-click to control'
        : 'Double-click to control';
    tooltip.className = `hover-tooltip show ${(isAdmin && editModeEnabled) ? 'edit-mode' : 'view-mode'}`;
}

function hideTooltip() {
    if (tooltip) tooltip.classList.remove('show');
}

function startDrag(e) {
    if (!isAdmin || !editModeEnabled || e.shiftKey) return;
    
    e.preventDefault();
    e.stopPropagation();

    const desk = e.target.closest('.desk');
    if (!desk) return;

    isDragging = true;
    dragStartX = e.clientX;
    dragStartY = e.clientY;

    // Prepare all desks to be dragged
    if (desk.classList.contains('selected') && selectedDesks.size > 1) {
        draggedDesks = Array.from(selectedDesks).map(d => ({
            element: d,
            startX: d.offsetLeft,
            startY: d.offsetTop
        }));
        selectedDesks.forEach(d => d.classList.add('dragging'));
    } else {
        draggedDesks = [{
            element: desk,
            startX: desk.offsetLeft,
            startY: desk.offsetTop
        }];
        desk.classList.add('dragging');
    }
}

document.addEventListener('mousemove', (e) => {
    if (isDragging && draggedDesks.length > 0) {
        const dx = e.clientX - dragStartX;
        const dy = e.clientY - dragStartY;
        
        draggedDesks.forEach(({ element, startX, startY }) => {
            const newX = Math.max(0, Math.min(startX + dx, canvas.offsetWidth - element.offsetWidth));
            const newY = Math.max(0, Math.min(startY + dy, canvas.offsetHeight - element.offsetHeight));
            element.style.left = `${newX}px`;
            element.style.top = `${newY}px`;
        });
    }

    if (isSelecting && selectionBox) {
        const canvasRect = canvas.getBoundingClientRect();
        const relativeX = e.clientX - canvasRect.left;
        const relativeY = e.clientY - canvasRect.top;
        
        const left = Math.min(selectStartX, relativeX);
        const top = Math.min(selectStartY, relativeY);
        const width = Math.abs(relativeX - selectStartX);
        const height = Math.abs(relativeY - selectStartY);

        selectionBox.style.left = `${left}px`;
        selectionBox.style.top = `${top}px`;
        selectionBox.style.width = `${width}px`;
        selectionBox.style.height = `${height}px`;

        updateSelection(left, top, width, height);
    }
});

document.addEventListener('mouseup', () => {
    if (isDragging) {
        document.querySelectorAll('.desk.dragging').forEach(d => d.classList.remove('dragging'));
        isDragging = false;
        draggedDesks = [];
    }

    if (isSelecting && selectionBox) {
        selectionBox.remove();
        selectionBox = null;
        isSelecting = false;
        canvas.classList.remove('selecting');
    }
});

canvas.addEventListener('mousedown', (e) => {
    if (e.target === canvas && !e.shiftKey && isAdmin) {
        selectedDesks.forEach(d => d.classList.remove('selected'));
        selectedDesks.clear();
        return;
    }

    if (isAdmin && editModeEnabled && e.shiftKey && e.target === canvas) {
        isSelecting = true;
        canvas.classList.add('selecting');
        
        const canvasRect = canvas.getBoundingClientRect();
        selectStartX = e.clientX - canvasRect.left;
        selectStartY = e.clientY - canvasRect.top;
        
        selectionBox = document.createElement('div');
        selectionBox.className = 'selection-box';
        selectionBox.style.left = `${selectStartX}px`;
        selectionBox.style.top = `${selectStartY}px`;
        canvas.appendChild(selectionBox);
    }
});

function updateSelection(boxLeft, boxTop, boxWidth, boxHeight) {
    selectedDesks.clear();
    document.querySelectorAll('.desk').forEach(desk => {
        desk.classList.remove('selected');
        const x = desk.offsetLeft;
        const y = desk.offsetTop;
        const w = desk.offsetWidth;
        const h = desk.offsetHeight;

        if (x < boxLeft + boxWidth && x + w > boxLeft && y < boxTop + boxHeight && y + h > boxTop) {
            desk.classList.add('selected');
            selectedDesks.add(desk);
        }
    });
}

if (isAdmin) {
    const editModeToggle = document.getElementById('editModeToggle');
    if (editModeToggle) {
        editModeToggle.addEventListener('change', (e) => {
            editModeEnabled = e.target.checked;
            canvas.style.cursor = editModeEnabled ? 'grab' : 'default';
            
            document.querySelectorAll('.desk').forEach(desk => updateDeskCursor(desk));
            updateDeskCount();
            
            if (!editModeEnabled) {
                selectedDesks.forEach(d => d.classList.remove('selected'));
                selectedDesks.clear();
            }
        });
    }
}

updateDeskCount();