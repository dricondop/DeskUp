document.addEventListener("DOMContentLoaded", () => {
    const display = document.getElementById("height-display");
    const inc = document.getElementById("increment");
    const dec = document.getElementById("decrement");
    const statusDisplay = document.getElementById("deskStatus");

    display.textContent = desk.height + " cm" ?? 0;

    // Poll for real-time desk data every 2 seconds
    let pollingInterval;
    if (desk.id) {
        pollingInterval = setInterval(fetchRealTimeData, 2000);
    }

    async function fetchRealTimeData() {
        try {
            const response = await fetch(`/api/desks/${desk.id}/realtime`, {
                headers: {
                    "X-CSRF-TOKEN": csrfToken,
                }
            });

            const result = await response.json();
            
            if (result.success && result.data && result.connected) {
                // Update height display if changed
                if (result.data.position_cm !== null) {
                    const newHeight = Math.round(result.data.position_cm);
                    if (newHeight !== desk.height) {
                        desk.height = newHeight;
                        display.textContent = desk.height + " cm";
                    }
                }
                
                // Update status
                if (result.data.status && statusDisplay) {
                    statusDisplay.textContent = result.data.status;
                }

                // Update speed if available
                if (result.data.speed_mms !== null) {
                    desk.speed = result.data.speed_mms;
                }
            }
        } catch (error) {
            console.error('Error fetching real-time data:', error);
        }
    }

    // Clean up interval when page unloads
    window.addEventListener('beforeunload', () => {
        if (pollingInterval) {
            clearInterval(pollingInterval);
        }
    });

    // Button-Hold function
    function holdButton(btn, placeholderFunction, holdDelay = 1000) {
        let activationTimeout;
        let repeatTimeout;
        let isHolding = false;
        
        const start = () => {
            if (isHolding) return;
            isHolding = true;
            placeholderFunction(); // Immediately changes +/- 1 value, functions as ordinary click
            activationTimeout = setTimeout(run, holdDelay);
        };

        const run = () => {
            if (!isHolding) return;
            placeholderFunction();
            // const repeatDelay = desk.speed || 1;
            repeatTimeout = setTimeout(run, 50);
        };

        const stop = () => {
            if (!isHolding) return;
            isHolding = false;
            clearTimeout(activationTimeout);
            clearTimeout(repeatTimeout);
            updateDesk("height"); // Sends final height to database
        };

        btn.addEventListener("mousedown", start);
        btn.addEventListener("mouseup", stop);
        btn.addEventListener("mouseleave", stop);
    };

    holdButton(inc, () => changeHeight(1));
    holdButton(dec, () => changeHeight(-1));


    function changeHeight(number) {
        desk.height = Math.min(150, Math.max(0, desk.height + number));
        display.textContent = desk.height + " cm";
    }
    
    async function updateDesk(field, value) {

        if (typeof value === "undefined") {
            if (field === "height") value = desk.height;
            if (field === "speed") value = desk.speed;
        }

        const pair = {};
        pair[field] = value; // This will e.g. give { height: 100 }


        try {
            const response = await fetch(`${desk.url}/${field}`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrfToken,
                },
                body: JSON.stringify(pair)
            });

            const data = await response.json(); 
            if (data.success) {
                    console.log(`${field} updated successfully`);
                }
            } 
        catch (error) {
            console.error(`Error updating ${field}:`, error);
        }
    }  
});




