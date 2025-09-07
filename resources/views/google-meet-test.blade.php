<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Google Meet Integration Test</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }
        .test-section {
            margin: 20px 0;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #f9f9f9;
        }
        .test-section h3 {
            color: #2c3e50;
            margin-top: 0;
        }
        button {
            background-color: #4285f4;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            margin: 10px 5px;
        }
        button:hover {
            background-color: #3367d6;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 6px;
            margin: 10px 0;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 6px;
            margin: 10px 0;
        }
        .info {
            background-color: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 6px;
            margin: 10px 0;
        }
        .code-block {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: 15px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            overflow-x: auto;
            margin: 10px 0;
        }
        .status {
            padding: 10px;
            border-radius: 6px;
            margin: 10px 0;
            font-weight: bold;
        }
        .status.pending {
            background-color: #fff3cd;
            color: #856404;
        }
        .status.success {
            background-color: #d4edda;
            color: #155724;
        }
        .status.error {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Google Meet Integration Test</h1>
        
        <div class="test-section">
            <h3>📋 Step 1: Get Google OAuth2 Authorization URL</h3>
            <p>Click the button below to get the Google OAuth2 authorization URL:</p>
            <button onclick="getAuthUrl()">Get Authorization URL</button>
            <div id="authUrlResult"></div>
        </div>

        <div class="test-section">
            <h3>🔗 Step 2: Complete OAuth2 Flow</h3>
            <p>After getting the authorization URL, click the button below to start the OAuth2 flow:</p>
            <button onclick="startOAuth2Flow()" id="oauth2Button" disabled>Start OAuth2 Flow</button>
            <div id="oauth2Result"></div>
        </div>

        <div class="test-section">
            <h3>✅ Step 3: Check OAuth2 Status</h3>
            <p>Check if the OAuth2 authorization was successful:</p>
            <button onclick="checkOAuth2Status()">Check OAuth2 Status</button>
            <div id="oauth2Status"></div>
        </div>

        <div class="test-section">
            <h3>🎥 Step 4: Test Google Meet Link Generation</h3>
            <p>Test creating a Google Meet link:</p>
            <button onclick="testGoogleMeet()">Test Google Meet</button>
            <div id="googleMeetResult"></div>
        </div>

        <div class="test-section">
            <h3>📚 Step 5: Test Training with Google Meet</h3>
            <p>Create a training with Google Meet integration:</p>
            <button onclick="testTrainingWithMeet()">Create Training with Meet</button>
            <div id="trainingResult"></div>
        </div>

        <div class="test-section">
            <h3>🔍 Step 6: Test HTML Pages Directly</h3>
            <p>Test the OAuth2 callback pages directly:</p>
            <button onclick="testSuccessPage()">Test Success Page</button>
            <button onclick="testErrorPage()">Test Error Page</button>
            <div id="htmlTestResult"></div>
        </div>
    </div>

    <script>
        const BASE_URL = 'http://localhost:8000';
        const AUTH_TOKEN = '34|m1tDPv0d9hmb6S9nWTLJgeMLIeXU4p7JQUp3pv5ea41a8e42';
        let authUrl = '';

        async function getAuthUrl() {
            const resultDiv = document.getElementById('authUrlResult');
            resultDiv.innerHTML = '<div class="status pending">Getting authorization URL...</div>';

            try {
                const response = await fetch(`${BASE_URL}/api/v1/google/auth-url`, {
                    method: 'GET',
                    headers: {
                        'Authorization': `Bearer ${AUTH_TOKEN}`,
                        'Content-Type': 'application/json'
                    }
                });

                const data = await response.json();
                
                if (data.success) {
                    authUrl = data.auth_url;
                    resultDiv.innerHTML = `
                        <div class="status success">✅ Authorization URL received!</div>
                        <div class="info">
                            <strong>Auth URL:</strong><br>
                            <div class="code-block">${authUrl}</div>
                        </div>
                    `;
                    document.getElementById('oauth2Button').disabled = false;
                } else {
                    resultDiv.innerHTML = `<div class="status error">❌ Error: ${data.error || 'Unknown error'}</div>`;
                }
            } catch (error) {
                resultDiv.innerHTML = `<div class="status error">❌ Network Error: ${error.message}</div>`;
            }
        }

        function startOAuth2Flow() {
            if (authUrl) {
                window.open(authUrl, '_blank');
                document.getElementById('oauth2Result').innerHTML = `
                    <div class="info">
                        🔗 OAuth2 flow opened in new window. Complete the authorization and then check the status.
                    </div>
                `;
            } else {
                document.getElementById('oauth2Result').innerHTML = `
                    <div class="status error">❌ Please get the authorization URL first.</div>
                `;
            }
        }

        async function checkOAuth2Status() {
            const resultDiv = document.getElementById('oauth2Status');
            resultDiv.innerHTML = '<div class="status pending">Checking OAuth2 status...</div>';

            try {
                const response = await fetch(`${BASE_URL}/api/v1/google/oauth2-code`, {
                    method: 'GET',
                    headers: {
                        'Authorization': `Bearer ${AUTH_TOKEN}`,
                        'Content-Type': 'application/json'
                    }
                });

                const data = await response.json();
                
                if (data.success) {
                    resultDiv.innerHTML = `
                        <div class="status success">✅ OAuth2 authorization successful!</div>
                        <div class="info">
                            <strong>Code:</strong> ${data.code}<br>
                            <strong>Message:</strong> ${data.message}
                        </div>
                    `;
                } else {
                    resultDiv.innerHTML = `
                        <div class="status error">❌ OAuth2 not completed yet</div>
                        <div class="info">${data.message}</div>
                    `;
                }
            } catch (error) {
                resultDiv.innerHTML = `<div class="status error">❌ Network Error: ${error.message}</div>`;
            }
        }

        async function testGoogleMeet() {
            const resultDiv = document.getElementById('googleMeetResult');
            resultDiv.innerHTML = '<div class="status pending">Testing Google Meet link generation...</div>';

            try {
                const response = await fetch(`${BASE_URL}/api/v1/google/meet-link`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${AUTH_TOKEN}`,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        title: 'Test Google Meet Session',
                        description: 'Testing Google Meet integration',
                        start_time: '2025-01-15T10:00:00Z',
                        end_time: '2025-01-15T11:00:00Z'
                    })
                });

                const data = await response.json();
                
                if (data.success) {
                    resultDiv.innerHTML = `
                        <div class="status success">✅ Google Meet link generated!</div>
                        <div class="info">
                            <strong>Meet Link:</strong> <a href="${data.meet_link}" target="_blank">${data.meet_link}</a><br>
                            <strong>Event ID:</strong> ${data.event_id}
                        </div>
                    `;
                } else {
                    resultDiv.innerHTML = `<div class="status error">❌ Error: ${data.error || 'Unknown error'}</div>`;
                }
            } catch (error) {
                resultDiv.innerHTML = `<div class="status error">❌ Network Error: ${error.message}</div>`;
            }
        }

        async function testTrainingWithMeet() {
            const resultDiv = document.getElementById('trainingResult');
            resultDiv.innerHTML = '<div class="status pending">Creating training with Google Meet...</div>';

            try {
                const response = await fetch(`${BASE_URL}/api/v1/trainings`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${AUTH_TOKEN}`,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        title: 'Google Meet Training Test',
                        description: 'Testing Google Meet integration with training',
                        category: 'Google Meet Test',
                        trainer_id: 36,
                        start_date: '2025-01-15',
                        end_date: '2025-01-15',
                        is_online: true,
                        type: 'online',
                        online_details: {
                            participant_size: '50',
                            google_meet_link: 'https://meet.google.com/test-link'
                        }
                    })
                });

                const data = await response.json();
                
                if (data.id) {
                    resultDiv.innerHTML = `
                        <div class="status success">✅ Training created successfully!</div>
                        <div class="info">
                            <strong>Training ID:</strong> ${data.id}<br>
                            <strong>Title:</strong> ${data.title}<br>
                            <strong>Google Meet Link:</strong> <a href="${data.online_details.google_meet_link}" target="_blank">${data.online_details.google_meet_link}</a>
                        </div>
                    `;
                } else {
                    resultDiv.innerHTML = `<div class="status error">❌ Error creating training</div>`;
                }
            } catch (error) {
                resultDiv.innerHTML = `<div class="status error">❌ Network Error: ${error.message}</div>`;
            }
        }

        function testSuccessPage() {
            window.open(`${BASE_URL}/oauth2/callback?code=test_code`, '_blank');
            document.getElementById('htmlTestResult').innerHTML = `
                <div class="info">🔗 Success page opened in new window</div>
            `;
        }

        function testErrorPage() {
            window.open(`${BASE_URL}/oauth2/callback?error=access_denied`, '_blank');
            document.getElementById('htmlTestResult').innerHTML = `
                <div class="info">🔗 Error page opened in new window</div>
            `;
        }
    </script>
</body>
</html>
