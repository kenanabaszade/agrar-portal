<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OAuth2 Success - Agrar Portal</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .success-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        .success-icon {
            color: #4CAF50;
            font-size: 48px;
            margin-bottom: 20px;
        }
        .success-message {
            color: #333;
            font-size: 18px;
            margin-bottom: 20px;
        }
        .code-display {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            font-family: monospace;
            word-break: break-all;
            margin: 20px 0;
        }
        .instructions {
            color: #666;
            font-size: 14px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="success-container">
        <div class="success-icon">✅</div>
        <h2>Authorization Successful!</h2>
        <p class="success-message">{{ $message }}</p>
        
        <div class="code-display">
            <strong>Authorization Code:</strong><br>
            {{ $code }}
        </div>
        
        <div class="instructions">
            <p><strong>Next Steps:</strong></p>
            <p>1. Copy the authorization code above</p>
            <p>2. Return to your application</p>
            <p>3. Use the code to complete the OAuth2 flow</p>
            <p>4. You can now close this window</p>
        </div>
    </div>
</body>
</html>
