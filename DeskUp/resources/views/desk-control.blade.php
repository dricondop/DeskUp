<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Desk Control | DeskUp</title>
    
    <link rel="stylesheet" href="{{ asset('css/sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/desk-control.css') }}">
</head>
<body>
    @include('components.sidebar')
    
    <div class="main-content">
        <main class="desk-control">
            <section class="desk-view">
                <h1>Desk Control</h1>
                <h3>Desk 101 &vert; Status: OK</h3>

               <img src="{{ asset('assets/desk.png') }}" alt="desk">
                
                <div>
                    <button class="desk-view-btns sitting">Sitting</button>
                    <button class="desk-view-btns error-log">Error log</button>
                </div>
            </section>

            <div class="desk-management">
                <div class="profile">
                    <div class="profile-pic"></div>
                    <h2>massbh</h2>
                </div>

                <section class="desk-adjustment">
                    <h1>Desk Management</h1>

                    <div class="slider-header">
                        <h3>Height Adjustment</h3>
                        <p>Speed: 36 mm/s</p>
                    </div>
                    
                    <div class="srange" id="desk-height">
                        <input type="range" min="0" max="150" value="110" id="height">
                        <span class="value" id="heightVal">110</span>
                    </div>

                    <div class="height-preset-btns">
                        <button>Sit</button>
                        <button>75</button>
                        <button>Stand</button>
                    </div>



                    <h3 class="activity">Activity</h3>
                    <!-- <div class="activity" id="activity"></div> -->
                    <div class="temp-activity-box"><p>18:00 Cleaning Schedule</p></div>
                    <div class="temp-activity-box"><p>17:40 Meeting</p></div>
                </section>
            </div>
        </main>
    </div>

    <script>
    // Single range slider: update color fill and value
    window.addEventListener("load", () => {
    document.querySelectorAll(".srange").forEach(wrap => {
        const range = wrap.querySelector('input[type="range"]');
        const valEl = wrap.querySelector(".value");
        const min = +range.min || 0, max = +range.max || 100;

        const update = () => {
        const p = ((+range.value - min) * 100) / (max - min);
        wrap.style.setProperty("--p", `${p}%`);
        valEl.textContent = range.value;
        };

        range.addEventListener("input", update);
        update(); // init
    });
    });
    </script>
</body>
</html>