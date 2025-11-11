document.addEventListener("DOMContentLoaded", () => {
    const display = document.getElementById("height-display");
    const inc = document.getElementById("increment");
    const dec = document.getElementById("decrement");

    display.textContent = desk.height + " cm"?? 0;

    // Increase / Decrease Desk height buttons
    inc.addEventListener("click", () => changeHeight(1));
    dec.addEventListener("click", () => changeHeight(-1));


    function changeHeight(number) {
        desk.height = Math.min(150, Math.max(0, desk.height + number));
        display.textContent = desk.height + " cm";
        calculateHeight(desk.height);
    }
    
    async function calculateHeight(newValue) {
        try {
            const response = await fetch(desk.url, {
                method: "POST",
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({height: newValue})
            });

            const data = await response.json(); 
            if (data.success) {
                    console.log('Height updated successfully');
                }
            } 
        catch (error) {
            console.error('Error updating height:', error);
        }
    }

    // Load deskMoveSpeed values
    const selection = document.getElementById("deskMoveSpeed");
    const maxSpeed = Number(selection.dataset.max) || 36;
    const selected = typeof desk.speed !== 'undefined' ? Number(desk.speed) : 1;

    let options = '';
    for (let i = 1; i <= maxSpeed; i++) {
        options += `<option value="${i}" ${i === selected ? 'selected' : ''}>${i} mm/s</option>`;
    }
    selection.innerHTML = options;

});




