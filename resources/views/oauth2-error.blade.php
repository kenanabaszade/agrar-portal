<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OAuth2 Error - Agrar Portal</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .error-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        .error-icon {
            color: #f44336;
            font-size: 48px;
            margin-bottom: 20px;
        }
        .error-message {
            color: #333;
            font-size: 18px;
            margin-bottom: 20px;
        }
        .error-details {
            background: #ffebee;
            padding: 15px;
            border-radius: 5px;
            color: #c62828;
            font-family: monospace;
            margin: 20px 0;
        }
        .instructions {
            color: #666;
            font-size: 14px;
            margin-top: 20px;
        }
        .retry-button {
            background: #2196F3;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 20px;
        }
        .retry-button:hover {
            background: #1976D2;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">❌</div>
        <h2>Authorization Failed</h2>
        <p class="error-message">{{ $message }}</p>
        
        <div class="error-details">
            <strong>Error Details:</strong><br>
            {{ $error }}
        </div>
        
        <div class="instructions">
            <p><strong>What to do:</strong></p>
            <p>1. Check your Google Cloud Console settings</p>
            <p>2. Make sure redirect URI is correct</p>
            <p>3. Ensure you're added as a test user</p>
            <p>4. Try the authorization process again</p>
        </div>
        
        <button class="retry-button" onclick="window.close()">Close Window</button>
    </div>
</body>
</html>
