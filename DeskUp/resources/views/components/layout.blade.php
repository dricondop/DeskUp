<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'DeskUp' }}</title>
    
    <link rel="stylesheet" href="{{ asset('css/sidebar.css') }}">
    
    {{ $styles ?? '' }}
</head>
<body>
    <x-sidebar />
    
    <div class="main-content">
        {{ $slot }}
    </div>

    {{ $scripts ?? '' }}
</body>
</html>
