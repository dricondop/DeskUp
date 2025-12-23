const display = document.getElementById("height-display");
const inc = document.getElementById("increment");
const dec = document.getElementById("decrement");

let allDesks = [];
let currentHeight = null;
let isEvent = false;

// Time window for open event
let eventStartTime = null;
let eventEndTime = null;

// NEW LIMITS
const MIN_HEIGHT = 68;
const MAX_HEIGHT = 132;

function addAllDesks(deskIds, initialHeight = null) {
    allDesks = [...deskIds];

    if (!allDesks.length) {
        display.textContent = "-- cm";
        return;
    }

    if (initialHeight !== null && typeof initialHeight === "number") {
        currentHeight = initialHeight;
    }

    if (currentHeight === null) {
        currentHeight = "--";
    }

    display.textContent = currentHeight + " cm";
    
    // Update 3D viewer WITHOUT initial animation
    if (window.desk3DViewer && currentHeight !== "--") {
        window.desk3DViewer.setHeight(currentHeight, false);
    }
}

// Button-hold function where btn = inc or dec
function holdButton(btn, placeholderFunction, holdDelay = 1000) {
    let activationTimeout;
    let repeatTimeout;
    let isHolding = false;
    
    const start = () => {
        if (isHolding) return;
        isHolding = true;
        placeholderFunction();
        activationTimeout = setTimeout(run, holdDelay);
    };

    const run = () => {
        if (!isHolding) return;
        placeholderFunction();
        repeatTimeout = setTimeout(run, 50);
    };

    const stop = () => {
        if (!isHolding) return;
        isHolding = false;
        clearTimeout(activationTimeout);
        clearTimeout(repeatTimeout);
        updateDesk("height");
    };

    btn.addEventListener("mousedown", start);
    btn.addEventListener("mouseup", stop);
    btn.addEventListener("mouseleave", stop);
};

holdButton(inc, () => changeHeight(1));
holdButton(dec, () => changeHeight(-1));

const presetButtons = document.querySelectorAll('.height-preset-btns button');
presetButtons.forEach(button => {
    button.addEventListener('click', () => {
        markUserAction(); // Mark that user is adjusting
        
        const targetHeight = Number(button.dataset.height);

        // Update height
        currentHeight = targetHeight;
        display.textContent = currentHeight + " cm";
        
        // Update 3D viewer WITH animation
        if (window.desk3DViewer) {
            window.desk3DViewer.setHeight(currentHeight, true);
        }
        
        // Update database
        updateDesk();
    })
})

// Change UI height - WITH NEW LIMITS
function changeHeight(number) 
{   
    if (!allDesks.length) return;
    
    markUserAction(); // Mark that user is adjusting
    
    const previousHeight = currentHeight;
    currentHeight = Math.min(MAX_HEIGHT, Math.max(MIN_HEIGHT, currentHeight + number));
    display.textContent = currentHeight + " cm";
    
    // Update 3D viewer WITH animation
    if (window.desk3DViewer) {
        window.desk3DViewer.setHeight(currentHeight, true);
    }
}

// Save desk height in database
async function updateDesk() 
{   
    // If the desks being controlled are part of an event, enforce a time window
    if (isEvent) {
        const now = new Date();

        if (!eventStartTime || !eventEndTime) {
            console.warn('Event time window is not set. Cannot adjust height');
            return;
        }
        if (now < eventStartTime || now > eventEndTime) {
            console.warn('Cannot adjust height outside of event time');
            return;
        }
    }

    for (const deskId of allDesks)
    {
        const payload = {'height': currentHeight};

        try {
            const response = await fetch(`/api/desks/${deskId}/height`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrfToken,
                },
                body: JSON.stringify(payload)
            });

            const data = await response.json(); 

            if (data.success) {
                console.log(`Height updated successfully to ${data.height} cm`);
                
                // Only update 3D viewer if server height is different
                if (window.desk3DViewer && data.height !== currentHeight) {
                    window.desk3DViewer.setHeight(data.height, true);
                    currentHeight = data.height;
                    display.textContent = currentHeight + " cm";
                }
            } else {
                console.error(`Failed to update height: ${data.message}`);
            }
        } 
        catch (error) {
            console.error(`Error updating height:`, error);
        }
    }
}

// Function for external synchronization
window.updateDeskHeight = function(height) {
    if (allDesks.length && height >= MIN_HEIGHT && height <= MAX_HEIGHT) {
        currentHeight = height;
        display.textContent = height + " cm";
        
        // Update 3D viewer WITH animation
        if (window.desk3DViewer) {
            window.desk3DViewer.setHeight(height, true);
        }
        
        updateDesk();
    }
};

// Poll desk state from API to sync "real-time" height
let pollingInterval = null;
let isUserAdjusting = false;
let lastUserAction = 0;

async function pollDeskState() {
    // Don't poll if user is actively adjusting (wait 3 seconds after last action)
    const timeSinceLastAction = Date.now() - lastUserAction;
    if (isUserAdjusting || timeSinceLastAction < 3000) {
        return;
    }
    
    //If there is no assingned desk, return
    if (!allDesks.length) return;
    
    try {
        for (const deskId of allDesks) {
            const response = await fetch(`/api/desks/${deskId}/current-state`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            });
            
            const data = await response.json();
            
            if (data.success && data.height) {
                // Only update if height is different (avoid unnecessary updates)
                if (currentHeight !== data.height) {
                    console.log(`Syncing height from API: ${data.height}cm (was ${currentHeight}cm)`);
                    currentHeight = data.height;
                    display.textContent = currentHeight + " cm";
                    
                    // Update 3D viewer
                    if (window.desk3DViewer) {
                        window.desk3DViewer.setHeight(currentHeight, true);
                    }
                }
                
                // Update status if element exists
                const statusEl = document.getElementById('deskStatus');
                if (statusEl && data.status) {
                    statusEl.textContent = data.status;
                }
            }
        }
    } catch (error) {
        console.error('Error polling desk state:', error);
    }
}

// Start polling when page loads
function startPolling(intervalMs = 5000) {
    if (pollingInterval) {
        clearInterval(pollingInterval);
    }
    
    // Initial poll
    pollDeskState();
    
    // Then poll every X seconds
    pollingInterval = setInterval(pollDeskState, intervalMs);
    console.log(`Started polling desk state every ${intervalMs/1000}s`);
}

// Stop polling
function stopPolling() {
    if (pollingInterval) {
        clearInterval(pollingInterval);
        pollingInterval = null;
        console.log('Stopped polling desk state');
    }
}

// Mark when user is adjusting to pause polling temporarily
function markUserAction() {
    lastUserAction = Date.now();
}
