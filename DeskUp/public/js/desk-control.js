document.addEventListener("DOMContentLoaded", () => {
    const display = document.getElementById("height-display");
    const inc = document.getElementById("increment");
    const dec = document.getElementById("decrement");

    display.textContent = desk.height + " cm" ?? 0;

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




