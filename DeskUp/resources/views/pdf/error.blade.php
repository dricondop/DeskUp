<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDF Error - DeskUp</title>
    <link rel="stylesheet" href="{{ asset('css/pdf-error.css') }}">
</head>
<body>
    <div class="error-container">
        <div class="error-icon">⚠️</div>
        <h1>PDF Generation Failed</h1>
        
        <div class="error-message">
            {{ $message ?? 'There was an error generating the PDF report.' }}
        </div>
        
        <a href="/health" class="btn">Return to Health Insights</a>
        
        <div class="technical-info">
            <h3>Technical Information</h3>
            <p>Error occurred during PDF generation. This could be due to:</p>
            <ul>
                <li>Insufficient data for the selected period</li>
                <li>Temporary service interruption</li>
                <li>Network connectivity issues</li>
            </ul>
            <p>Please try again in a few moments. If the problem persists, contact support.</p>
        </div>
        
        <div class="timestamp">
            Error occurred on: {{ date('Y-m-d H:i:s') }}
        </div>
    </div>
</body>
</html>