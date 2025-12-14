
const display = document.getElementById("height-display");
const inc = document.getElementById("increment");
const dec = document.getElementById("decrement");

let allDesks = [];
let currentHeight = null;
let isEvent = false;

// time window for open event
let eventStartTime = null;
let eventEndTime = null;

function addAllDesks(deskIds, initialHeight = null) {
    allDesks = [...deskIds];    // overwrites array with a new copy

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
}



// Button-Hold function where btn = inc or dec
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

const presetButtons = document.querySelectorAll('.height-preset-btns button');
presetButtons.forEach(button => {
    button.addEventListener('click', () => {
        const targetHeight = Number(button.dataset.height);

        currentHeight = targetHeight;
        display.textContent = currentHeight + " cm";
        updateDesk();
    })
})


// Change UI height
function changeHeight(number) 
{   
    if (!allDesks.length) return;
    
    currentHeight = Math.min(150, Math.max(0, currentHeight + number));

    display.textContent = currentHeight + " cm";
}

// Save desk height in database
async function updateDesk() 
{   
    // if the desks being controlled is an event, enfore a time window
    if (isEvent) {
        const now = new Date();

        if (!eventEndTime || !eventEndTime) {
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
                console.log(`Height updated successfully`);
            }
        } 
        catch (error) {
            console.error(`Error updating height:`, error);
        }
    }
}  


