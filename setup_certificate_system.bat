@echo off
echo Setting up Certificate PDF Generation System for Windows...

REM Check if Python is installed
python --version >nul 2>&1
if %errorlevel% neq 0 (
    echo Python is not installed!
    echo Please install Python 3 from:
    echo 1. Microsoft Store (recommended)
    echo 2. python.org
    echo.
    echo After installation, restart this script.
    pause
    exit /b 1
)

echo Python found! Installing dependencies...

REM Install Python dependencies
pip install -r requirements.txt
if %errorlevel% neq 0 (
    echo Failed to install Python dependencies!
    pause
    exit /b 1
)

REM Create directories
if not exist "generated_certificates" mkdir generated_certificates
if not exist "certificate" mkdir certificate

echo.
echo Setup completed successfully!
echo.
echo Certificate generation system is ready.
echo When a user passes an exam, a PDF certificate will be automatically generated.
echo Certificates can be verified at: https://your-domain.com/api/certificates/verify/{digital_signature}
echo PDFs can be downloaded at: https://your-domain.com/api/certificates/download/{digital_signature}
echo.
echo To test the system, run: test_certificate_generator.bat
echo.
pause

