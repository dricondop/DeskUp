<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Events — DeskUp</title>

    <link rel="stylesheet" href="{{ asset('css/events.css') }}">
    <link rel="stylesheet" href="{{ asset('css/sidebar.css') }}">
</head>
<body>
    @include('components.sidebar')

    <div class="main-content">
        <header>
            <div class="header-content">
                <h1>Upcoming Events</h1>
                <span class="add-event">&#43;</span>
            </div>
            <div>
                <button>Upcoming</button>
            </div>
        </header>

    </div>
</body>
</html>
