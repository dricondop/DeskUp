const canvas = document.getElementById('canvas');
const MAX_DESKS = 50;
let deskCounter = 1;
let isDragging = false;
let isSelecting = false;
let selectedDesks = new Set();
let dragState = { desk: null, offsetX: 0, offsetY: 0, startPositions: [] };
let selectionState = { box: null, startX: 0, startY: 0 };
let editModeEnabled = true;

loadDefaultLayout();

async function loadDefaultLayout() {
    try {
        const response = await fetch('/default-layout.json');
        const data = await response.json();
        if (data.desks) {
            data.desks.forEach(d => addDesk(d.x, d.y, d.name));
            deskCounter = Math.max(...data.desks.map(d => parseInt(d.name.match(/\d+/)?.[0] || 0))) + 1;
        }
    } catch (error) {
        console.error('Error loading layout:', error);
    }
}

function updateDeskCount() {
    const count = document.querySelectorAll('.desk').length;
    document.getElementById('deskCount').textContent = count;
    document.getElementById('addDesk').disabled = count >= MAX_DESKS;
}

function addDesk(x, y, name = null) {
    if (document.querySelectorAll('.desk').length >= MAX_DESKS) {
        alert(`Maximum of ${MAX_DESKS} desks reached!`);
        return;
    }

    const desk = document.createElement('div');
    desk.className = 'desk';
    desk.style.left = `${x}px`;
    desk.style.top = `${y}px`;
    desk.innerHTML = `
                <img src="desk_icon.png" alt="Desk" style="width:48px;height:48px;display:block;margin:0 auto">
                <div class="desk-label" style="font-size:16px;margin-top:4px">${name || `Desk ${deskCounter++}`}</div>
            `;
    desk.addEventListener('mousedown', startDrag);
    canvas.appendChild(desk);
    updateDeskCount();
}

function startDrag(e) {
    if (e.shiftKey || !editModeEnabled) return;
    e.preventDefault();

    const desk = e.target.closest('.desk');
    if (!desk) return;

    isDragging = true;
    dragState.desk = desk;

    if (desk.classList.contains('selected') && selectedDesks.size > 1) {
        // Multi-desk drag
        dragState.startPositions = Array.from(selectedDesks).map(d => ({
            desk: d,
            x: d.offsetLeft,
            y: d.offsetTop
        }));
        dragState.offsetX = e.clientX;
        dragState.offsetY = e.clientY;
        selectedDesks.forEach(d => d.classList.add('dragging'));
    } else {
        // Single desk drag
        dragState.offsetX = e.clientX - desk.offsetLeft;
        dragState.offsetY = e.clientY - desk.offsetTop;
        desk.classList.add('dragging');
    }
}

function clampToCanvas(x, y, width, height) {
    return {
        x: Math.max(0, Math.min(x, window.innerWidth - width)),
        y: Math.max(0, Math.min(y, window.innerHeight - height))
    };
}

document.addEventListener('mousemove', (e) => {
    if (isDragging && dragState.desk) {
        if (dragState.startPositions.length > 0) {
            // Multi-desk drag
            const dx = e.clientX - dragState.offsetX;
            const dy = e.clientY - dragState.offsetY;
            dragState.startPositions.forEach(({ desk, x, y }) => {
                const pos = clampToCanvas(x + dx, y + dy, desk.offsetWidth, desk.offsetHeight);
                desk.style.left = `${pos.x}px`;
                desk.style.top = `${pos.y}px`;
            });
        } else {
            // Single desk drag
            const pos = clampToCanvas(
                e.clientX - dragState.offsetX,
                e.clientY - dragState.offsetY,
                dragState.desk.offsetWidth,
                dragState.desk.offsetHeight
            );
            dragState.desk.style.left = `${pos.x}px`;
            dragState.desk.style.top = `${pos.y}px`;
        }
    }

    if (isSelecting && selectionState.box) {
        const left = Math.min(selectionState.startX, e.clientX);
        const top = Math.min(selectionState.startY, e.clientY);
        const width = Math.abs(e.clientX - selectionState.startX);
        const height = Math.abs(e.clientY - selectionState.startY);

        Object.assign(selectionState.box.style, {
            left: `${left}px`,
            top: `${top}px`,
            width: `${width}px`,
            height: `${height}px`
        });

        updateSelection(left, top, width, height);
    }
});

document.addEventListener('mouseup', () => {
    if (isDragging) {
        document.querySelectorAll('.desk.dragging').forEach(d => d.classList.remove('dragging'));
        isDragging = false;
        dragState = { desk: null, offsetX: 0, offsetY: 0, startPositions: [] };
    }

    if (isSelecting && selectionState.box) {
        selectionState.box.remove();
        selectionState.box = null;
        isSelecting = false;
        canvas.classList.remove('selecting');
    }
});

// Start selection box
canvas.addEventListener('mousedown', (e) => {
    if (e.target === canvas && !e.shiftKey) {
        selectedDesks.forEach(d => d.classList.remove('selected'));
        selectedDesks.clear();
        return;
    }

    if (e.shiftKey && e.target === canvas) {
        isSelecting = true;
        canvas.classList.add('selecting');
        selectionState.startX = e.clientX;
        selectionState.startY = e.clientY;
        selectionState.box = document.createElement('div');
        selectionState.box.className = 'selection-box';
        selectionState.box.style.left = `${e.clientX}px`;
        selectionState.box.style.top = `${e.clientY}px`;
        canvas.appendChild(selectionState.box);
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

document.getElementById('addDesk').addEventListener('click', () => {
    addDesk(
        Math.random() * (window.innerWidth - 150),
        Math.random() * (window.innerHeight - 150)
    );
});

document.getElementById('deleteSelected').addEventListener('click', () => {
    if (selectedDesks.size === 0) {
        alert('No desks selected. Hold Shift and drag to select desks.');
        return;
    }

    if (confirm(`Delete ${selectedDesks.size} selected desk(s)?`)) {
        selectedDesks.forEach(d => d.remove());
        selectedDesks.clear();

        document.querySelectorAll('.desk').forEach((desk, i) => {
            const label = desk.querySelector('.desk-label');
            if (label) label.textContent = `Desk ${i + 1}`;
        });
        deskCounter = document.querySelectorAll('.desk').length + 1;
        updateDeskCount();
    }
});

// Toggle move mode
document.getElementById('moveModeToggle').addEventListener('change', (e) => {
    editModeEnabled = e.target.checked;
    canvas.style.cursor = editModeEnabled ? 'grab' : 'default';
    document.querySelectorAll('.desk').forEach(desk => {
        desk.style.cursor = editModeEnabled ? 'move' : 'default';
    });
});

updateDeskCount();