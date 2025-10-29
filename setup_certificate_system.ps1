# PowerShell Certificate System Setup Script
Write-Host "Setting up Certificate PDF Generation System for Windows..." -ForegroundColor Green

# Check if Python is installed
try {
    $pythonVersion = python --version 2>&1
    if ($LASTEXITCODE -eq 0) {
        Write-Host "Python found: $pythonVersion" -ForegroundColor Green
    } else {
        throw "Python not found"
    }
} catch {
    Write-Host "Python is not installed!" -ForegroundColor Red
    Write-Host "Please install Python 3 from:" -ForegroundColor Yellow
    Write-Host "1. Microsoft Store (recommended)" -ForegroundColor Yellow
    Write-Host "2. python.org" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "After installation, restart this script." -ForegroundColor Yellow
    Read-Host "Press Enter to continue"
    exit 1
}

Write-Host "Installing Python dependencies..." -ForegroundColor Yellow

# Install Python dependencies
try {
    pip install -r requirements.txt
    if ($LASTEXITCODE -eq 0) {
        Write-Host "Dependencies installed successfully!" -ForegroundColor Green
    } else {
        throw "Failed to install dependencies"
    }
} catch {
    Write-Host "Failed to install Python dependencies!" -ForegroundColor Red
    Read-Host "Press Enter to continue"
    exit 1
}

# Create directories
if (!(Test-Path "generated_certificates")) {
    New-Item -ItemType Directory -Name "generated_certificates" | Out-Null
    Write-Host "Created generated_certificates directory" -ForegroundColor Green
}

if (!(Test-Path "certificate")) {
    New-Item -ItemType Directory -Name "certificate" | Out-Null
    Write-Host "Created certificate directory" -ForegroundColor Green
}

Write-Host ""
Write-Host "Setup completed successfully!" -ForegroundColor Green
Write-Host ""
Write-Host "Certificate generation system is ready." -ForegroundColor Cyan
Write-Host "When a user passes an exam, a PDF certificate will be automatically generated." -ForegroundColor Cyan
Write-Host "Certificates can be verified at: https://your-domain.com/api/certificates/verify/{digital_signature}" -ForegroundColor Cyan
Write-Host "PDFs can be downloaded at: https://your-domain.com/api/certificates/download/{digital_signature}" -ForegroundColor Cyan
Write-Host ""
Write-Host "To test the system, run: .\test_certificate_generator.ps1" -ForegroundColor Yellow
Write-Host ""
Read-Host "Press Enter to continue"

