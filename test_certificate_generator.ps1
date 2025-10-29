# PowerShell Certificate Generator Test Script
Write-Host "Testing Certificate Generator..." -ForegroundColor Green

try {
    Write-Host "Running certificate generator test using test_data.json..." -ForegroundColor Yellow
    $result = python certificate_generator.py --file test_data.json
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host "Test completed successfully!" -ForegroundColor Green
        Write-Host "Result: $result" -ForegroundColor Cyan
        
        # Parse the result to show key information
        try {
            $jsonResult = $result | ConvertFrom-Json
            if ($jsonResult.success) {
                Write-Host ""
                Write-Host "Certificate Details:" -ForegroundColor Yellow
                Write-Host "Digital Signature: $($jsonResult.digital_signature)" -ForegroundColor Cyan
                Write-Host "Certificate Number: $($jsonResult.certificate_number)" -ForegroundColor Cyan
                Write-Host "PDF Path: $($jsonResult.pdf_path)" -ForegroundColor Cyan
                Write-Host "Verification URL: $($jsonResult.verification_url)" -ForegroundColor Cyan
                
                # Check if PDF file exists
                if (Test-Path $jsonResult.pdf_path) {
                    Write-Host "PDF file created successfully!" -ForegroundColor Green
                } else {
                    Write-Host "PDF file not found!" -ForegroundColor Red
                }
            } else {
                Write-Host "Certificate generation failed: $($jsonResult.error)" -ForegroundColor Red
            }
        } catch {
            Write-Host "Could not parse result JSON" -ForegroundColor Yellow
        }
    } else {
        Write-Host "Test failed!" -ForegroundColor Red
        Write-Host "Error: $result" -ForegroundColor Red
    }
} catch {
    Write-Host "Python is not installed or not in PATH." -ForegroundColor Red
    Write-Host "Please install Python 3 from Microsoft Store or python.org" -ForegroundColor Yellow
}

Write-Host ""
Read-Host "Press Enter to continue"
