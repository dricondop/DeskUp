<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8" />
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $desk->name }} - Desk Control</title>
        <link rel="stylesheet" href="{{ asset('css/desk-control.css') }}">
    </head>
    <body>
        <main class="desk-control">
            <section class="desk-view">
                <h1>Desk Control</h1>
                <h3>{{ $desk->name }} &vert; Status: <span id="deskStatus">{{ $desk->status }}</span></h3>

               <img src="{{ asset('assets/desk.png') }}" alt="desk">
                
                <div>
                    <button class="desk-view-btns sitting">Sitting</button>
                    <button class="desk-view-btns error-log">Error log</button>
                </div>
            </section>

            <div class="desk-management">
                <div class="profile">
                    @if($isLoggedIn)
                        <div class="profile-pic"></div>
                        <h2>{{ auth()->user()->name }}</h2>
                    @else
                        <div class="profile-pic"></div>
                        <h2>Guest</h2>
                    @endif
                </div>

                <section class="desk-adjustment">
                    <h1>Desk Management</h1>

                    <div class="slider-header">
                        <h3>Height Adjustment</h3>
                        <p>Speed: {{ $desk->speed }} mm/s</p>
                    </div>
                    
                    <div class="srange" id="desk-height">
                        <input type="range" min="0" max="150" value="{{ $desk->height }}" id="height">
                        <span class="value" id="heightVal">{{ $desk->height }}</span>
                    </div>

                    <div class="height-preset-btns">
                        <button data-height="75">Sit</button>
                        <button data-height="100">75</button>
                        <button data-height="120">Stand</button>
                    </div>

                    <h3 class="activity">Activity</h3>
                    <div id="activityList">
                        @forelse($desk->activities()->orderBy('scheduled_at', 'desc')->limit(5)->get() as $activity)
                            <div class="temp-activity-box">
                                <p>{{ $activity->scheduled_at->format('H:i') }} {{ $activity->description }}</p>
                            </div>
                        @empty
                            <div class="temp-activity-box">
                                <p>No scheduled activities</p>
                            </div>
                        @endforelse
                    </div>

                    <div style="margin-top: 20px;">
                        <a href="/layout" style="display: inline-block; padding: 10px 20px; background: #4CAF50; color: white; text-decoration: none; border-radius: 5px;">
                            Back to Layout
                        </a>
                    </div>
                </section>
            </div>
        </main>
    </body>
</html>
<script>
    const deskId = {{ $desk->id }};
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

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
            
            range.addEventListener("change", async () => {
                try {
                    const response = await fetch(`/api/desks/${deskId}/height`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({ height: range.value })
                    });
                    
                    const data = await response.json();
                    if (data.success) {
                        console.log('Height updated successfully');
                    }
                } catch (error) {
                    console.error('Error updating height:', error);
                }
            });

            update();
        });
    });

    document.querySelectorAll('.height-preset-btns button').forEach(btn => {
        btn.addEventListener('click', () => {
            const height = btn.getAttribute('data-height');
            const heightSlider = document.getElementById('height');
            heightSlider.value = height;
            
            heightSlider.dispatchEvent(new Event('input'));
            heightSlider.dispatchEvent(new Event('change'));
        });
    });
</script>


