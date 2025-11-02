const phrases = [
    'smart ergonomics for a better workspace.',
    'insights that protect your posture.',
    'intelligent height control, simplified.',
    'data that helps teams thrive.',
    'your desk, optimized for wellness.',
    'technology meets ergonomics.',
    'the future of work is here.'
];

let current = 0;
const textDuration = 4500; // Duration each text is displayed in milliseconds
const el = document.getElementById('dynamic-text');

function changeText() {
    el.classList.add('fade-out');
    
    setTimeout(() => {
        current = (current + 1) % phrases.length;
        el.textContent = phrases[current];
        el.classList.remove('fade-out');
    }, 900); // This number is the transition time and it´s linked to the CSS, so make sure they match
}

setInterval(changeText, textDuration);