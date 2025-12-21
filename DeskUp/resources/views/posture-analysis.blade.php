<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DeskUp | Posture Analysis</title>
    
    <link rel="stylesheet" href="{{ asset('css/sidebar.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('css/posture-analysis.css') }}">

    <!-- Vue.js CDN -->
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
</head>
<body>
    @include('components.sidebar')
    
    <div id="height-detection-app">
        <height-detection></height-detection>
    </div>

    <!-- Archivo Vue.js separado -->
    <script src="{{ asset('js/posture-analysis.js') }}"></script>
</body>
</html>