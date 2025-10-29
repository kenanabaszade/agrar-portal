#!/bin/bash

# Certificate PDF Generation Setup Script
# This script sets up the Python environment for certificate generation

echo "Setting up Certificate PDF Generation System..."

# Check if Python 3 is installed
if ! command -v python3 &> /dev/null; then
    echo "Python 3 is not installed. Please install Python 3 first."
    exit 1
fi

# Check if pip is installed
if ! command -v pip3 &> /dev/null; then
    echo "pip3 is not installed. Please install pip3 first."
    exit 1
fi

# Install Python dependencies
echo "Installing Python dependencies..."
pip3 install -r requirements.txt

# Install Chrome/Chromium for headless PDF generation
echo "Installing Chrome dependencies..."

# Detect OS and install Chrome
if [[ "$OSTYPE" == "linux-gnu"* ]]; then
    # Linux
    if command -v apt-get &> /dev/null; then
        # Ubuntu/Debian
        wget -q -O - https://dl.google.com/linux/linux_signing_key.pub | sudo apt-key add -
        echo "deb [arch=amd64] http://dl.google.com/linux/chrome/deb/ stable main" | sudo tee /etc/apt/sources.list.d/google-chrome.list
        sudo apt-get update
        sudo apt-get install -y google-chrome-stable
    elif command -v yum &> /dev/null; then
        # CentOS/RHEL
        sudo yum install -y google-chrome-stable
    fi
elif [[ "$OSTYPE" == "darwin"* ]]; then
    # macOS
    if command -v brew &> /dev/null; then
        brew install --cask google-chrome
    else
        echo "Please install Chrome manually on macOS"
    fi
elif [[ "$OSTYPE" == "msys" ]] || [[ "$OSTYPE" == "win32" ]]; then
    # Windows
    echo "Please install Chrome manually on Windows"
fi

# Create necessary directories
echo "Creating directories..."
mkdir -p generated_certificates
mkdir -p certificate

# Set permissions
chmod +x certificate_generator.py

# Test the setup
echo "Testing Python script..."
python3 certificate_generator.py '{"user":{"id":1,"first_name":"Test","last_name":"User","email":"test@example.com"},"exam":{"id":1,"title":"Test Exam","description":"Test Description"},"training":{"id":1,"title":"Test Training","description":"Test Training Description"}}'

if [ $? -eq 0 ]; then
    echo "Setup completed successfully!"
    echo ""
    echo "Certificate generation system is ready."
    echo "When a user passes an exam, a PDF certificate will be automatically generated."
    echo "Certificates can be verified at: https://your-domain.com/api/certificates/verify/{digital_signature}"
    echo "PDFs can be downloaded at: https://your-domain.com/api/certificates/download/{digital_signature}"
else
    echo "Setup failed. Please check the error messages above."
    exit 1
fi

