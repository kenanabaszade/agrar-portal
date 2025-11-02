# Certificate Generation System - Server Requirements & Documentation

## Server Requirements

### Required Software

1. **PHP 8.2 or higher**
   - Required extensions:
     - `gd` (for image processing)
     - `fileinfo` (for MIME type detection)  
     - `curl` or `allow_url_fopen` enabled (for fetching external images)
   
   **Verify extensions:**
   ```bash
   php -m | grep -E "gd|fileinfo|curl"
   ```

2. **Google Chrome or Chromium Browser** (CRITICAL)
   - Required for PDF generation from HTML templates
   - Must be installed and accessible via command line
   - Common installation paths:
     - **Windows**: `C:\Program Files\Google\Chrome\Application\chrome.exe`
     - **Linux**: `/usr/bin/google-chrome` or `/usr/bin/chromium`
     - **macOS**: `/Applications/Google Chrome.app/Contents/MacOS/Google Chrome`
   
   **Installation:**
   - Windows: Download from [google.com/chrome](https://www.google.com/chrome/)
   - Linux: 
     ```bash
     # Ubuntu/Debian
     sudo apt update
     sudo apt install chromium-browser
     # OR
     sudo apt install google-chrome-stable
     ```
   - macOS: Download from [google.com/chrome](https://www.google.com/chrome/)
   
   **Verify installation:**
   ```bash
   # Windows
   "C:\Program Files\Google\Chrome\Application\chrome.exe" --version
   
   # Linux/macOS
   google-chrome --version
   # OR
   chromium --version
   ```

3. **Composer Dependencies**
   - `endroid/qr-code` (v6.0+) - For QR code generation
   - All dependencies are installed via `composer install`
   
   **Install:**
   ```bash
   composer install
   ```

### Important Notes

- **Python is NOT required** - The system has been migrated to PHP-only
- **Selenium is NOT required** - We use Chrome's built-in `--print-to-pdf` feature
- **No additional drivers needed** - Chrome works directly via command line

### File System Permissions
- **Storage directory**: `storage/app/public/certificates/` must be writable
- **Certificate template directory**: `certificate/` must be readable
- **Temporary directory**: System temp directory (for HTML files and Chrome user data)

### Directory Structure
```
project-root/
├── certificate/
│   ├── certificate.html      # HTML template
│   ├── image.png             # Ministry logo
│   ├── aqrar_logo_1.png      # Organization logos (optional)
│   └── aqrar_logo_2.png      # Organization logos (optional)
├── storage/
│   └── app/
│       └── public/
│           └── certificates/  # Generated PDF certificates (auto-created)
└── app/
    └── Services/
        └── CertificateGeneratorService.php
```

## How It Works

### Certificate Generation Flow

1. **Trigger**: Certificate generation is triggered when:
   - A user passes an exam (`ExamController::submit()`)
   - Manual certificate generation via API
   - On-demand generation when viewing/downloading a certificate

2. **Data Preparation** (`CertificateGeneratorService::generateCertificate()`):
   - User data (name, ID, email)
   - Exam data (title, description, certificate description)
   - Training data (title, description)

3. **HTML Generation** (`createCertificateHtml()`):
   - Reads the HTML template from `certificate/certificate.html`
   - Replaces placeholders:
     - `CAHİD HÜMBƏTOV` → User's full name (uppercase)
     - `AZ-2025-001234` → Certificate number (AZ-YYYY-XXXXXX format)
     - `27 / 10 / 2025` → Issue date
     - `PLACEHOLDER_EXAM_NAME` → Exam title
     - Long certificate description → Custom or default description
     - `PLACEHOLDER_QR_CODE` → Base64 encoded QR code
     - `href=""` → Verification URL
     - `./image.png` → file:// URL for local images
   - Converts external image URLs (https://) to base64 data URIs automatically

4. **QR Code Generation** (`generateQrCode()`):
   - Uses `endroid/qr-code` library
   - Creates QR code pointing to PDF download URL
   - Encodes as base64 PNG data URI

5. **Digital Signature** (`generateDigitalSignature()`):
   - Creates SHA256 hash from: user ID, exam ID, exam title, user name, timestamp
   - Used for certificate verification and unique identification

6. **PDF Generation** (`generatePdf()`):
   - Creates temporary HTML file in system temp directory
   - Creates temporary Chrome user data directory (required for headless mode)
   - Executes Chrome headless with `--print-to-pdf` option
   - Chrome options:
     - `--headless=new` - Headless mode
     - `--no-sandbox` - Required for some server environments
     - `--user-data-dir` - Temporary directory for Chrome profile
     - `--print-to-pdf` - Output PDF file path
     - `--print-to-pdf-no-header` - Remove header/footer
   - Validates PDF was created successfully
   - Cleans up temporary files and directories

7. **Storage**:
   - PDF saved to: `storage/app/public/certificates/certificate_{signature}.pdf`
   - Certificate record updated with PDF path
   - Registration linked to certificate

### Image Handling

1. **Local Images** (`./image.png`):
   - Converted to `file://` URL format
   - Chrome can access local files via file:// protocol

2. **External Images** (https:// URLs):
   - Automatically detected via regex in HTML
   - Fetched using PHP's `file_get_contents()` with stream context
   - Converted to base64 data URIs
   - Embedded directly in HTML (no network requests needed in Chrome)

3. **QR Code**:
   - Generated as PNG using PHP library
   - Encoded as base64 data URI
   - No external dependencies

### Chrome Command Example

```bash
"C:\Program Files\Google\Chrome\Application\chrome.exe" \
  --headless=new \
  --no-sandbox \
  --disable-dev-shm-usage \
  --disable-gpu \
  --user-data-dir="C:\Temp\chrome_user_data_xyz" \
  --print-to-pdf="C:\path\to\output.pdf" \
  --print-to-pdf-no-header \
  "file:///C:/path/to/temp.html"
```

### Security Considerations

1. **User Data Directory**: 
   - Created with unique name per request
   - Automatically cleaned up after PDF generation
   - Prevents conflicts between concurrent requests

2. **File Permissions**:
   - Only web server user needs write access to storage
   - Certificate templates are read-only

3. **Input Validation**:
   - All user data is sanitized before HTML generation
   - File paths are validated before use

### Troubleshooting

**Chrome not found:**
- Check Chrome installation path
- Verify PATH environment variable includes Chrome directory
- Check file permissions for Chrome executable

**PDF generation fails:**
- Check Chrome user data directory permissions
- Verify sufficient disk space
- Check system temp directory is writable
- Review Laravel logs for specific errors

**Images not appearing:**
- Verify `certificate/image.png` exists
- Check external image URLs are accessible
- Review base64 conversion logs

**Preview shows blank page:**
- Verify PDF file size > 0
- Check PDF header validation (should start with `%PDF`)
- Ensure proper Content-Type headers

### Performance Considerations

- **Image Conversion**: External images are fetched and converted once during HTML generation
- **Chrome Process**: Each PDF generation spawns a new Chrome process (typical duration: 1-3 seconds)
- **Caching**: Generated PDFs are stored and reused (not regenerated unless needed)
- **Concurrent Requests**: Each request uses unique temp directories to avoid conflicts

## Quick Setup Guide

### 1. Verify PHP Extensions
```bash
php -m | grep -E "gd|fileinfo"
```

If missing, install:
- **Ubuntu/Debian**: `sudo apt install php8.2-gd php8.2-fileinfo`
- **Windows**: Enable in `php.ini`

### 2. Install Chrome
- **Windows**: Download and install from google.com/chrome
- **Linux**: `sudo apt install chromium-browser`
- **macOS**: Download from google.com/chrome

### 3. Verify Chrome Access
```bash
# Test Chrome can be executed
chrome --version
# OR
chromium --version
```

### 4. Install Composer Dependencies
```bash
composer install
```

This will automatically install `endroid/qr-code` and all other dependencies.

### 5. Set Directory Permissions
```bash
# Ensure storage is writable
chmod -R 775 storage
chown -R www-data:www-data storage  # Adjust user/group as needed
```

### 6. Create Storage Link
```bash
php artisan storage:link
```

## Migration from Python (Already Completed)

The system has been migrated from Python to PHP-only. Python files have been removed.

**Benefits of PHP-only solution:**
- No Python dependency required
- Simpler deployment (only PHP + Chrome needed)
- Better error handling and logging
- Consistent with Laravel ecosystem
- Easier to maintain and debug
- No subprocess complexity
- Faster execution (no Python startup overhead)

