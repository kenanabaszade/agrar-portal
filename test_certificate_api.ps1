# PowerShell API Test Script
Write-Host "Testing Certificate API..." -ForegroundColor Green

# Test data
$testData = @{
    user_id = 1
    exam_id = 1
    training_id = 1
} | ConvertTo-Json

try {
    Write-Host "Sending request to Laravel API..." -ForegroundColor Yellow
    
    # Send POST request
    $response = Invoke-RestMethod -Uri "http://localhost:8000/api/certificates/generate-pdf-test" -Method POST -Body $testData -ContentType "application/json"
    
    Write-Host "API Response:" -ForegroundColor Green
    $response | ConvertTo-Json -Depth 3
    
    if ($response.success) {
        Write-Host ""
        Write-Host "Certificate created successfully!" -ForegroundColor Green
        Write-Host "Digital Signature: $($response.digital_signature)" -ForegroundColor Cyan
        Write-Host "Verification URL: $($response.verification_url)" -ForegroundColor Cyan
        
        # Test verification
        Write-Host ""
        Write-Host "Testing verification..." -ForegroundColor Yellow
        $verifyResponse = Invoke-RestMethod -Uri $response.verification_url -Method GET
        
        Write-Host "Verification Response:" -ForegroundColor Green
        $verifyResponse | ConvertTo-Json -Depth 3
    }
    
} catch {
    Write-Host "Error: $($_.Exception.Message)" -ForegroundColor Red
    Write-Host "Make sure Laravel server is running on localhost:8000" -ForegroundColor Yellow
}

Write-Host ""
Read-Host "Press Enter to continue"
